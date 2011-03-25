<?php
namespace gateway\workers;

use gateway\protocol\SmppClient;
use gateway\protocol\GsmEncoder;
use gateway\workers\queue\SmsRequest;
use gateway\workers\queue\SmsResponse;

/**
 * SMS sender worker. 
 * Since the worker uses an IPC message queue, one can fork several of these workers. 
 * Each worker maintains it's own connection to the SMSC, and will exit if the parent process also exists.
 * The implementation uses the posix, pcntl and semaphore extensions to be able to fork several workers and 
 * keep everything synchronised through a IPC Message Queue.
 * The default implementation pushes ID'es from the SMSC back through the same message queue. If you do not
 * override this remember to read the ID'es from the queue, or it will fill up eventually.
 * 
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class SmsSender
{
	protected $client;
	protected $transport;
	protected $queue;
	protected $options;
	protected $lastEnquireLink;
	
	/**
	 * Construct a new SmsSender
	 * This will prepare the transport and SMPP client.
	 * It works with either a pool of hosts (arrays), or a single host.
	 * 
	 * It will use the following default options, but you can override them by specifing an $options array.
	 * array(
	 * 	'persistent_connections' => false,
	 * 	'null_terminate_octetstrings' => false,
	 * 	'use_msg_payload_for_csms' => true,
	 * 	'registered_delivery_flag' => \SMPP\REG_DELIVERY_SMSC_BOTH,
	 * 	'debug_handler' => null,
	 *  'debug' => false,
	 * 	'enquire_link_timeout' => 30,
	 * 	'recv_timeout' => 10,
	 * 	'send_timeout' => 10,
	 * 	'username' => 'JaneDoe',
	 * 	'password' => 'iHeartPasswordz',
	 * 	'max_object_size' => 65536
	 * )
	 * 
	 * @param mixed $hostname
	 * @param mixed $port
	 * @param resource $queue
	 * @param array $options
	 */
	public function __construct($hostname, $port, $queue, $options=null)
	{
		if (!is_resource($queue)) throw new \InvalidArgumentException('Queue must be an IPC message queue resource');
		
		// Merge options
		if (is_null($options)) $options = array();
		$defaultOptions = array(
			'persistent_connections' => false,
			'null_terminate_octetstrings' => false,
			'use_msg_payload_for_csms' => true,
			'registered_delivery_flag' => 0x01,
			'debug_handler' => null,
			'debug' => false,
			'enquire_link_timeout' => 30,
			'recv_timeout' => 10000,
			'send_timeout' => 10000,
			'username' => 'JaneDoe',
			'password' => 'iHeartPasswordz',
			'max_object_size' => 65536
		);
		$this->options = $options = array_merge($defaultOptions,$options);
		
		
		// If given an array of hosts use a socket-pool, otherwise just a single socket
		if (is_array($hostname) && is_array($port)) {
			$this->transport = new \gateway\transport\TSocketPool($hostname, $port, $options['persistent_connections'], $options['debug_handler']);
		} else {
			$this->transport = new \gateway\transport\TSocket($hostname, $port, $options['persistent_connections'], $options['debug_handler']);
		}
		$this->transport->setDebug($options['debug']);
		
		$this->client = new SmppClient($this->transport);
		$this->client->debug = $options['debug'];
		
		// Set static options for SMPP client.
		SmppClient::$sms_null_terminate_octetstrings = $options['null_terminate_octetstrings'];
		SmppClient::$sms_use_msg_payload_for_csms = $options['use_msg_payload_for_csms'];
		SmppClient::$sms_registered_delivery_flag = $options['registered_delivery_flag'];
		
		// Set transport timeouts
		$this->transport->setRecvTimeout($options['recv_timeout']);
		$this->transport->setSendTimeout($options['send_timeout']);
		
		$this->queue = $queue;
		$this->lastEnquireLink = 0;
	}
	
	/**
	 * Open transport connection and bind as a transmitter
	 */
	protected function connect()
	{
		$this->transport->open();
		$this->client->bindTransmitter($this->options['username'],$this->options['password']);
	}
	
	/**
	 * The main loop of the worker
	 */
	public function run()
	{
		$this->connect();
		
		while (true) {
			// commit suicide if the parent process no longer exists
			if (posix_getppid() == 1) exit();
			
			// Make sure to send enquire link periodically to keep the link alive
			if (time()-$this->lastEnquireLink >= $this->options['enquire_link_timeout']) {
				$this->ping();
				$this->lastEnquireLink = time();
			}
			
			// Check for new messages
			$res = msg_receive($this->queue, SmsRequest::TYPE, $msgtype, $this->options['max_object_size'], $sms, true, \MSG_IPC_NOWAIT, $errorcode);
			
			if (!$res && $errorcode === \MSG_ENOMSG) { // No messages for us
				// Sleep 0.01 seconds between each iteration, to avoid wasting CPU
				usleep(10000);
				continue;
			}
			if (!$res) { // something bad happend to our queue
				exit('Message queue receive failed, parent probably exited, errorcode: '.$errorcode);
			}
			if (!$sms instanceof SmsRequest) throw new \InvalidArgumentException('Unknown message received');

			// Prepare message
			if ($sms->dataCoding == \SMPP\DATA_CODING_DEFAULT) {
				$encoded = GsmEncoder::utf8_to_gsm0338($sms->message);
			} else {
				$encoded = $message;
			}
			
			// Contruct SMPP Address objects
			if (!ctype_digit($sms->sender)) {
				$sender = new \SMPP\Address($sms->sender,\SMPP\TON_ALPHANUMERIC);
			} else if ($sender < 10000) {
				$sender = new \SMPP\Address($sms->sender,SMPP\TON_NATIONAL,SMPP\NPI_E164);
			} else {
				$sender = new \SMPP\Address($sms->sender,\SMPP\TON_INTERNATIONAL,\SMPP\NPI_E164);
			}
			
			// Send message
			$ids = array();
			try {
				$i = 0;
				foreach ($sms->recipients as $number) {
					$address = new \SMPP\Address($number,\SMPP\TON_INTERNATIONAL,\SMPP\NPI_E164);
					$ids[] = $this->client->sendSMS($sender, $address, $encoded, null, $sms->dataCoding);
					
					if ($i++ % 10 == 0) { // relay back for every 10 SMSes
						$this->relaySmsIds($sms->id, $ids);
						$ids = array();
					}
				}	
			} catch (\Exception $e) {
				if (!empty($ids)) { // make sure to report any partial progress back
					$this->relaySmsIds($sms->id, $ids); 
				}
				throw $e; // rethrow
			}
			
			$this->relaySmsIds($sms->id, $ids);
		}
	}
	
	/**
	 * Ping the SMSC (enquire link).
	 * Override to ie. also ping other servers
	 */
	protected function ping()
	{
		$this->client->enquireLink();
	}
	
	/**
	 * Relay the SMS ids returned by the SMSC back.
	 * The default implementation uses the message queue to send them back, but you can override this method.
	 * 
	 * @param string $requestId
	 * @param array $ids
	 */
	protected function relaySmsIds($requestId, $ids)
	{
		// Send the IDs back with a SmsResponse object
		msg_send($this->queue, SmsResponse::TYPE, new SmsResponse($requestId, $ids), true);
	}
}
