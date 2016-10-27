<?php
namespace Phpsmpp\Protocol;
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 14/10/2016
 * Time: 09:51
 */
use Phpsmpp\Protocol\Tags\SmppTag;

/**
 * Primitive type to represent SMSes
 * @author hd@onlinecity.dk
 */
class SmppSms extends SmppPdu
{
    public $service_type;
    public $source;
    public $destination;
    public $esmClass;
    public $protocolId;
    public $priorityFlag;
    public $registeredDelivery;
    public $dataCoding;
    public $message;
    /**
     * @var SmppTag[]
     */
    public $tags;

    //Used for multi-part SMS
    public $message_identifier;
    public $message_parts;
    public $message_part_number;

    // Unused in deliver_sm
    public $scheduleDeliveryTime;
    public $validityPeriod;
    public $smDefaultMsgId;
    public $replaceIfPresentFlag;

    /**
     * Construct a new SMS
     *
     * @param integer $id
     * @param integer $status
     * @param integer $sequence
     * @param string $body
     * @param string $service_type
     * @param Address $source
     * @param Address $destination
     * @param integer $esmClass
     * @param integer $protocolId
     * @param integer $priorityFlag
     * @param integer $registeredDelivery
     * @param integer $dataCoding
     * @param string $message
     * @param array $tags (optional)
     * @param string $scheduleDeliveryTime (optional)
     * @param string $validityPeriod (optional)
     * @param integer $smDefaultMsgId (optional)
     * @param integer $replaceIfPresentFlag (optional)
     */
    public function __construct($id, $status, $sequence, $body, $service_type, SmppAddress $source, SmppAddress $destination,
                                $esmClass, $protocolId, $priorityFlag, $registeredDelivery, $dataCoding, $message, $tags,
                                $message_identifier = null, $message_parts = null, $message_part_number = null,
                                $scheduleDeliveryTime=null, $validityPeriod=null, $smDefaultMsgId=null, $replaceIfPresentFlag=null)
    {
        parent::__construct($id, $status, $sequence, $body);
        $this->service_type = $service_type;
        $this->source = $source;
        $this->destination = $destination;
        $this->esmClass = $esmClass;
        $this->protocolId = $protocolId;
        $this->priorityFlag = $priorityFlag;
        $this->registeredDelivery = $registeredDelivery;
        $this->dataCoding = $dataCoding;
        $this->message = $message;
        $this->tags = $tags;
        $this->message_identifier = $message_identifier;
        $this->message_parts = $message_parts;
        $this->message_part_number = $message_part_number;
        $this->scheduleDeliveryTime = $scheduleDeliveryTime;
        $this->validityPeriod = $validityPeriod;
        $this->smDefaultMsgId = $smDefaultMsgId;
        $this->replaceIfPresentFlag = $replaceIfPresentFlag;
    }

    public function toString() {
        return "service_type: [". $this->service_type ."], source: [". $this->source->toString() ."], destination: [". $this->destination->toString() ."], esmClass: [". $this->esmClass ."], protocolId: [". $this->protocolId ."], priorityFlag: [". $this->priorityFlag ."], registeredDelivery: [". $this->registeredDelivery ."], dataCoding: [". $this->dataCoding ."], message: [". $this->message ."], tags: [". $this->getTagsToString() ."], message_identifier: [". $this->message_identifier ."], message_parts: [". $this->message_parts ."], message_part_number: [". $this->message_part_number ."], scheduleDeliveryTime: [". $this->scheduleDeliveryTime ."], validityPeriod: [". $this->validityPeriod ."], smDefaultMsgId: [". $this->smDefaultMsgId ."], replaceIfPresentFlag: [". $this->replaceIfPresentFlag ."]";
    }

    private function getTagsToString() {
        $tagsStr = "";
        $cnt = 0;

        if($this->tags !== null) {
            foreach($this->tags as $tag) {
                if ($cnt > 0) {
                    $tagsStr .= ";";
                }
                $tagsStr .= $tag->toString();
                $cnt++;
            }

            return $tagsStr;
        }
        else return $tagsStr;
    }

    public function getSourceNumberPhone() {
        if($this->source != null && $this->source->value != null) {
            return $this->source->value;
        }

        return "unknown";
    }
}