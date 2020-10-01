PHP SMPP (v3.4) client
====

Install:

    composer require alexandr-mironov/php-smpp
    
Example of wrapper (php>=7.0) for this Client.
In this case we got ALPHANUMERIC sender value 'github_example':

```php
<?php

namespace your_namespace;

use smpp\{ Address, SMPP, Client as SmppClient, transport\Socket};

class SmsBuilder
{
    const DEFAULT_SENDER = 'github_example';
    protected $transport;
    protected $smppClient;
    protected $debug = false;
    protected $from;
    protected $to;
    protected $login;
    protected $password;

    /**
     * SmsBuilder constructor.
     * @param string $address SMSC IP
     * @param int $port SMSC port
     * @param string $login
     * @param string $password
     * @param int $timeout timeout of reading PDU in milliseconds
     * @param bool $debug - debug flag when true output additional info
     */
    public function __construct(
        string $address,
        int $port,
        string $login,
        string $password,
        int $timeout = 10000,
        bool $debug = false
    )
    {
        $this->transport = new Socket([$address], $port);
        $this->transport->setRecvTimeout($timeout);
        $this->smppClient = new SmppClient($this->transport);

        // Activate binary hex-output of server interaction
        $this->smppClient->debug = $debug;
        $this->transport->debug = $debug;

        $this->login = $login;
        $this->password = $password;

        $this->from = new Address(self::DEFAULT_SENDER,SMPP::TON_ALPHANUMERIC);
    }

    /**
     * @param $sender
     * @param $ton
     * @return $this
     * @throws \Exception
     */
    public function setSender($sender, $ton)
    {
        return $this->setAddress($sender, 'from', $ton);
    }

    /**
     * @param $address
     * @param $ton
     * @return $this
     * @throws \Exception
     */
    public function setRecipient($address, $ton)
    {
        return $this->setAddress($address, 'to', $ton);
    }

    /**
     * @param $address
     * @param string $type
     * @param int $ton
     * @param int $npi
     * @return $this
     * @throws \Exception
     */
    protected function setAddress($address, string $type, $ton = SMPP::TON_UNKNOWN, $npi = SMPP::NPI_UNKNOWN)
    {
        // some example of data preparation
        if($ton === SMPP::TON_INTERNATIONAL){
             $npi = SMPP::NPI_E164;
        }
        $this->$type = new Address($address, $ton, $npi);
        return $this;
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message)
    {
        $this->transport->open();
        $this->smppClient->bindTransceiver($this->login,$this->password);
        // strongly recommend use SMPP::DATA_CODING_UCS2 as default encoding in project to prevent problems with non latin symbols
        $this->smppClient->sendSMS($this->from, $this->to, $message, null, SMPP::DATA_CODING_UCS2);
        $this->smppClient->close();
    }
}
```

This wrapper implement some kind of Builder pattern, usage example:
```php
<?php
// replace address, port, login and password to your values
(new your_namespace\SmsBuilder('192.168.1.1', '2776', 'your_login', 'your_password', 10000))
    ->setRecipient('79000000000', \smpp\SMPP::TON_INTERNATIONAL) //msisdn of recipient
    ->sendMessage('Тестовое сообщение на русском and @noth3r$Ymb0ls');
```

Original description
=======
PHP-based SMPP client lib
=============

This is a simplified SMPP client lib for sending or receiving smses through [SMPP v3.4](http://www.smsforum.net/SMPP_v3_4_Issue1_2.zip).

In addition to the client, this lib also contains an encoder for converting UTF-8 text to the GSM 03.38 encoding, and a socket wrapper. The socket wrapper provides connection pool, IPv6 and timeout monitoring features on top of PHP's socket extension.

This lib has changed significantly from it's first release, which required namespaces and included some worker components. You'll find that release at [1.0.1-namespaced](https://github.com/onlinecity/php-smpp/tree/1.0.1-namespaced)

This lib requires the [sockets](http://www.php.net/manual/en/book.sockets.php) PHP-extension, and is not supported on Windows. A [windows-compatible](https://github.com/onlinecity/php-smpp/tree/windows-compatible) version is also available.


Connection pools
-----
You can specify a list of connections to have the SocketTransport attempt each one in succession or randomly. Also if you give it a hostname with multiple A/AAAA-records it will try each one.
If you want to monitor the DNS lookups, set defaultDebug to true before constructing the transport.

The (configurable) send timeout governs how long it will wait for each server to timeout. It can take a long time to try a long list of servers, depending on the timeout. You can change the timeout both before and after the connection attempts are made.

The transport supports IPv6 and will prefer IPv6 addresses over IPv4 when available. You can modify this feature by setting forceIpv6 or forceIpv4 to force it to only use IPv6 or IPv4.

In addition to the DNS lookups, it will also look for local IPv4 addresses using gethostbyname(), so "localhost" works for IPv4. For IPv6 localhost specify "::1". 


Implementation notes
-----

 - You can't connect as a transceiver, otherwise supported by SMPP v.3.4
 - The SUBMIT_MULTI operation of SMPP, which sends a SMS to a list of recipients, is not supported atm. You can easily add it though.
 - The sockets will return false if the timeout is reached on read() (but not readAll or write). 
   You can use this feature to implement an enquire_link policy. If you need to send enquire_link for every 30 seconds of inactivity, 
   set a timeout of 30 seconds, and send the enquire_link command after readSMS() returns false.
 - The examples above assume that the SMSC default datacoding is [GSM 03.38](http://en.wikipedia.org/wiki/GSM_03.38).
 - Remember to activate registered delivery if you want delivery receipts (set to SMPP::REG_DELIVERY_SMSC_BOTH / 0x01).
 - Both the SmppClient and transport components support a debug callback, which defaults to [error_log](http://www.php.net/manual/en/function.error-log.php) . Use this to redirect debug information.
 
F.A.Q.
-----

**Can I use this to send messages from my website?**  
Not on it's own, no. After PHP processes the request on a website, it closes all connections. Most SMPP providers do not want you to open and close connections, you should keep them alive and send enquire_link commands periodically. Which means you probably need to get some kind of long running process, ie. using the [process control functions](http://www.php.net/manual/en/book.pcntl.php), and implement a form of queue system which you can push to from the website. This requires shell level access to the server, and knowledge of unix processes.

**How do I receive delivery receipts or SMS'es?**  
To receive a delivery receipt or a SMS you must connect a receiver in addition to the transmitter. This receiver must wait for a delivery receipt to arrive, which means you probably need to use the [process control functions](http://www.php.net/manual/en/book.pcntl.php).

We do have an open source implementation at [php-smpp-worker](https://github.com/onlinecity/php-smpp-worker) you can look at for inspiration, but we cannot help you with making your own. Perhaps you should look into if your SMSC provider can give you a HTTP based API or using turnkey software such as [kannel](http://www.kannel.org/), this project provides the protocol implementation only and a basic socket wrapper.

**I can't send more than 160 chars**  
There are three built-in methods to send Concatenated SMS (csms); CSMS_16BIT_TAGS, CSMS_PAYLOAD, CSMS_8BIT_UDH. CSMS_16BIT_TAGS is the default, if it don't work try another.

**Is this lib compatible with PHP 5.2.x ?**  
It's tested on PHP 5.3, but is known to work with 5.2 as well.

**Can it run on windows?**  
It requires the sockets extension, which is available on windows, but is incomplete. Use the [windows-compatible](https://github.com/onlinecity/php-smpp/tree/windows-compatible) version instead, which uses fsockopen and stream functions.

**Why am I not seeing any debug output?**  
Remember to implement a debug callback for SocketTransport and SmppClient to use. Otherwise they default to [error_log](http://www.php.net/manual/en/function.error-log.php) which may or may not print to screen. 

**Why do I get 'res_nsend() failed' or 'Could not connect to any of the specified hosts' errors?**  
Your provider's DNS server probably has an issue with IPv6 addresses (AAAA records). Try to set ```SocketTransport::$forceIpv4=true;```. You can also try specifying an IP-address (or a list of IPs) instead. Setting ```SocketTransport:$defaultDebug=true;``` before constructing the transport is also useful in resolving connection issues.

**I tried forcing IPv4 and/or specifying an IP-address, but I'm still getting 'Could not connect to any of the specified hosts'?**  
It would be a firewall issue that's preventing your connection, or something else entirely. Make sure debug output is enabled and displayed. If you see something like 'Socket connect to 1.2.3.4:2775 failed; Operation timed out' this means a connection could not be etablished. If this isn't a firewall issue, you might try increasing the connect timeout. The sendTimeout also specifies the connect timeout, call ```$transport->setSendTimeout(10000);``` to set a 10-second timeout.

**Why do I get 'Failed to read reply to command: 0x4', 'Message Length is invalid' or 'Error in optional part' errors?**  
Most likely your SMPP provider doesn't support NULL-terminating the message field. The specs aren't clear on this issue, so there is a toggle. Set ```SmppClient::$sms_null_terminate_octetstrings = false;``` and try again.  

**What does 'Bind Failed' mean?**  
It typically means your SMPP provider rejected your login credentials, ie. your username or password.

**Can I test the client library without a SMPP server?**  
Many service providers can give you a demo account, but you can also use the [logica opensmpp simulator](http://opensmpp.logica.com/CommonPart/Introduction/Introduction.htm#simulator) (java) or [smsforum client test tool](http://www.smsforum.net/sctt_v1.0.Linux.tar.gz) (linux binary). In addition to a number of real-life SMPP servers this library is tested against these simulators.

**I have an issue that not mentioned here, what do I do?**  
Please obtain full debug information, and open an issue here on github. Make sure not to include the Send PDU hex-codes of the BindTransmitter call, since it will contain your username and password. Other hex-output is fine, and greatly appeciated. Any PHP Warnings or Notices could also be important. Please include information about what SMPP server you are connecting to, and any specifics.
