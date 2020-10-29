<?php


namespace smpp;

use smpp\exceptions\SmppException;
use smpp\transport\Socket;

/**
 * Class for receiving or sending sms through SMPP protocol.
 * This is a reduced implementation of the SMPP protocol, and as such not all features will or ought to be available.
 * The purpose is to create a lightweight and simplified SMPP client.
 *
 * @author hd@onlinecity.dk, paladin
 * @see http://en.wikipedia.org/wiki/Short_message_peer-to-peer_protocol - SMPP 3.4 protocol specification
 * Derived from work done by paladin, see: http://sourceforge.net/projects/phpsmppapi/
 *
 * Copyright (C) 2011 OnlineCity
 * Copyright (C) 2006 Paladin
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * This license can be read at: http://www.opensource.org/licenses/lgpl-2.1.php
 */
class Client
{
    const MODE_TRANSMITTER   = 'transmitter';
    const MODE_TRANSCEIVER   = 'transceiver';
    const MODE_RECEIVER      = 'receiver';

    // SMPP bind parameters
    public static $systemType           = "WWW";
    public static $interfaceVersion     = 0x34;
    public static $addrTon              = 0;
    public static $addrNPI              = 0;
    public static $addressRange         = "";

    // ESME transmitter parameters
    public static $smsServiceType               = "";
    public static $smsEsmClass                  = 0x00;
    public static $smsProtocolID                = 0x00;
    public static $smsPriorityFlag              = 0x00;
    public static $smsRegisteredDeliveryFlag    = 0x00;
    public static $smsReplaceIfPresentFlag      = 0x00;
    public static $smsSmDefaultMessageID        = 0x00;

    /**
     * SMPP v3.4 says octect string are "not necessarily NULL terminated".
     * Switch to toggle this feature
     * @var boolean
     *
     * set NULL teminate octetstrings FALSE as default
     */
    public static $smsNullTerminateOctetstrings = false;

    /**
     * Use sar_msg_ref_num and sar_total_segments with 16 bit tags
     * @var integer
     */
    const CSMS_16BIT_TAGS = 0;

    /**
     * Use message payload for CSMS
     * @var integer
     */
    const CSMS_PAYLOAD = 1;

    /**
     * Embed a UDH in the message with 8-bit reference.
     * @var integer
     */
    const CSMS_8BIT_UDH = 2;

    public static $csmsMethod = self::CSMS_16BIT_TAGS;

    public $debug;

    protected $pduQueue;

    protected $transport;
    protected $debugHandler;

    // Used for reconnect
    protected $mode;
    private $login;
    private $pass;

    protected $sequenceNumber;
    protected $sarMessageReferenceNumber;

    /**
     * Construct the SMPP class
     *
     * @param Socket $transport
     * @param string $debugHandler
     */
    public function __construct(Socket $transport, $debugHandler=null)
    {
        // Internal parameters
        $this->sequenceNumber=1;
        $this->debug=false;
        $this->pduQueue=array();

        $this->transport = $transport;
        $this->debugHandler = $debugHandler ? $debugHandler : 'error_log';
        $this->mode = null;
    }

    /**
     * Binds the receiver. One object can be bound only as receiver or only as trancmitter.
     * @param string $login - ESME system_id
     * @param string $pass - ESME password
     * @return bool
     * @throws SmppException
     */
    public function bindReceiver($login, $pass)
    {
        if (!$this->transport->isOpen()) return false;
        if($this->debug) call_user_func($this->debugHandler, 'Binding receiver...');

        $response = $this->bind($login, $pass, SMPP::BIND_RECEIVER);

        if($this->debug){
            call_user_func($this->debugHandler, "Binding status  : ".$response->status);
        }
        $this->mode = self::MODE_RECEIVER;
        $this->login = $login;
        $this->pass = $pass;
    }

    /**
     * Binds the transmitter. One object can be bound only as receiver or only as trancmitter.
     * @param string $login - ESME system_id
     * @param string $pass - ESME password
     * @return bool
     * @throws SmppException
     */
    public function bindTransmitter($login, $pass)
    {
        if (!$this->transport->isOpen()){
            return false;
        }

        if($this->debug){
            call_user_func($this->debugHandler, 'Binding transmitter...');
        }

        $response = $this->bind($login, $pass, SMPP::BIND_TRANSMITTER);

        if($this->debug){
            call_user_func($this->debugHandler, "Binding status  : ".$response->status);
        }
        $this->mode = self::MODE_TRANSMITTER;
        $this->login = $login;
        $this->pass = $pass;
    }

    /**
     * @param $login
     * @param $pass
     * @return bool
     */
    public function bindTransceiver($login, $pass)
    {
        if (!$this->transport->isOpen()){
            return false;
        }

        $response = $this->bind($login, $pass, SMPP::BIND_TRANSCEIVER);

        if($this->debug){
            call_user_func($this->debugHandler, "Binding status  : ".$response->status);
        }
        $this->mode = self::MODE_TRANSCEIVER;
        $this->login = $login;
        $this->pass = $pass;
    }

    /**
     * Closes the session on the SMSC server.
     */
    public function close()
    {
        if (!$this->transport->isOpen()) return;
        if($this->debug) call_user_func($this->debugHandler, 'Unbinding...');

        $response=$this->sendCommand(SMPP::UNBIND,"");

        if($this->debug) call_user_func($this->debugHandler, "Unbind status   : ".$response->status);
        $this->transport->close();
    }

    /**
     * Parse a timestring as formatted by SMPP v3.4 section 7.1.
     * Returns an unix timestamp if $newDates is false or DateTime/DateInterval is missing,
     * otherwise an object of either DateTime or DateInterval is returned.
     *
     * @param string $input
     * @param boolean $newDates
     * @return mixed
     * @throws \Exception
     */
    public function parseSmppTime($input, $newDates=true)
    {
        // Check for support for new date classes
        if (!class_exists('DateTime') || !class_exists('DateInterval')) $newDates = false;

        $numMatch = preg_match('/^(\\d{2})(\\d{2})(\\d{2})(\\d{2})(\\d{2})(\\d{2})(\\d{1})(\\d{2})([R+-])$/',$input,$matches);
        if (!$numMatch) return null;
        list($whole, $y, $m, $d, $h, $i, $s, $t, $n, $p) = $matches;

        // Use strtotime to convert relative time into a unix timestamp
        if ($p == 'R') {
            if ($newDates) {
                $spec = "P";
                if ($y) $spec .= $y.'Y';
                if ($m) $spec .= $m.'M';
                if ($d) $spec .= $d.'D';
                if ($h || $i || $s) $spec .= 'T';
                if ($h) $spec .= $h.'H';
                if ($i) $spec .= $i.'M';
                if ($s) $spec .= $s.'S';
                return new \DateInterval($spec);
            } else {
                return strtotime("+$y year +$m month +$d day +$h hour +$i minute $s +second");
            }
        } else {
            $offsetHours = floor($n/4);
            $offsetMinutes = ($n % 4)*15;
            $time = sprintf("20%02s-%02s-%02sT%02s:%02s:%02s%s%02s:%02s",$y,$m,$d,$h,$i,$s,$p,$offsetHours,$offsetMinutes); // Not Y3K safe
            if ($newDates) {
                return new \DateTime($time);
            } else {
                return strtotime($time);
            }
        }
    }

    /**
     * Query the SMSC about current state/status of a previous sent SMS.
     * You must specify the SMSC assigned message id and source of the sent SMS.
     * Returns an associative array with elements: message_id, final_date, message_state and error_code.
     *    message_state would be one of the SMPP::STATE_* constants. (SMPP v3.4 section 5.2.28)
     *    error_code depends on the telco network, so could be anything.
     *
     * @param string $messageID
     * @param Address $source
     * @return array
     * @throws \Exception
     */
    public function queryStatus($messageID, Address $source)
    {
        $pduBody = pack(
            'a'.(strlen($messageID)+1).'cca'.(strlen($source->value)+1),
            $messageID,
            $source->ton,
            $source->npi,
            $source->value
        );
        $reply = $this->sendCommand(SMPP::QUERY_SM, $pduBody);
        if (!$reply || $reply->status != SMPP::ESME_ROK) return null;

        // Parse reply
        $posID = strpos($reply->body,"\0",0);
        $posDate = strpos($reply->body,"\0",$posID+1);
        $data = array();
        $data['message_id'] = substr($reply->body, 0, $posID);
        $data['final_date'] = substr($reply->body, $posID,$posDate-$posID);
        $data['final_date'] = $data['final_date'] ? $this->parseSmppTime(trim($data['final_date'])) : null;
        $status = unpack("cmessage_state/cerror_code", substr($reply->body,$posDate+1));
        return array_merge($data,$status);
    }

    /**
     * Read one SMS from SMSC. Can be executed only after bindReceiver() call.
     * This method bloks. Method returns on socket timeout or enquire_link signal from SMSC.
     * @return DeliveryReceipt|Sms|bool
     */
    public function readSMS()
    {
        $commandID=SMPP::DELIVER_SM;
        // Check the queue
        $queueLength = count($this->pduQueue);
        for($i=0;$i<$queueLength;$i++) {
            $pdu=$this->pduQueue[$i];
            if($pdu->id==$commandID) {
                //remove response
                array_splice($this->pduQueue, $i, 1);
                return $this->parseSMS($pdu);
            }
        }
        // Read pdu
        do{
            $pdu = $this->readPDU();
            if ($pdu === false) {
                return false;
            } // TSocket v. 0.6.0+ returns false on timeout
            //check for enquire link command
            if($pdu->id==SMPP::ENQUIRE_LINK) {
                $response = new Pdu(SMPP::ENQUIRE_LINK_RESP, SMPP::ESME_ROK, $pdu->sequence, "\x00");
                $this->sendPDU($response);
            } else if ($pdu->id!=$commandID) { // if this is not the correct PDU add to queue
                array_push($this->pduQueue, $pdu);
            }
        } while($pdu && $pdu->id!=$commandID);

        if($pdu) {
            return $this->parseSMS($pdu);
        }
        return false;
    }

    /**
     * Send one SMS to SMSC. Can be executed only after bindTransmitter() call.
     * $message is always in octets regardless of the data encoding.
     * For correct handling of Concatenated SMS, message must be encoded with GSM 03.38 (data_coding 0x00) or UCS-2BE (0x08).
     * Concatenated SMS'es uses 16-bit reference numbers, which gives 152 GSM 03.38 chars or 66 UCS-2BE chars per CSMS.
     * If we are using 8-bit ref numbers in the UDH for CSMS it's 153 GSM 03.38 chars
     *
     * @param Address $from
     * @param Address $to
     * @param string $message
     * @param array $tags (optional)
     * @param integer $dataCoding (optional)
     * @param integer $priority (optional)
     * @param string $scheduleDeliveryTime (optional)
     * @param string $validityPeriod (optional)
     * @return string message id
     */
    public function sendSMS(
        Address $from,
        Address $to,
        $message,
        $tags=null,
        $dataCoding=SMPP::DATA_CODING_DEFAULT,
        $priority=0x00,
        $scheduleDeliveryTime=null,
        $validityPeriod=null
    )
    {
        $messageLength = strlen($message);

        if ($messageLength>160 && $dataCoding != SMPP::DATA_CODING_UCS2 && $dataCoding != SMPP::DATA_CODING_DEFAULT) {
            return false;
        }

        switch ($dataCoding) {
            case SMPP::DATA_CODING_UCS2:
                // in octets, 70 UCS-2 chars
                $singleSmsOctetLimit = 140;
                // There are 133 octets available, but this would split the UCS the middle so use 132 instead
                $csmsSplit = 132;
                $message = mb_convert_encoding($message, 'UCS-2');
                //Update message length with current encoding
                $messageLength = mb_strlen($message);
                break;
            case SMPP::DATA_CODING_DEFAULT:
                // we send data in octets, but GSM 03.38 will be packed in septets (7-bit) by SMSC.
                $singleSmsOctetLimit = 160;
                // send 152/153 chars in each SMS (SMSC will format data)
                $csmsSplit = (self::$csmsMethod == self::CSMS_8BIT_UDH) ? 153 : 152;
                break;
            default:
                $singleSmsOctetLimit = 254; // From SMPP standard
                break;
        }

        // Figure out if we need to do CSMS, since it will affect our PDU
        if ($messageLength > $singleSmsOctetLimit) {
            $doCsms = true;
            if (self::$csmsMethod != self::CSMS_PAYLOAD) {
                $parts = $this->splitMessageString($message, $csmsSplit, $dataCoding);
                $short_message = reset($parts);
                $csmsReference = $this->getCsmsReference();
            }
        } else {
            $short_message = $message;
            $doCsms = false;
        }

        // Deal with CSMS
        if ($doCsms) {
            if (self::$csmsMethod == self::CSMS_PAYLOAD) {
                $payload = new Tag(Tag::MESSAGE_PAYLOAD, $message, $messageLength);
                return $this->submit_sm(
                    $from,
                    $to,
                    null,
                    (empty($tags) ? array($payload) : array_merge($tags,$payload)),
                    $dataCoding,
                    $priority,
                    $scheduleDeliveryTime,
                    $validityPeriod
                );
            } else if (self::$csmsMethod == self::CSMS_8BIT_UDH) {
                $seqnum = 1;
                foreach ($parts as $part) {
                    $udh = pack('cccccc',5,0,3,substr($csmsReference,1,1),count($parts),$seqnum);
                    $res = $this->submit_sm(
                        $from,
                        $to,
                        $udh.$part,
                        $tags,
                        $dataCoding,
                        $priority,
                        $scheduleDeliveryTime,
                        $validityPeriod,
                        (self::$smsEsmClass|0x40)
                    );
                    $seqnum++;
                }
                return $res;
            } else {
                $sar_msg_ref_num = new Tag(Tag::SAR_MSG_REF_NUM, $csmsReference, 2, 'n');
                $sar_total_segments = new Tag(Tag::SAR_TOTAL_SEGMENTS, count($parts), 1, 'c');
                $seqnum = 1;
                foreach ($parts as $part) {
                    $sartags = [$sar_msg_ref_num, $sar_total_segments, new Tag(Tag::SAR_SEGMENT_SEQNUM, $seqnum, 1, 'c')];
                    $res = $this->submit_sm($from, $to, $part, (empty($tags) ? $sartags : array_merge($tags,$sartags)), $dataCoding, $priority, $scheduleDeliveryTime, $validityPeriod);
                    $seqnum++;
                }
                return $res;
            }
        }

        return $this->submit_sm($from, $to, $short_message, $tags, $dataCoding);
    }

    /**
     * Perform the actual submit_sm call to send SMS.
     * Implemented as a protected method to allow automatic sms concatenation.
     * Tags must be an array of already packed and encoded TLV-params.
     *
     * @param Address $source
     * @param Address $destination
     * @param string $short_message
     * @param array $tags
     * @param integer $dataCoding
     * @param integer $priority
     * @param string $scheduleDeliveryTime
     * @param string $validityPeriod
     * @param string $esmClass
     * @return string message id
     */
    protected function submit_sm(
        Address $source,
        Address $destination,
        $short_message=null,
        $tags=null,
        $dataCoding=SMPP::DATA_CODING_DEFAULT,
        $priority=0x00,
        $scheduleDeliveryTime=null,
        $validityPeriod=null,
        $esmClass=null
    )
    {
        if (is_null($esmClass)) $esmClass = self::$smsEsmClass;

        // Construct PDU with mandatory fields
        $pdu = pack(
            'a1cca'.(strlen($source->value)+1)
            .'cca'.(strlen($destination->value)+1)
            .'ccc'.($scheduleDeliveryTime ? 'a16x' : 'a1').($validityPeriod ? 'a16x' : 'a1')
            .'ccccca'.(strlen($short_message)+(self::$smsNullTerminateOctetstrings ? 1 : 0)),
            self::$smsServiceType,
            $source->ton,
            $source->npi,
            $source->value,
            $destination->ton,
            $destination->npi,
            $destination->value,
            $esmClass,
            self::$smsProtocolID,
            $priority,
            $scheduleDeliveryTime,
            $validityPeriod,
            self::$smsRegisteredDeliveryFlag,
            self::$smsReplaceIfPresentFlag,
            $dataCoding,
            self::$smsSmDefaultMessageID,
            strlen($short_message),//sm_length
            $short_message//short_message
        );

        // Add any tags
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $pdu .= $tag->getBinary();
            }
        }

        $response=$this->sendCommand(SMPP::SUBMIT_SM,$pdu);
        $body = unpack("a*msgid",$response->body);
        return $body['msgid'];
    }

    /**
     * Get a CSMS reference number for sar_msg_ref_num.
     * Initializes with a random value, and then returns the number in sequence with each call.
     */
    protected function getCsmsReference()
    {
        $limit = (self::$csmsMethod == self::CSMS_8BIT_UDH) ? 255 : 65535;
        if (!isset($this->sarMessageReferenceNumber)) {
            $this->sarMessageReferenceNumber = mt_rand(0,$limit);
        }
        $this->sarMessageReferenceNumber++;

        if ($this->sarMessageReferenceNumber>$limit) {
            $this->sarMessageReferenceNumber = 0;
        }
        return $this->sarMessageReferenceNumber;
    }


    /**
     * Split a message into multiple parts, taking the encoding into account.
     * A character represented by an GSM 03.38 escape-sequence shall not be split in the middle.
     * Uses str_split if at all possible, and will examine all split points for escape chars if it's required.
     *
     * @param string $message
     * @param integer $split
     * @param integer $dataCoding (optional)
     * @return array
     */
    protected function splitMessageString($message, $split, $dataCoding=SMPP::DATA_CODING_DEFAULT)
    {
        switch ($dataCoding) {
            case SMPP::DATA_CODING_DEFAULT:
                $msg_length = strlen($message);
                // Do we need to do php based split?
                $numParts = floor($msg_length / $split);
                if ($msg_length % $split == 0) $numParts--;
                $slowSplit = false;

                for($i=1;$i<=$numParts;$i++) {
                    if ($message[$i*$split-1] == "\x1B") {
                        $slowSplit = true;
                        break;
                    };
                }
                if (!$slowSplit) return str_split($message,$split);

                // Split the message char-by-char
                $parts = array();
                $part = null;
                $n = 0;
                for($i=0;$i<$msg_length;$i++) {
                    $c = $message[$i];
                    // reset on $split or if last char is a GSM 03.38 escape char
                    if ($n==$split || ($n==($split-1) && $c=="\x1B")) {
                        $parts[] = $part;
                        $n = 0;
                        $part = null;
                    }
                    $part .= $c;
                }
                $parts[] = $part;
                return $parts;
            case SMPP::DATA_CODING_UCS2: // UCS2-BE can just use str_split since we send 132 octets per message, which gives a fine split using UCS2
            default:
                return str_split($message,$split);
        }
    }

    /**
     * Binds the socket and opens the session on SMSC
     * @param string $login - ESME system_id
     * @param $pass
     * @param $commandID
     * @return bool|Pdu
     * @throws \Exception
     */
    protected function bind($login, $pass, $commandID)
    {
        // Make PDU body
        $pduBody = pack(
            'a'.(strlen($login)+1).
            'a'.(strlen($pass)+1).
            'a'.(strlen(self::$systemType)+1).
            'CCCa'.(strlen(self::$addressRange)+1),
            $login, $pass,
            self::$systemType,
            self::$interfaceVersion,
            self::$addrTon,
            self::$addrNPI,
            self::$addressRange
        );

        $response=$this->sendCommand($commandID, $pduBody);
        if ($response->status != SMPP::ESME_ROK){
            throw new SmppException(SMPP::getStatusMessage($response->status), $response->status);
        }

        return $response;
    }

    /**
     * Parse received PDU from SMSC.
     * @param Pdu $pdu - received PDU from SMSC.
     * @return DeliveryReceipt|Sms parsed PDU as array.
     */
    protected function parseSMS(Pdu $pdu)
    {
        // Check command id
        if($pdu->id != SMPP::DELIVER_SM) throw new \InvalidArgumentException('PDU is not an received SMS');

        // Unpack PDU
        $ar=unpack("C*",$pdu->body);

        // Read mandatory params
        $serviceType = $this->getString($ar,6,true);

        //
        $sourceAddrTon  = next($ar);
        $sourceAddrNPI  = next($ar);
        $sourceAddr     = $this->getString($ar,21);
        $source         = new Address($sourceAddr, $sourceAddrTon, $sourceAddrNPI);

        //
        $destinationAddrTon = next($ar);
        $destinationAddrNPI = next($ar);
        $destinationAddr    = $this->getString($ar,21);
        $destination        = new Address($destinationAddr, $destinationAddrTon, $destinationAddrNPI);

        $esmClass = next($ar);
        $protocolId = next($ar);
        $priorityFlag = next($ar);
        next($ar); // schedule_delivery_time
        next($ar); // validity_period
        $registeredDelivery = next($ar);
        next($ar); // replace_if_present_flag
        $dataCoding = next($ar);
        next($ar); // sm_default_msg_id
        $sm_length = next($ar);
        $message = $this->getString($ar,$sm_length);

        // Check for optional params, and parse them
        if (current($ar) !== false) {
            $tags = array();
            do {
                $tag = $this->parseTag($ar);
                if ($tag !== false) $tags[] = $tag;
            } while (current($ar) !== false);
        } else {
            $tags = null;
        }

        if (($esmClass & SMPP::ESM_DELIVER_SMSC_RECEIPT) != 0) {
            $sms = new DeliveryReceipt(
                $pdu->id,
                $pdu->status,
                $pdu->sequence,
                $pdu->body,
                $serviceType,
                $source,
                $destination,
                $esmClass,
                $protocolId,
                $priorityFlag,
                $registeredDelivery,
                $dataCoding,
                $message,
                $tags
            );
            $sms->parseDeliveryReceipt();
        } else {
            $sms = new Sms(
                $pdu->id,
                $pdu->status,
                $pdu->sequence,
                $pdu->body,
                $serviceType,
                $source,
                $destination,
                $esmClass,
                $protocolId,
                $priorityFlag,
                $registeredDelivery,
                $dataCoding,
                $message,
                $tags
            );
        }

        if($this->debug) {
            call_user_func($this->debugHandler, "Received sms:\n" . print_r($sms,true));
        }

        // Send response of recieving sms
        $response = new Pdu(SMPP::DELIVER_SM_RESP, SMPP::ESME_ROK, $pdu->sequence, "\x00");
        $this->sendPDU($response);
        return $sms;
    }

    /**
     * Send the enquire link command.
     * @return Pdu
     * @throws \Exception
     */
    public function enquireLink()
    {
        return $this->sendCommand(SMPP::ENQUIRE_LINK, null);
    }

    /**
     * Respond to any enquire link we might have waiting.
     * If will check the queue first and respond to any enquire links we have there.
     * Then it will move on to the transport, and if the first PDU is enquire link respond,
     * otherwise add it to the queue and return.
     *
     */
    public function respondEnquireLink()
    {
        // Check the queue first
        $queueLength = count($this->pduQueue);
        for($i=0; $i<$queueLength; $i++) {
            $pdu=$this->pduQueue[$i];
            if($pdu->id==SMPP::ENQUIRE_LINK) {
                //remove response
                array_splice($this->pduQueue, $i, 1);
                $this->sendPDU(new Pdu(SMPP::ENQUIRE_LINK_RESP, SMPP::ESME_ROK, $pdu->sequence, "\x00"));
            }
        }

        // Check the transport for data
        if ($this->transport->hasData()) {
            $pdu = $this->readPDU();
            if($pdu->id==SMPP::ENQUIRE_LINK) {
                $this->sendPDU(new Pdu(SMPP::ENQUIRE_LINK_RESP, SMPP::ESME_ROK, $pdu->sequence, "\x00"));
            } else if ($pdu) {
                array_push($this->pduQueue, $pdu);
            }
        }
    }

    /**
     * Reconnect to SMSC.
     * This is mostly to deal with the situation were we run out of sequence numbers
     * @throws \Exception
     */
    protected function reconnect()
    {
        $this->close();
        sleep(1);
        $this->transport->open();
        $this->sequenceNumber = 1;

        switch($this->mode){
            case self::MODE_TRANSMITTER:{
                $this->bindTransmitter($this->login, $this->pass);
                break;
            }
            case self::MODE_RECEIVER:{
                $this->bindReceiver($this->login, $this->pass);
                break;
            }
            case self::MODE_TRANSCEIVER:{
                $this->bindTransceiver($this->login, $this->pass);
                break;
            }
            default:
                throw new \Exception('Invalid mode: ' . $this->mode);
        }
    }

    /**
     * Sends the PDU command to the SMSC and waits for response.
     * @param integer $id - command ID
     * @param string $pduBody - PDU body
     * @return bool|Pdu
     * @throws \Exception
     */
    protected function sendCommand($id, $pduBody)
    {
        if (!$this->transport->isOpen()) {
            return false;
        }
        $pdu = new Pdu($id, 0, $this->sequenceNumber, $pduBody);
        $this->sendPDU($pdu);
        $response = $this->readPduResponse($this->sequenceNumber, $pdu->id);

        if ($response === false){
            throw new SmppException('Failed to read reply to command: 0x'.dechex($id));
        }

        if ($response->status != SMPP::ESME_ROK){
            throw new SmppException(SMPP::getStatusMessage($response->status), $response->status);
        }

        $this->sequenceNumber++;

        // Reached max sequence number, spec does not state what happens now, so we re-connect
        if ($this->sequenceNumber >= 0x7FFFFFFF) {
            $this->reconnect();
        }

        return $response;
    }

    /**
     * Prepares and sends PDU to SMSC.
     * @param Pdu $pdu
     */
    protected function sendPDU(Pdu $pdu)
    {
        $length = strlen($pdu->body) + 16;
        $header = pack("NNNN", $length, $pdu->id, $pdu->status, $pdu->sequence);
        if($this->debug) {
            call_user_func($this->debugHandler, "Send PDU         : $length bytes");
            call_user_func($this->debugHandler, ' '.chunk_split(bin2hex($header.$pdu->body),2," "));
            call_user_func($this->debugHandler, ' command_id      : 0x'.dechex($pdu->id));
            call_user_func($this->debugHandler, ' sequence number : '.$pdu->sequence);
        }
        $this->transport->write($header.$pdu->body,$length);
    }

    /**
     * Waits for SMSC response on specific PDU.
     * If a GENERIC_NACK with a matching sequence number, or null sequence is received instead it's also accepted.
     * Some SMPP servers, ie. logica returns GENERIC_NACK on errors.
     *
     * @param integer $sequenceNumber - PDU sequence number
     * @param integer $commandID - PDU command ID
     * @return Pdu|bool
     * @throws SmppException
     */
    protected function readPduResponse($sequenceNumber, $commandID)
    {
        // Get response cmd id from command ID
        $commandID = $commandID | SMPP::GENERIC_NACK;

        // Check the queue first
        $queueLength = count($this->pduQueue);
        for($i=0; $i < $queueLength; $i++) {
            $pdu = $this->pduQueue[$i];
            if (
                ($pdu->sequence == $sequenceNumber && ($pdu->id == $commandID || $pdu->id == SMPP::GENERIC_NACK))
                ||
                ($pdu->sequence == null && $pdu->id == SMPP::GENERIC_NACK)
            ) {
                // remove response pdu from queue
                array_splice($this->pduQueue, $i, 1);
                return $pdu;
            }
        }

        // Read PDUs until the one we are looking for shows up, or a generic nack pdu with matching sequence or null sequence
        do{
            $pdu=$this->readPDU();
            if ($pdu) {
                if (
                    $pdu->sequence == $sequenceNumber
                    && ($pdu->id == $commandID || $pdu->id == SMPP::GENERIC_NACK)
                ){
                    return $pdu;
                }
                if ($pdu->sequence == null && $pdu->id == SMPP::GENERIC_NACK){
                    return $pdu;
                }
                array_push($this->pduQueue, $pdu); // unknown PDU push to queue
            }
        } while($pdu);
        return false;
    }

    /**
     * Reads incoming PDU from SMSC.
     * @return bool|Pdu
     */
    protected function readPDU()
    {
        // Read PDU length
        $bufLength = $this->transport->read(4);
        if(!$bufLength){
            return false;
        }

        /**
         * extraction define next variables:
         * @var $length
         * @var $command_id
         * @var $command_status
         * @var $sequence_number
         */
        extract(unpack("Nlength", $bufLength));

        // Read PDU headers
        $bufHeaders = $this->transport->read(12);
        if(!$bufHeaders){
            return false;
        }
        extract(unpack("Ncommand_id/Ncommand_status/Nsequence_number", $bufHeaders));

        // Read PDU body
        if($length-16>0){
            $body=$this->transport->readAll($length-16);
            if(!$body){
                throw new \RuntimeException('Could not read PDU body');
            }
        } else {
            $body=null;
        }

        if($this->debug) {
            call_user_func($this->debugHandler, "Read PDU         : $length bytes");
            call_user_func($this->debugHandler, ' '.chunk_split(bin2hex($bufLength.$bufHeaders.$body),2," "));
            call_user_func($this->debugHandler, " command id      : 0x".dechex($command_id));
            call_user_func($this->debugHandler, " command status  : 0x".dechex($command_status)." ".SMPP::getStatusMessage($command_status));
            call_user_func($this->debugHandler, ' sequence number : '.$sequence_number);
        }
        return new Pdu($command_id, $command_status, $sequence_number, $body);
    }

    /**
     * Reads C style null padded string from the char array.
     * Reads until $maxlen or null byte.
     *
     * @param array $ar - input array
     * @param integer $maxLength - maximum length to read.
     * @param boolean $firstRead - is this the first bytes read from array?
     * @return string.
     */
    protected function getString(&$ar, $maxLength = 255, $firstRead=false)
    {
        $s="";
        $i=0;
        do{
            $c = ($firstRead && $i==0) ? current($ar) : next($ar);
            if ($c != 0) $s .= chr($c);
            $i++;
        } while($i<$maxLength && $c !=0);
        return $s;
    }

    /**
     * Read a specific number of octets from the char array.
     * Does not stop at null byte
     *
     * @param array $ar - input array
     * @param int $length
     * @return string
     */
    protected function getOctets(&$ar,$length)
    {
        $s = "";
        for($i=0; $i < $length; $i++) {
            $c = next($ar);
            if ($c === false){
                return $s;
            }
            $s .= chr($c);
        }
        return $s;
    }

    protected function parseTag(&$ar)
    {
        $unpackedData = unpack(
            'nid/nlength',
            pack("C2C2",next($ar),next($ar),next($ar),next($ar))
        );

        if (!$unpackedData){
            throw new \InvalidArgumentException('Could not read tag data');
        }
        /**
         * Extraction create variables:
         * @var $length
         * @var $id
         */
        extract($unpackedData);

        // Sometimes SMSC return an extra null byte at the end
        if ($length==0 && $id == 0) {
            return false;
        }

        $value = $this->getOctets($ar,$length);
        $tag = new Tag($id, $value, $length);
        if ($this->debug) {
            call_user_func($this->debugHandler, "Parsed tag:");
            call_user_func($this->debugHandler, " id     :0x" . dechex($tag->id));
            call_user_func($this->debugHandler, " length :" . $tag->length);
            call_user_func($this->debugHandler, " value  :" . chunk_split(bin2hex($tag->value),2," "));
        }
        return $tag;
    }
}