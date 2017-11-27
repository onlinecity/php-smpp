<?php
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 26/10/2016
 * Time: 16:13
 */

namespace Phpsmpp\Protocol;


class SmppPduSubmitSmResp extends SmppPdu
{
    private $smscMsgId;

    public function __construct($id, $status, $sequence, $body, $tcpMessage, $smscMsgId) {
        parent::__construct($id, $status, $sequence, $body, $tcpMessage);

        $this->smscMsgId =$smscMsgId;// unpack("a*msgid",$this->body);
    }

    public function toString() {
        return parent::toString().", smscMsgId: [". $this->smscMsgId ."]";
    }

    /**
     * @return array
     */
    public function getSmscMsgId()
    {
        return $this->smscMsgId;
    }

    /**
     * @param array $smscMsgId
     */
    public function setSmscMsgId($smscMsgId)
    {
        $this->smscMsgId = $smscMsgId;
    }
}