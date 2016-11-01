<?php
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 24/10/2016
 * Time: 15:47
 */

namespace Phpsmpp\Callback;


use Phpsmpp\Protocol\SmppDeliveryReceipt;
use Phpsmpp\Protocol\SmppPdu;
use Phpsmpp\Protocol\SmppSms;

interface SmsCallbackInterface
{
    function onBindReceiverSuccess();
    function onBindTransmitterSuccess();
    function onEnquireLinkReceived(SmppPdu $pdu);
    function onSmsReceived(SmppSms $sms);
    function onSmsDeliveryReceipt(SmppDeliveryReceipt $deliveryReceipt);
}