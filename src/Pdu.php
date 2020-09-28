<?php

/**
 * Primitive class for encapsulating PDUs
 * @author hd@onlinecity.dk
 */
namespace smpp;


class Pdu
{
    public $id;
    public $status;
    public $sequence;
    public $body;

    /**
     * Create new generic PDU object
     *
     * @param integer $id
     * @param integer $status
     * @param integer $sequence
     * @param string $body
     */
    public function __construct($id, $status, $sequence, $body)
    {
        $this->id = $id;
        $this->status = $status;
        $this->sequence = $sequence;
        $this->body = $body;
    }
}