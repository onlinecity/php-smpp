<?php
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 24/10/2016
 * Time: 15:47
 */

namespace Phpsmpp\Callback;


use Phpsmpp\Protocol\SmppSms;

interface SmsCallbackInterface
{
    function onSmsReceived(SmppSms $sms);
    function onSmsDeliveryReceipt(SmppDeliveryReceipt $deliveryReceipt);
}