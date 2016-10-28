<?php

namespace Phpsmpp\Test\Protocol;
use Phpsmpp\Protocol\SMPP;
use Phpsmpp\Protocol\SmppAddress;

/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 28/10/2016
 * Time: 13:38
 */
class SmppAddressTest extends \PHPUnit_Framework_TestCase
{
    public function testShortCodeStaysShortCode()
    {
        $address = new SmppAddress("76544", SMPP::TON_INTERNATIONAL, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164, SMPP::NPI_E164, 41);
        $this->assertTrue($address->inboundInternationalPhoneNumber == "76544");
        $this->assertTrue($address->outboundInternationalPhoneNumber == "76544");
    }

    public function testNationalBecomesInternational()
    {
        $address = new SmppAddress("0786413023", SMPP::TON_NATIONAL, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164, SMPP::NPI_E164, 41);
        $this->assertTrue($address->inboundInternationalPhoneNumber == "+41786413023");
        $this->assertTrue($address->outboundInternationalPhoneNumber == "41786413023");
    }

    public function testInternationalBecomesInternational()
    {
        $address = new SmppAddress("41786413023", SMPP::TON_INTERNATIONAL, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164, SMPP::NPI_E164, 41);
        $this->assertTrue($address->inboundInternationalPhoneNumber == "+41786413023");
        $this->assertTrue($address->outboundInternationalPhoneNumber == "41786413023");

        $address = new SmppAddress("+41786413023", SMPP::TON_INTERNATIONAL, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164, SMPP::NPI_E164, 41);
        $this->assertTrue($address->inboundInternationalPhoneNumber == "+41786413023");
        $this->assertTrue($address->outboundInternationalPhoneNumber == "41786413023");

        $address = new SmppAddress("0041786413023", SMPP::TON_INTERNATIONAL, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164, SMPP::NPI_E164, 41);
        $this->assertTrue($address->inboundInternationalPhoneNumber == "+41786413023");
        $this->assertTrue($address->outboundInternationalPhoneNumber == "41786413023");
    }

    public function testUnknownBecomesInternational() {
        $address = new SmppAddress("0786413023", SMPP::TON_UNKNOWN, SMPP::TON_NATIONAL, SMPP::NPI_UNKNOWN, SMPP::NPI_E164, 41);
        $this->assertTrue($address->inboundInternationalPhoneNumber == "+41786413023");
        $this->assertTrue($address->outboundInternationalPhoneNumber == "41786413023");

        $address = new SmppAddress("41786413023", SMPP::TON_UNKNOWN, SMPP::TON_INTERNATIONAL, SMPP::NPI_UNKNOWN, SMPP::NPI_E164, 41);
        $this->assertTrue($address->inboundInternationalPhoneNumber == "+41786413023");
        $this->assertTrue($address->outboundInternationalPhoneNumber == "41786413023");
    }
}