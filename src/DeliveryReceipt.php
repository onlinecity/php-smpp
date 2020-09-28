<?php


namespace smpp;

/**
 * An extension of a SMS, with data embedded into the message part of the SMS.
 * @author hd@onlinecity.dk
 */
class DeliveryReceipt extends Sms
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
     * @throws \InvalidArgumentException
     */
    public function parseDeliveryReceipt()
    {
        $numMatches = preg_match('/^id:([^ ]+) sub:(\d{1,3}) dlvrd:(\d{3}) submit date:(\d{10,12}) done date:(\d{10,12}) stat:([A-Z ]{7}) err:(\d{2,3}) text:(.*)$/si', $this->message, $matches);
        if ($numMatches == 0) {
            throw new \InvalidArgumentException('Could not parse delivery receipt: '.$this->message."\n".bin2hex($this->body));
        }
        list($matched, $this->id, $this->sub, $this->dlvrd, $this->submitDate, $this->doneDate, $this->stat, $this->err, $this->text) = $matches;

        // Convert dates
        $dp = str_split($this->submitDate,2);
        $this->submitDate = gmmktime($dp[3],$dp[4],isset($dp[5]) ? $dp[5] : 0,$dp[1],$dp[2],$dp[0]);
        $dp = str_split($this->doneDate,2);
        $this->doneDate = gmmktime($dp[3],$dp[4],isset($dp[5]) ? $dp[5] : 0,$dp[1],$dp[2],$dp[0]);
    }
}