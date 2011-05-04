<?php 
namespace gateway\workers\queue;
use gateway\workers\queue\QueueItem;
use gateway\protocol\SmppClient;

/**
 * Class to represent smsrequest objects. The objects will be serialized in the message queue.
 * This object must contain all data the worker needs to process the SMS.
 * For ease of use it will accept multiple recipients, and convert them to \SMPP\Address objects.
 * 
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class SmsRequest extends QueueItem
{
	const TYPE=1;
	
	public $id;
	public $sender;
	public $message;
	public $recipients;
	public $dataCoding;
	
	/**
	 * A request for a SmsWorker to send a SMS.
	 * The $recipients must be an array of international (E164) numbers.
	 * The sender can be either alphanumeric, international, or national formatted. 
	 * If the sender address is 4-digits or less it's assumed to be a short number, and 
	 * the type will be set to national, otherwise it must be an international number.
	 * If $dataCoding is set to default the $message will be automatically converted to GSM 03.38.
	 * 
	 * @param string $message
	 * @param array $recipients
	 * @param string $sender
	 * @param integer $id - the request id, used for matching with the response
	 * @param integer $dataCoding
	 */
	public function __construct($message,$recipients,$sender,$id,$dataCoding=0x00)
	{
		$this->message = $message;
		$this->recipients = $recipients;
		$this->sender = $sender;
		$this->id = $id;
		$this->dataCoding = $dataCoding;
	}
	
	public function serialize()
	{
		foreach($this->recipients as &$recipient) {
			$recipient = (int) $recipient; // cast them all to integers first
		}
		return serialize(array($this->id,$this->sender,$this->recipients,$this->dataCoding,$this->message));
	}
	
	public function unserialize($serialized)
	{
		list($this->id,$this->sender,$this->recipients,$this->dataCoding,$this->message) = unserialize($serialized);
	}
}