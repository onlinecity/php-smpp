<?php


namespace smpp;

/**
 * Primitive class to represent SMPP optional params, also know as TLV (Tag-Length-Value) params
 * @author hd@onlinecity.dk
 */
class Tag
{
    public $id;
    public $length;
    public $value;
    public $type;

    const DEST_ADDR_SUBUNIT = 0x0005;
    const DEST_NETWORK_TYPE = 0x0006;
    const DEST_BEARER_TYPE = 0x0007;
    const DEST_TELEMATICS_ID = 0x0008;
    const SOURCE_ADDR_SUBUNIT = 0x000D;
    const SOURCE_NETWORK_TYPE = 0x000E;
    const SOURCE_BEARER_TYPE = 0x000F;
    const SOURCE_TELEMATICS_ID = 0x0010;
    const QOS_TIME_TO_LIVE = 0x0017;
    const PAYLOAD_TYPE = 0x0019;
    const ADDITIONAL_STATUS_INFO_TEXT = 0x001D;
    const RECEIPTED_MESSAGE_ID = 0x001E;
    const MS_MSG_WAIT_FACILITIES = 0x0030;
    const PRIVACY_INDICATOR = 0x0201;
    const SOURCE_SUBADDRESS = 0x0202;
    const DEST_SUBADDRESS = 0x0203;
    const USER_MESSAGE_REFERENCE = 0x0204;
    const USER_RESPONSE_CODE = 0x0205;
    const SOURCE_PORT = 0x020A;
    const DESTINATION_PORT = 0x020B;
    const SAR_MSG_REF_NUM = 0x020C;
    const LANGUAGE_INDICATOR = 0x020D;
    const SAR_TOTAL_SEGMENTS = 0x020E;
    const SAR_SEGMENT_SEQNUM = 0x020F;
    const SC_INTERFACE_VERSION = 0x0210;
    const CALLBACK_NUM_PRES_IND = 0x0302;
    const CALLBACK_NUM_ATAG = 0x0303;
    const NUMBER_OF_MESSAGES = 0x0304;
    const CALLBACK_NUM = 0x0381;
    const DPF_RESULT = 0x0420;
    const SET_DPF = 0x0421;
    const MS_AVAILABILITY_STATUS = 0x0422;
    const NETWORK_ERROR_CODE = 0x0423;
    const MESSAGE_PAYLOAD = 0x0424;
    const DELIVERY_FAILURE_REASON = 0x0425;
    const MORE_MESSAGES_TO_SEND = 0x0426;
    const MESSAGE_STATE = 0x0427;
    const USSD_SERVICE_OP = 0x0501;
    const DISPLAY_TIME = 0x1201;
    const SMS_SIGNAL = 0x1203;
    const MS_VALIDITY = 0x1204;
    const ALERT_ON_MESSAGE_DELIVERY = 0x130C;
    const ITS_REPLY_TYPE = 0x1380;
    const ITS_SESSION_INFO = 0x1383;


    /**
     * Construct a new TLV param.
     * The value must either be pre-packed with pack(), or a valid pack-type must be specified.
     *
     * @param integer $id
     * @param string $value
     * @param integer $length (optional)
     * @param string $type (optional)
     */
    public function __construct($id, $value, $length=null, $type='a*')
    {
        $this->id = $id;
        $this->value = $value;
        $this->length = $length;
        $this->type = $type;
    }

    /**
     * Get the TLV packed into a binary string for transport
     * @return string
     */
    public function getBinary()
    {
        return pack('nn'.$this->type, $this->id, ($this->length ? $this->length : strlen($this->value)), $this->value);
    }
}