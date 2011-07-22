PHP-based SMPP client lib
=============

This is a simplified SMPP client lib for sending or receiving smses through [SMPP v3.4](http://www.smsforum.net/SMPP_v3_4_Issue1_2.zip).

In addition to the client, this lib also contains an encoder for converting UTF-8 text to the GSM 03.38 encoding, and a socket wrapper. The socket wrapper provides connection pool, IPv6 and timeout monitoring features on top of PHP's socket extension.

This lib has changed significantly from it's first release, which required namespaces and included some worker components. You'll find that release at https://github.com/onlinecity/php-smpp/tree/1.0.1-namespaced.


Basic usage example
-----

To send a SMS you can do:

``` php
<?php
require_once 'smppclient.class.php';
require_once 'gsmencoder.class.php';
require_once 'sockettransport.class.php';

// Construct transport and client
$transport = new SocketTransport(array('smpp.provider.com'),3600);
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
//SmppClient::$sms_registered_delivery_flag = SMPP::REG_DELIVERY_SMSC_BOTH;

// Prepare message
$message = 'H€llo world';
$encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
$from = new SmppAddress('SMPP Test',SMPP::TON_ALPHANUMERIC);
$to = new SmppAddress(4512345678,SMPP::TON_INTERNATIONAL,SMPP::NPI_E164);

// Send
$smpp->sendSMS($from,$to,$encodedMessage,$tags);

// Close connection
$smpp->close();
```

To receive a SMS (or delivery receipt):

``` php
<?php
require_once 'smppclient.class.php';
require_once 'sockettransport.class.php';

// Construct transport and client
$transport = new SocketTransport(array('smpp.provider.com'),3600);
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


Connection pools
-----
You can specify a list of connections to have the SocketTransport attempt each one in succession or randomly. Also if you give it a hostname with multiple A/AAAA-records it will try each one.
If you want to monitor the DNS lookups, set defaultDebug to true before constructing the transport.

The (configurable) send timeout governs how long it will wait for each server to timeout. It can take a long time to try a long list of servers, depending on the timeout. You can change the timeout both before and after the connection attempts are made.

The transport supports IPv6 and will prefer IPv6 addresses over IPv4 when available. You can modify this feature by setting forceIpv6 or forceIpv4 to force it to only use IPv6 or IPv4.

In addition to the DNS lookups, it will also look for local IPv4 addresses using gethostbyname(), so "localhost" works for IPv4. For IPv6 localhost specify "::1". 


Implementation notes
-----

 - You can't connect as a transceiver, otherwise supported by SMPP v.3.4
 - The SUBMIT_MULTI operation of SMPP, which sends a SMS to a list of recipients, is not supported atm. You can easily add it though.
 - The sockets will return false if the timeout is reached on read() (but not readAll or write). 
   You can use this feature to implement an enquire_link policy. If you need to send enquire_link for every 30 seconds of inactivity, 
   set a timeout of 30 seconds, and send the enquire_link command after readSMS() returns false.
 - The examples above assume that the SMSC default datacoding is [GSM 03.38](http://en.wikipedia.org/wiki/GSM_03.38).
 - Remember to activate registered delivery if you want delivery receipts (set to SMPP::REG_DELIVERY_SMSC_BOTH / 0x01).
 - Both the SmppClient and transport components support a debug callback, which defaults to error_log. Use this to redirect debug information.