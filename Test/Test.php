<?php

namespace Phpsmpp\Test;

require_once __DIR__.'/../vendor/autoload.php';

use Phpsmpp\Protocol\GsmEncoder;
use Phpsmpp\Protocol\SmppAddress;
use Phpsmpp\Protocol\SmppClient;
use Phpsmpp\Protocol\SMPP;
use Phpsmpp\Transport\TSocket;

/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 13/10/2016
 * Time: 17:06
 */
//$GLOBALS['SMPP_ROOT'] = dirname(__FILE__); // assumes this file is in the root
//require_once $GLOBALS['SMPP_ROOT'].'/Protocol/smppclient.class.php';
//require_once $GLOBALS['SMPP_ROOT'].'/Transport/tsocket.class.php';

// Construct Transport and client
$transport = new TSocket('SMSCserver',8000);
$transport->setDebug(true);
$transport->setRecvTimeout(3000000);
$transport->setSendTimeout(3000000);
$smpp = new SmppClient($transport);

// Activate binary hex-output of server interaction
$smpp->debug = true;

try {
// Open the connection
//$Transport->setDebug(true);
    $transport->open();
    //$smpp->bindReceiver("login", "password");
    $smpp->bindTransmitter("login", "password");

// Read SMS and output
    $sms = $smpp->readSMS();
    echo "SMS:\n";
    var_dump($sms);
    sendSMS($smpp);
}
finally {
    // Close connection
    $smpp->close();
}

function sendSMS(SmppClient $smpp) {
    $message = 'Hello';
    $encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
    $from = new SmppAddress("+123456890");
    $to = new SmppAddress("+1234567890");

    // Send
    $smpp->sendSMS($from,$to,$encodedMessage);
}