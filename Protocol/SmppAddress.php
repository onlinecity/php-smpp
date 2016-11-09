<?php
namespace Phpsmpp\Protocol;
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 14/10/2016
 * Time: 09:52
 */

/**
 * Primitive class for encapsulating smpp addresses
 * @author hd@onlinecity.dk
 */
class SmppAddress
{
    public $ton; // type-of-number
    /**
     * The default ton if $ton is unknown
     * @var
     */
    public $default_unknown_addr_ton;
    public $npi; // numbering-plan-indicator
    /**
     * The default npi if $npi is unknown
     * @var
     */
    public $default_unknown_addr_npi;

    /** The phone number (not formatted)
     * @var string
     */
    public $value;
    /** For example, switzerland: 41, Nigeria: 234
     * @var integer
     */
    public $gatewayCountryCodeNumber;

    /**
     * Phone number formatted in the inbound (incoming) sms
     * @var string
     */
    public $inboundInternationalPhoneNumber;

    /**
     * Phone number formatted for the SMS-C when we send the sms
     * @var string
     */
    public $outboundInternationalPhoneNumber;

    /**
     * Construct a new object of class Address
     *
     * @param string $value
     * @param integer $ton
     * @param integer $npi
     * @throws \InvalidArgumentException
     */
    public function __construct($value, $ton, $default_unknown_addr_ton, $npi, $default_unknown_addr_npi, $gatewayCountryCodeNumber)
    {
        // Address-Value field may contain 10 octets (12-length-type), see 3GPP TS 23.040 v 9.3.0 - section 9.1.2.5 page 46.
        if ($ton == SMPP::TON_ALPHANUMERIC && strlen($value) > 11) throw new \InvalidArgumentException('Alphanumeric address may only contain 11 chars');
        if ($ton == SMPP::TON_INTERNATIONAL && $npi == SMPP::NPI_E164 && strlen($value) > 15) throw new \InvalidArgumentException('E164 address may only contain 15 digits');

        $this->value = (string) $value;
        $this->ton = $ton;
        $this->default_unknown_addr_ton = $default_unknown_addr_ton;
        $this->npi = $npi;
        $this->default_unknown_addr_npi = $default_unknown_addr_npi;
        $this->gatewayCountryCodeNumber = $gatewayCountryCodeNumber;
        $this->inboundInternationalPhoneNumber = $this->getInboundInternationalPhoneNumber();
        $this->outboundInternationalPhoneNumber = $this->getoutboundInternationalPhoneNumber();
    }

    /** To understand what this methods does, see the unit test class SmppAddressTest
     * @return string
     */
    private function getInboundInternationalPhoneNumber()
    {
        //if value is null or empty
        if ($this->value == null) {
            return $this->value;
        }

        //if value is a short code
        if (strlen($this->value) <= 6) {
            return $this->value;
        }

        //if value already starts with "+"
        if (mb_substr($this->value, 0, 1, "utf-8") === "+") {
            return $this->value;
        }

        //if value starts with "00"
        if (mb_substr($this->value, 0, 2, "utf-8") === "00") {
            //replace "00" with "+"
            return "+".mb_substr($this->value, 2, null, "utf-8");
        }

        $npi = $this->npi;
        if($this->npi == SMPP::NPI_UNKNOWN) {
            $npi = $this->default_unknown_addr_npi;
        }

        if($npi == SMPP::NPI_E164) {
            $ton = $this->ton;
            if ($this->ton == SMPP::TON_UNKNOWN) {
                $ton = $this->default_unknown_addr_ton;
            }

            if ($ton == SMPP::TON_INTERNATIONAL) {
                return "+" . $this->value;
            }

            if ($ton == SMPP::TON_NATIONAL) {
                return "+" . $this->gatewayCountryCodeNumber . mb_substr($this->value, 1, null, "utf-8");
            }
        }

        return $this->value;
    }

    /** To understand what this methods does, see the unit test class SmppAddressTest
     * @return string
     */
    private function getoutboundInternationalPhoneNumber()
    {
        //if value is null or empty
        if ($this->value == null) {
            return $this->value;
        }

        //if value is a short code
        if (strlen($this->value) <= 6) {
            return $this->value;
        }

        //if value starts with "+", remove it
        if (mb_substr($this->value, 0, 1, "utf-8") === "+") {
            return mb_substr($this->value, 1, null, "utf-8");
        }

        //if value starts with "00"
        if (mb_substr($this->value, 0, 2, "utf-8") === "00") {
            //Remove the "00"
            return mb_substr($this->value, 2, null, "utf-8");
        }

        $npi = $this->npi;
        if($this->npi == SMPP::NPI_UNKNOWN) {
            $npi = $this->default_unknown_addr_npi;
        }

        if($npi == SMPP::NPI_E164) {
            $ton = $this->ton;
            if ($this->ton == SMPP::TON_UNKNOWN) {
                $ton = $this->default_unknown_addr_ton;
            }

            if ($ton == SMPP::TON_INTERNATIONAL) {
                return $this->value;
            }

            if ($ton == SMPP::TON_NATIONAL) {
                return $this->gatewayCountryCodeNumber . mb_substr($this->value, 1, null, "utf-8");
            }
        }

        return $this->value;
    }

    public function toString() {
        return "value: [". $this->value ."], inboundStandardizedPhoneNumber: [". $this->inboundInternationalPhoneNumber ."], outboundStandardizedPhoneNumber:[". $this->outboundInternationalPhoneNumber ."], ton: [". $this->ton ."], default_unknown_addr_ton: [". $this->default_unknown_addr_ton ."], npi: [". $this->npi ."], default_unknown_addr_npi: [". $this->default_unknown_addr_npi ."], gatewayCountryCodeNumber: [". $this->gatewayCountryCodeNumber ."]";
    }
}