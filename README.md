PHP-based SMPP client lib
=============

This is a simplified SMPP client lib for sending or receiving smses through [SMPP v3.4](http://www.smsforum.net/SMPP_v3_4_Issue1_2.zip).

The socket implementation from [Apache's Thrift](http://thrift.apache.org/) is used for the transport layer components. 

The library is divided into two parts:

 - protocol - containing everything related to SMPP
 - transport - the transport components from Apache's Thrift

Basic usage example
-----

To send a SMS you can do:

``` php
<?php
$GLOBALS['SMPP_ROOT'] = dirname(__FILE__); // assumes this file is in the root
require_once $GLOBALS['SMPP_ROOT'].'/protocol/smppclient.class.php';
require_once $GLOBALS['SMPP_ROOT'].'/protocol/gsmencoder.class.php';
require_once $GLOBALS['SMPP_ROOT'].'/transport/tsocket.class.php';

// Construct transport and client
$transport = new TSocket('your.smsc.com',2775);
$transport->setRecvTimeout(10000);
$smpp = new SmppClient($transport);

// Activate binary hex-output of server interaction
$smpp->debug = true;

// Open the connection
$transport->open();
$smpp->bindTransmitter("USERNAME","PASSWORD");

// Optional connection specific overrides
//SmppClient::$sms_null_terminate_octetstrings = false;
//SmppClient::$sms_use_msg_payload_for_csms = true;

// Prepare message
$message = 'H€llo world';
$encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
$from = new SmppAddress(GsmEncoder::utf8_to_gsm0338('SMPP Tést'),SMPP::TON_ALPHANUMERIC);
$to = new SmppAddress(4512345678,SMPP::TON_INTERNATIONAL,SMPP::NPI_E164);

// Send
$smpp->sendSMS($from,$to,$encodedMessage,$tags);

// Close connection
$smpp->close();
```

To receive a SMS (or delivery receipt):

``` php
<?php
$GLOBALS['SMPP_ROOT'] = dirname(__FILE__); // assumes this file is in the root
require_once $GLOBALS['SMPP_ROOT'].'/protocol/smppclient.class.php';
require_once $GLOBALS['SMPP_ROOT'].'/transport/tsocket.class.php';

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

// Close connection
$smpp->close();
```

Implementation notes
-----

 - You can't connect as a transceiver, otherwise supported by SMPP v.3.4
 - The SUBMIT_MULTI operation of SMPP, which sends a SMS to a list of recipients, is not supported atm. You can easily add it though.
 - The thrift sockets will return false if the timeout is reached (after version 0.6.0). 
   You can use this feature to implement an enquire_link policy. If you need to send enquire_link for every 30 seconds of inactivity, 
   set a timeout of 30 seconds, and send the enquire_link command if readSMS() returns false.
 - The examples above assume that the SMSC default datacoding is [GSM 03.38](http://en.wikipedia.org/wiki/GSM_03.38).
 - Remember to activate registered delivery if you want delivery receipts (set to SMPP::REG_DELIVERY_SMSC_BOTH / 0x01).
 - Both the SmppClient and transport components support a debug callback, which defaults to error_log. Use this to redirect debug information.