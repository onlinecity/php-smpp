<?php
namespace gateway\workers\queue;
use gateway\workers\queue\QueueItem;

/**
 * Objects of this class will be returned by the workers.
 * They contain a mapping between a request id, and one or more sms ids.
 * This mapping is used for processing delivery reports.
 * 
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class SmsResponse extends QueueItem
{
	const TYPE=2;
	
	public $id;
	public $smsIds;
	
	/**
	 * A response from a worker, with IDs returned from SMSC
	 * 
	 * @param integer $id
	 * @param array $smsIds
	 */
	public function __construct($id,$smsIds)
	{
		$this->id = $id;
		$this->smsIds = $smsIds;
	}
	
	public function serialize()
	{
		return serialize(array($this->id,$this->smsIds));
	}
	
	public function unserialize($serialized)
	{
		list($this->id,$this->smsIds) = unserialize($serialized);
	}
}