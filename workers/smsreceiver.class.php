<?php
namespace gateway\workers;

use gateway\protocol\SmppClient;
use gateway\transport\TSocket;
use gateway\transport\TTransportException;

/**
 * SMS receiver worker. 
 * This worker maintains it's own connection to the SMSC, and will exit if the parent process also exists.
 * The implementation uses the posix, pcntl to support forking.
 * This class does not receive or send messages to the IPC message queue as the senders. You should override
 * this basic processing methods with something more useful. 
 * 
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class SmsReceiver
{
	protected $client;
	protected $transport;
	protected $options;
	protected $lastEnquireLink;
	
	/**
	 * Construct a new SmsSender
	 * This will prepare the transport and SMPP client.
	 * It works with a single host.
	 * 
	 * It will use the following default options, but you can override them by specifing an $options array.
	 * array(
	 * 	'persistent_connections' => false,
	 * 	'debug_handler' => null,
	 *  'debug' => false,
	 * 	'enquire_link_timeout' => 30,
	 * 	'username' => 'JaneDoe',
	 * 	'password' => 'iHeartPasswordz'
	 * )
	 * 
	 * @param string $hostname
	 * @param integer $port
	 * @param array $options
	 */
	public function __construct($hostname, $port, $options=null)
	{
		// Merge options
		if (is_null($options)) $options = array();
		$defaultOptions = array(
			'persistent_connections' => false,
			'debug_handler' => null,
			'debug' => false,
			'enquire_link_timeout' => 30,
			'recv_timeout' => 30000,
			'send_timeout' => 10000,
			'username' => 'JaneDoe',
			'password' => 'iHeartPasswordz'
		);
		$this->options = $options = array_merge($defaultOptions,$options);
		
		
		$this->transport = new TSocket($hostname, $port, $options['persistent_connections'], $options['debug_handler']);
		$this->transport->setDebug($options['debug']);
		
		$this->client = new SmppClient($this->transport);
		$this->client->debug = $options['debug'];
		
		// Set transport timeouts
		$this->transport->setRecvTimeout($options['recv_timeout']);
		$this->transport->setSendTimeout($options['send_timeout']);
		
		$this->lastEnquireLink = 0;
	}
	
	/**
	 * Open transport connection and bind as a receiver
	 */
	protected function connect()
	{
		$this->transport->open();
		$this->client->bindReceiver($this->options['username'],$this->options['password']);
	}
	
	/**
	 * Do fancy processing here, you probably want to override this method.
	 * @param \SMPP\SMS $sms
	 */
	protected function processSms(\SMPP\SMS $sms)
	{
		call_user_func($this->options['debug_handler'] ?: 'error_log', "Processing SMS:\n".print_r($sms,true)); // dummy
	}
	
	/**
	 * This is a callback method, which is called when the connection times out
	 */
	protected function refreshConnection()
	{
		$this->client->enquireLink();
		$this->lastEnquireLink = time();
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
				$this->refreshConnection();
			}
			
			// Read the SMS and send it to processing
			try {
				$sms = $this->client->readSMS();
				$this->processSms($sms);
			} catch (TTransportException $e) {
				if (time()-$this->lastEnquireLink > 1) { // connection probably timed out, send enquireLink, and try again
					$this->refreshConnection();
					continue;
				} else {
					throw $e; // oh no... something went very wrong?
				}
			}
		}
	}
}
