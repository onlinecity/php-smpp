<?php
namespace Phpsmpp\Protocol;
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 14/10/2016
 * Time: 09:50
 */
/**
 * Primitive class for encapsulating PDUs
 * @author hd@onlinecity.dk
 */
class SmppPdu
{
    public $id;
    public $status;
    public $sequence;
    public $body;
    public $tcpMessage;

    /**
     * Create new generic PDU object
     *
     * @param integer $id
     * @param integer $status
     * @param integer $sequence
     * @param string $body
     * @param string $tcpMessage
     */
    public function __construct($id, $status, $sequence, $body, $isValid = true, $tcpMessage = null)
    {
        $this->id = $id;
        $this->status = $status;
        $this->sequence = $sequence;
        $this->body = $body;
        $this->isValid = $isValid;
        $this->tcpMessage = $tcpMessage;
    }

    public function isValid()
    {
        return SMPP::command_id_valid($this->id) && SMPP::status_code_valid($this->status) && isset($this->sequence);
    }
}