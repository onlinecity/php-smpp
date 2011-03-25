PHP-based SMPP client lib
=============

This is a simplified SMPP client lib for sending or receiving smses through [SMPP v3.4](http://www.smsforum.net/SMPP_v3_4_Issue1_2.zip).

The socket implementation from [Apache's Thrift](http://thrift.apache.org/) is used for the transport layer components. 

This library is targeted towards PHP 5.3, and as such uses namespaces.

The library is divided into three parts with their own sub-namespace:

 - gateway\protocol - containing everything related to SMPP
 - gateway\transport - the transport components from Apache's Thrift
 - gateway\workers - a multi-process example and basic components

Basic usage example
-----

To send a SMS you can do:

	<?php
	require_once 'autoload.php';
	
	use gateway\protocol\SmppClient;
	use gateway\protocol\GsmEncoder;
	use gateway\transport\TSocket;
	
	// Construct transport and client
	$transport = new TSocket('your.smsc.com',2775);
	$transport->setRecvTimeout(10000);
	$smpp = new SmppClient($transport);
	
	// Activate binary hex-output of server interaction
	$smpp->debug = true;
	
	// Open the connection
	$transport->open();
	$smpp->bindTransmitter("USERNAME","PASSWORD");
	
	// Prepare message
	$message = 'H€llo world';
	$encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
	$from = new \SMPP\Address('SMPP Test',SMPP\TON_ALPHANUMERIC);
	$to = new \SMPP\Address(4512345678,SMPP\TON_INTERNATIONAL,SMPP\NPI_E164);
	
	// Send
	$smpp->sendSMS($from,$to,$encodedMessage,$tags);
	
	// Cleanup
	$smpp->close();
	unset($smpp);
	
	
To receive a SMS:
	
	<?php
	require_once 'autoload.php';

	use gateway\protocol\SmppClient;
	use gateway\transport\TSocket;

	// Construct transport and client
	$transport = new TSocket('your.smsc.com',2775);
	$transport->setRecvTimeout(60000); // for this example wait up to 60 seconds for data
	$smpp = new SmppClient($transport);
	
	// Activate binary hex-output of server interaction
	$smpp->debug = true;

	// Open the connection
	$transport->open();
	$smpp->bindReceiver("USERNAME","PASSWORD");
	
	// Read SMS and output
	$sms = $smpp->readSMS();
	echo "SMS:\n";
	var_dump($sms);
	
	// Cleanup
	$smpp->close();
	unset($smpp);
	
Multi-process use example
-----

This example will run 10 sender workers, and a single receiver for delivery receipts.

Start all the workers by running:

	<?php
	// File "run.php"
	require_once 'autoload.php';
	
	$options = array(
		'hostname' => 'your.smsc.com',
		'port' => 2775,
		'username' => 'USERNAME',
		'password' => 'PASSWORD',
		'debug' => false
	);
	
	$queue = msg_get_queue(ftok(realpath('autoload.php'),'S'));
	$factory = new \gateway\workers\SmsFactory();
	$factory->startAll($options, $queue);
	
The factory (and thus the script) will run indefinitively, and if a worker exits/dies, it will respawn it.

Then another script injects a SMS into the queue:

	<?php
	// File "inject.php"
	use gateway\workers\queue\SmsRequest;
	require_once 'autoload.php';
	
	$queue = msg_get_queue(ftok(realpath('autoload.php'),'S'));
	
	$smsrequest = new SmsRequest('H€llo world', array(4512345678), 'SMPP Test', 1337);
	msg_send($queue, SmsRequest::TYPE, $smsrequest, true); 

