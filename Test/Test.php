<?php

namespace Phpsmpp\Test;

require_once __DIR__.'/../vendor/autoload.php';

use Phpsmpp\Protocol\SmppClient;
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
$transport = new TSocket('server',9999);
$transport->setRecvTimeout(60000); // for this example wait up to 60 seconds for data
$smpp = new SmppClient($transport);

// Activate binary hex-output of server interaction
$smpp->debug = true;

try {
// Open the connection
//$Transport->setDebug(true);
    $transport->open();
    $smpp->bindReceiver("login", "pwd");

// Read SMS and output
    $sms = $smpp->readSMS();
    echo "SMS:\n";
    var_dump($sms);
}
finally {
    // Close connection
    $smpp->close();
}