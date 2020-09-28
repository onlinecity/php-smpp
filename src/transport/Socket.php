<?php


namespace smpp\transport;

use smpp\exceptions\SocketTransportException;

/**
 * TCP Socket Transport for use with multiple protocols.
 * Supports connection pools and IPv6 in addition to providing a few public methods to make life easier.
 * It's primary purpose is long running connections, since it don't support socket re-use, ip-blacklisting, etc.
 * It assumes a blocking/synchronous architecture, and will block when reading or writing, but will enforce timeouts.
 *
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class Socket
{
    protected $socket;
    protected $hosts;
    protected $persist;
    protected $debugHandler;
    public $debug;

    protected static $defaultSendTimeout=100;
    protected static $defaultRecvTimeout=750;
    public static $defaultDebug=false;

    public static $forceIpv6=false;
    public static $forceIpv4=false;
    public static $randomHost=false;

    /**
     * Construct a new socket for this transport to use.
     *
     * @param array $hosts list of hosts to try.
     * @param mixed $ports list of ports to try, or a single common port
     * @param boolean $persist use persistent sockets
     * @param mixed $debugHandler callback for debug info
     */
    public function __construct(array $hosts,$ports,$persist=false,$debugHandler=null)
    {
        $this->debug = self::$defaultDebug;
        $this->debugHandler = $debugHandler ? $debugHandler : 'error_log';

        // Deal with optional port
        $h = array();
        foreach ($hosts as $key => $host) {
            $h[] = array($host, is_array($ports) ? $ports[$key] : $ports);
        }
        if (self::$randomHost) shuffle($h);
        $this->resolveHosts($h);

        $this->persist = $persist;
    }

    /**
     * Resolve the hostnames into IPs, and sort them into IPv4 or IPv6 groups.
     * If using DNS hostnames, and all lookups fail, a InvalidArgumentException is thrown.
     *
     * @param array $hosts
     * @throws \InvalidArgumentException
     */
    protected function resolveHosts($hosts)
    {
        $i = 0;
        foreach($hosts as $host) {
            list($hostname,$port) = $host;
            $ip4s = array();
            $ip6s = array();
            if (preg_match('/^([12]?[0-9]?[0-9]\.){3}([12]?[0-9]?[0-9])$/',$hostname)) {
                // IPv4 address
                $ip4s[] = $hostname;
            } else if (preg_match('/^([0-9a-f:]+):[0-9a-f]{1,4}$/i',$hostname)) {
                // IPv6 address
                $ip6s[] = $hostname;
            } else { // Do a DNS lookup
                if (!self::$forceIpv4) {
                    // if not in IPv4 only mode, check the AAAA records first
                    $records = dns_get_record($hostname,DNS_AAAA);
                    if ($records === false && $this->debug) {
                        call_user_func($this->debugHandler, 'DNS lookup for AAAA records for: '.$hostname.' failed');
                    }
                    if ($records) {
                        foreach ($records as $r) {
                            if (isset($r['ipv6']) && $r['ipv6']) $ip6s[] = $r['ipv6'];
                        }
                    }
                    if ($this->debug) {
                        call_user_func($this->debugHandler, "IPv6 addresses for $hostname: ".implode(', ',$ip6s));
                    }
                }
                if (!self::$forceIpv6) {
                    // if not in IPv6 mode check the A records also
                    $records = dns_get_record($hostname,DNS_A);
                    if ($records === false && $this->debug) {
                        call_user_func($this->debugHandler, 'DNS lookup for A records for: '.$hostname.' failed');
                    }
                    if ($records) {
                        foreach ($records as $r) {
                            if (isset($r['ip']) && $r['ip']) $ip4s[] = $r['ip'];
                        }
                    }
                    // also try gethostbyname, since name could also be something else, such as "localhost" etc.
                    $ip = gethostbyname($hostname);
                    if ($ip != $hostname && !in_array($ip,$ip4s)) {
                        $ip4s[] = $ip;
                    }
                    if ($this->debug) {
                        call_user_func($this->debugHandler, "IPv4 addresses for $hostname: ".implode(', ',$ip4s));
                    }
                }
            }

            // Did we get any results?
            if (self::$forceIpv4 && empty($ip4s)) {
                continue;
            }
            if (self::$forceIpv6 && empty($ip6s)) {
                continue;
            }
            if (empty($ip4s) && empty($ip6s)) {
                continue;
            }

            if ($this->debug) {
                $i += count($ip4s)+count($ip6s);
            }

            // Add results to pool
            $this->hosts[] = array($hostname,$port,$ip6s,$ip4s);
        }
        if ($this->debug) {
            call_user_func($this->debugHandler, "Built connection pool of ".count($this->hosts)." host(s) with $i ip(s) in total");
        }
        if (empty($this->hosts)) {
            throw new \InvalidArgumentException('No valid hosts was found');
        }
    }

    /**
     * Get a reference to the socket.
     * You should use the public functions rather than the socket directly
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Get an arbitrary option
     *
     * @param integer $option
     * @param integer $lvl
     * @return false|mixed
     */
    public function getSocketOption($option,$lvl=SOL_SOCKET)
    {
        return socket_get_option($this->socket,$lvl,$option);
    }

    /**
     * Set an arbitrary option
     *
     * @param integer $option
     * @param mixed $value
     * @param integer $lvl
     * @return bool
     */
    public function setSocketOption($option,$value,$lvl=SOL_SOCKET)
    {
        return socket_set_option($this->socket,$lvl,$option,$value);
    }

    /**
     * Sets the send timeout.
     * Returns true on success, or false.
     * @param int $timeout	Timeout in milliseconds.
     * @return boolean
     */
    public function setSendTimeout($timeout)
    {
        if (!$this->isOpen()) {
            self::$defaultSendTimeout = $timeout;
        } else {
            return socket_set_option(
                $this->socket,
                SOL_SOCKET,
                SO_SNDTIMEO,
                $this->millisecToSolArray($timeout)
            );
        }
    }

    /**
     * Sets the receive timeout.
     * Returns true on success, or false.
     * @param int $timeout	Timeout in milliseconds.
     * @return boolean
     */
    public function setRecvTimeout($timeout)
    {
        if (!$this->isOpen()) {
            self::$defaultRecvTimeout = $timeout;
        } else {
            return socket_set_option(
                $this->socket,
                SOL_SOCKET,
                SO_RCVTIMEO,
                $this->millisecToSolArray($timeout)
            );
        }
    }

    /**
     * Check if the socket is constructed, and there are no exceptions on it
     * Returns false if it's closed.
     * Throws SocketTransportException is state could not be ascertained
     * @throws SocketTransportException
     */
    public function isOpen()
    {
        if (!is_resource($this->socket)) return false;
        $r = null;
        $w = null;
        $e = array($this->socket);
        $res = socket_select($r,$w,$e,0);
        if ($res === false) {
            throw new SocketTransportException(
                'Could not examine socket; '.socket_strerror(socket_last_error()),
                socket_last_error()
            );
        }
        if (!empty($e)) return false; // if there is an exception on our socket it's probably dead
        return true;
    }

    /**
     * Convert a milliseconds into a socket sec+usec array
     * @param integer $millisec
     * @return array
     */
    private function millisecToSolArray($millisec)
    {
        $usec = $millisec*1000;
        return ['sec' => floor($usec/1000000), 'usec' => $usec%1000000];
    }

    /**
     * Open the socket, trying to connect to each host in succession.
     * This will prefer IPv6 connections if forceIpv4 is not enabled.
     * If all hosts fail, a SocketTransportException is thrown.
     *
     * @throws SocketTransportException
     */
    public function open()
    {
        if (!self::$forceIpv4) {
            $socket6 = @socket_create(AF_INET6,SOCK_STREAM,SOL_TCP);
            if ($socket6 == false) {
                throw new SocketTransportException(
                    'Could not create socket; ' . socket_strerror(socket_last_error()),
                    socket_last_error()
                );
            }
            socket_set_option($socket6,SOL_SOCKET,SO_SNDTIMEO,$this->millisecToSolArray(self::$defaultSendTimeout));
            socket_set_option($socket6,SOL_SOCKET,SO_RCVTIMEO,$this->millisecToSolArray(self::$defaultRecvTimeout));
        }
        if (!self::$forceIpv6) {
            $socket4 = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
            if ($socket4 == false) throw new SocketTransportException('Could not create socket; '.socket_strerror(socket_last_error()), socket_last_error());
            socket_set_option($socket4,SOL_SOCKET,SO_SNDTIMEO,$this->millisecToSolArray(self::$defaultSendTimeout));
            socket_set_option($socket4,SOL_SOCKET,SO_RCVTIMEO,$this->millisecToSolArray(self::$defaultRecvTimeout));
        }
        $it = new \ArrayIterator($this->hosts);
        while ($it->valid()) {
            list($hostname,$port,$ip6s,$ip4s) = $it->current();
            if (!self::$forceIpv4 && !empty($ip6s)) { // Attempt IPv6s first
                foreach ($ip6s as $ip) {
                    if ($this->debug) call_user_func($this->debugHandler, "Connecting to $ip:$port...");
                    $r = @socket_connect($socket6, $ip, $port);
                    if ($r) {
                        if ($this->debug) call_user_func($this->debugHandler, "Connected to $ip:$port!");
                        @socket_close($socket4);
                        $this->socket = $socket6;
                        return;
                    } elseif ($this->debug) {
                        call_user_func($this->debugHandler, "Socket connect to $ip:$port failed; ".socket_strerror(socket_last_error()));
                    }
                }
            }
            if (!self::$forceIpv6 && !empty($ip4s)) {
                foreach ($ip4s as $ip) {
                    if ($this->debug) call_user_func($this->debugHandler, "Connecting to $ip:$port...");
                    $r = @socket_connect($socket4, $ip, $port);
                    if ($r) {
                        if ($this->debug) call_user_func($this->debugHandler, "Connected to $ip:$port!");
                        @socket_close($socket6);
                        $this->socket = $socket4;
                        return;
                    } elseif ($this->debug) {
                        call_user_func($this->debugHandler, "Socket connect to $ip:$port failed; ".socket_strerror(socket_last_error()));
                    }
                }
            }
            $it->next();
        }
        throw new SocketTransportException('Could not connect to any of the specified hosts');
    }

    /**
     * Do a clean shutdown of the socket.
     * Since we don't reuse sockets, we can just close and forget about it,
     * but we choose to wait (linger) for the last data to come through.
     */
    public function close()
    {
        $arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_LINGER, $arrOpt);
        socket_close($this->socket);
    }

    /**
     * Check if there is data waiting for us on the wire
     * @return boolean
     * @throws SocketTransportException
     */
    public function hasData()
    {
        $r = array($this->socket);
        $w = null;
        $e = null;
        $res = socket_select($r,$w,$e,0);
        if ($res === false) throw new SocketTransportException('Could not examine socket; '.socket_strerror(socket_last_error()), socket_last_error());
        if (!empty($r)) return true;
        return false;
    }

    /**
     * Read up to $length bytes from the socket.
     * Does not guarantee that all the bytes are read.
     * Returns false on EOF
     * Returns false on timeout (technically EAGAIN error).
     * Throws SocketTransportException if data could not be read.
     *
     * @param integer $length
     * @return mixed
     * @throws SocketTransportException
     */
    public function read($length)
    {
        $d = socket_read($this->socket,$length,PHP_BINARY_READ);
        // sockets give EAGAIN on timeout
        if ($d === false && socket_last_error() === SOCKET_EAGAIN) {
            return false;
        }
        if ($d === false) {
            throw new SocketTransportException(
                'Could not read '.$length.' bytes from socket; '.socket_strerror(socket_last_error()),
                socket_last_error()
            );
        }
        if ($d === '') {
            return false;
        }

        return $d;
    }

    /**
     * Read all the bytes, and block until they are read.
     * Timeout throws SocketTransportException
     *
     * @param integer $length
     * @return string
     */
    public function readAll($length)
    {
        $d = "";
        $r = 0;
        $readTimeout = socket_get_option($this->socket,SOL_SOCKET,SO_RCVTIMEO);
        while ($r < $length) {
            $buf = '';
            $r += socket_recv($this->socket,$buf,$length-$r,MSG_DONTWAIT);
            if ($r === false) {
                throw new SocketTransportException('Could not read '.$length.' bytes from socket; '.socket_strerror(socket_last_error()), socket_last_error());
            }
            $d .= $buf;
            if ($r == $length) {
                return $d;
            }

            // wait for data to be available, up to timeout
            $r = array($this->socket);
            $w = null;
            $e = array($this->socket);
            $res = socket_select($r,$w,$e,$readTimeout['sec'],$readTimeout['usec']);

            // check
            if ($res === false) {
                throw new SocketTransportException('Could not examine socket; '.socket_strerror(socket_last_error()), socket_last_error());
            }
            if (!empty($e)) {
                throw new SocketTransportException('Socket exception while waiting for data; '.socket_strerror(socket_last_error()), socket_last_error());
            }
            if (empty($r)) {
                throw new SocketTransportException('Timed out waiting for data on socket');
            }
        }
    }

    /**
     * Write (all) data to the socket.
     * Timeout throws SocketTransportException
     *
     * @param $buffer
     * @param integer $length
     */
    public function write($buffer,$length)
    {
        $r = $length;
        $writeTimeout = socket_get_option($this->socket,SOL_SOCKET,SO_SNDTIMEO);

        while ($r>0) {
            $wrote = socket_write($this->socket,$buffer,$r);
            if ($wrote === false) {
                throw new SocketTransportException('Could not write '.$length.' bytes to socket; '.socket_strerror(socket_last_error()), socket_last_error());
            }
            $r -= $wrote;
            if ($r == 0) {
                return;
            }

            $buffer = substr($buffer,$wrote);

            // wait for the socket to accept more data, up to timeout
            $r = null;
            $w = array($this->socket);
            $e = array($this->socket);
            $res = socket_select($r,$w,$e,$writeTimeout['sec'],$writeTimeout['usec']);

            // check
            if ($res === false) {
                throw new SocketTransportException('Could not examine socket; '.socket_strerror(socket_last_error()), socket_last_error());
            }
            if (!empty($e)) {
                throw new SocketTransportException('Socket exception while waiting to write data; '.socket_strerror(socket_last_error()), socket_last_error());
            }
            if (empty($w)) {
                throw new SocketTransportException('Timed out waiting to write data on socket');
            }
        }
    }
}