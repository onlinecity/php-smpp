<?php
namespace Phpsmpp\Protocol;
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 14/10/2016
 * Time: 09:50
 */

/**
 * An extension of a SMS, with data embedded into the message part of the SMS.
 * @author hd@onlinecity.dk
 */
class SmppDeliveryReceipt extends SmppSms
{
    public $id;
    public $sub;
    public $dlvrd;
    public $submitDate;
    public $doneDate;
    public $stat;
    public $err;
    public $text;

    /**
     * Parse a delivery receipt formatted as specified in SMPP v3.4 - Appendix B
     * It accepts all chars except space as the message id
     *
     * @throws InvalidArgumentException
     */
    public function parseDeliveryReceipt()
    {
        $numMatches = preg_match('/^id:([^ ]+) sub:(\d{1,3}) dlvrd:(\d{3}) submit date:(\d{10}) done date:(\d{10}) stat:([A-Z]{7}) err:(\d{3}) text:(.*)$/ms', $this->message, $matches);
        if ($numMatches == 0) {
            throw new InvalidArgumentException('Could not parse delivery receipt: '.$this->message."\n".bin2hex($this->body));
        }
        list($matched, $this->id, $this->sub, $this->dlvrd, $this->submitDate, $this->doneDate, $this->stat, $this->err, $this->text) = $matches;

        // Convert dates
        $dp = str_split($this->submitDate,2);
        $this->submitDate = gmmktime($dp[3],$dp[4],0,$dp[1],$dp[2],$dp[0]);
        $dp = str_split($this->doneDate,2);
        $this->doneDate = gmmktime($dp[3],$dp[4],0,$dp[1],$dp[2],$dp[0]);
    }
}