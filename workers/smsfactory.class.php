<?php
namespace gateway\workers;

/**
 * Factory class for the SmsSender and SmsReceiver workers.
 * This class will fork the required amount of workers, and keep them running until it's closed.
 * The implementation uses the posix and pcntl extensions to support forking. In addition the  
 * SmsSenders use the semaphore extension to keep synchronized through a message queue.
 * The message queue must be implemented at a higher level, since this class will just start the
 * workers and keep them running, but not send or receive data.
 * 
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class SmsFactory
{
	protected $senderClass;
	protected $receiverClass;
	protected $numSenders;
	
	protected $options;
	protected $queue;
	protected $debugHandler;
	protected $senders;
	protected $receiver;
	
	public static $pidFile = 'parent.pid';
	
	public function __construct($senderClass='\gateway\workers\SmsSender',$receiverClass='\gateway\workers\SmsReceiver',$numSenders=10)
	{
		$this->senderClass = $senderClass;
		$this->receiverClass = $receiverClass;
		$this->numSenders = $numSenders;
		$this->senders = array();
	}
	
	
	/**
	 * Start all workers.
	 * 
	 * As a bare minimum the following options should be set:
	 *  hostname,port,username,password
	 * For more options see SmsSender and SmsReceiver classes.
	 * 
	 * @param unknown_type $options
	 * @param unknown_type $queue
	 */
	public function startAll($options, $queue)
	{
		if (!is_resource($queue)) throw new \InvalidArgumentException('Queue must be an IPC message queue resource');
		if (empty($options)) throw new \InvalidArgumentException('Options must be set');
		if (!isset($options['hostname'])) throw new \InvalidArgumentException('Hostname option must be set');
		if (!isset($options['port'])) throw new \InvalidArgumentException('Port option must be set');
		
		$this->options = $options;
		$this->queue = $queue;
		$this->debugHandler = isset($options['debug_handler']) ? $options['debug_handler'] : 'error_log';
		call_user_func($this->debugHandler, "Factory started with pid: ".getmypid());
		file_put_contents(self::$pidFile, getmypid());
		
		$this->fork();
	}
	
	protected function constructReceiver()
	{
		$class = $this->receiverClass;
		$hostname = isset($this->options['recv_hostname']) ? $this->options['recv_hostname'] : $this->options['hostname'];
		$port = isset($this->options['recv_port']) ? $this->options['recv_port'] : $this->options['port'];
		
		return new $class($hostname,$port,$this->options);
	}
	
	protected function constructSender()
	{
		$class = $this->senderClass;
		return new $class($this->options['hostname'],$this->options['port'],$this->queue,$this->options);
	}
	
	private function fork()
	{
		$constructReceiver=true;
		
		for($i=0;$i<($this->numSenders+1);$i++) {
			switch ($pid = pcntl_fork()) {
				case -1: // @fail
					die('Fork failed');
					break;
				case 0: // @child
					$worker = $constructReceiver ? $this->constructReceiver() : $this->constructSender();
					call_user_func($this->debugHandler, "Constructed: ".get_class($worker)." with pid: ".getmypid());
					$worker->run();
					break;
				default: // @parent
					
					// Store PID
					if ($constructReceiver) {
						$this->receiver = $pid;
						$constructReceiver = false;
					} else {
						$this->senders[$pid] = $pid;
					}
					
					if ($i<($this->numSenders)) { // fork more
						continue;
					}
					
					// All children are spawned, wait for something to happen, and respawn if it does
					$exitedPid = pcntl_wait($status);
					
					// What happened to our child?
					if (pcntl_wifsignaled($status)) {
						$what = 'was signaled';
					} else if (pcntl_wifexited($status)) {
						$what = 'has exited';
					} else {
						$what = 'returned for some reason';
					}
					call_user_func($this->debugHandler, "Pid: $exitedPid $what");
					
					// Respawn
					if ($exitedPid == $this->receiver) {
						$constructReceiver = true;
						$this->receiver = null;
					} else {
						unset($this->senders[$exitedPid]);
					}
					$i--;
					call_user_func($this->debugHandler, "Will respawn new ".($constructReceiver ? 'receiver' : 'sender'). " to cover loss in one second");
					sleep(1); // Sleep for one second before respawning child
					continue;
					break;
			}
		}
	}
}