<?php

/**

 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace MasterShaper\Models;

class RulesetInterfaceModel extends DefaultModel
{
    const UNIDIRECTIONAL = 1;
    const BIDIRECTIONAL = 2;

    protected $initialized = false;
    protected $ruleset;
    protected $rules = array();
    protected $network_interface;

    protected static $tc_bin;
    protected static $ipt_bin;

    /****
     * Just to record the positions of IP packet header fields
     * when within an GRE-encapsulated tunnel:
     *
     * Pos (Byte)     Lenght (Bytes)    What
     * 25             1                 Type of Service (TOS, DSCP)
     * 33             1                 Protocol
     * 36             4                 Source IP address
     * 40             4                 Destination IP address
     *
     ****/

    /**
     * Ruleset_Interface constructor
     *
     * Initialize the Ruleset_Interface class
     */
    public function __construct(&$ruleset, $network_interface)
    {
        global $config;

        $this->ruleset = $ruleset;

        static::$tc_bin = $config->getTcPath();
        static::$ipt_bin = $config->getIptablesPath();

        if (!isset($network_interface) ||
            empty($network_interface) ||
            !is_object($network_interface) ||
            !is_a($network_interface, 'MasterShaper\Models\NetworkInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $network_interface parameter is invalid!', true);
            return;
        }

        $this->network_interface = $network_interface;
    }

    /**
     * set the status of the interface
     *
     * this function set a "initialized" flag to indicate whether
     * the interface has been already initialized or not.
     *
     * @param bool new status
     */
    protected function setStatus($status)
    {
        if (!isset($status) || !is_bool($status)) {
            static::raiseError(__METHOD__ .'(), $status parameter is invalid!');
            return false;
        }

        $this->initialized = $status;
        return true;
    }

    /**
     * return the current status of the interface
     *
     * this function return the current state of the "initialized flag to
     * indicate whether the interface has been already initialized or not.
     *
     * @return bool
     */
    public function getStatus()
    {
        if (!isset($this->initialized)) {
            return false;
        }

        return $this->initialized;
    }

    /**
     * return ruleset
     *
     * this function will return the buffer in which all
     * the generated rules for this interface are stored.
     *
     * @return string
     */
    public function getRules()
    {
        if (!isset($this->rules)) {
            return false;
        }

        return $this->rules;
    }

    /**
     * check if interface is active
     *
     * will return, if the interface assigned to this
     * class is enabled or disabled in MasterShaper
     * config.
     *
     * @return bool
     */
    public function isActive()
    {
        if (!isset($this->network_interface)) {
            static::raiseError(__METHOD__ .'(), no network interface bound!');
            return false;
        }

        return $this->network_interface->isActive();
    }

    /* return interface speed in kbps
     *
     * @return int
     */
    protected function getSpeed()
    {
        global $ms;

        if (!$this->network_interface->getSpeed()) {
            return false;
        }

        if (($speed = $this->network_interface->getSpeed()) === false) {
            static::raiseError(get_class($this->network_interface) .'::getSpeed() returned false!');
            return false;
        }

        return $ms->getKbit($speed);
    }

    /**
     * return interface id
     *
     * return the unique primary database key as interface id.
     *
     * @return int
     */
    protected function getInterfaceId()
    {
        if (!$this->network_interface->hasId()) {
            return false;
        }

        return $this->network_interface->getIdx();
    }

    /**
     * return interface name
     *
     * returns the current interface name (ipsec0, eth0, ...)
     *
     * @return string
     */
    public function getInterfaceName()
    {
        if (!$this->network_interface->hasName()) {
            return false;
        }

        return $this->network_interface->getName();
    }

    /**
     * is matching inside GRE tunnel
     *
     * @param bool
     */
    protected function isGRE()
    {
        if ($this->if_gre == 'Y') {
            return true;
        }

        return false;
    }

    protected function addRootQdisc($id)
    {
        global $ms;

        switch ($ms->getOption("classifier", true)) {
            default:
            case 'HTB':
                $type = 'htb';
                $params = array(
                    'default' => '1'
                );
                break;

            case 'HFSC':
                $type = 'hfsc';
                $params = array(
                    'default' => '1'
                );
                break;
        }

        if (!$this->ruleset->addQdisc(
            $this,
            $id,
            $type,
            $params
        )) {
            static::raiseError(get_class($this->ruleset) .'::addQdisc() returned false!');
            return false;
        }

        return true;
    }

    protected function addInitClass($parent, $classid)
    {
        global $ms;

        if (($bw = $this->getSpeed()) === false) {
            static::raiseError(__CLASS__ .'::getSpeed() returned false!');
            return false;
        }

        if (!isset($bw) || empty($bw) || !is_numeric($bw)) {
            static::raiseError(__METHOD__ .'(), unknown bandwidth for interface '. $this->getInterfaceName());
            return false;
        }

        switch ($ms->getOption("classifier", true)) {
            default:
            case 'HTB':
                $type = 'htb';
                $params = array(
                    'rate' =>$bw .'Kbit',
                );
                break;

            case 'HFSC':
                $type ='hfsc';
                $params = array(
                    'sc rate' => $bw .'Kbit',
                    'ul rate' => $bw .'Kbit'
                );
                break;
        }

        if (!$this->ruleset->addClass(
            $this,
            $classid,
            $type,
            $params,
            $parent
        )) {
            static::raiseError(get_class($this->ruleset) .'::addClass() returned false!');
            return false;
        }

        return true;
    }

    /**
     * adds the top level filter which brings traffic into the initClass
     */
    protected function addInitFilter($parent)
    {
        if (!isset($parent) ||
            empty($parent) ||
            !is_string($parent)
        ) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        global $ms;

        if (!$ms->hasOption('use_hashkey') ||
            $ms->getOption("use_hashkey", true) == "Y"
        ) {
            return true;
        }

        if (!$this->ruleset->addFilter(
            $this,
            $parent,
            "protocol all u32 match u32 0 0 classid 1:1"
        )) {
            static::raiseError(get_class($this->ruleset) .'::addFilter() returned false!');
            return false;
        }

        return true;
    }

    /**
     * adds the hashkey filter
     */
    protected function addHashkeyFilter($parent, $direction)
    {
        if (!isset($parent) ||
            empty($parent) ||
            !is_string($parent)
        ) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (!isset($direction) ||
            empty($direction) ||
            !is_string($direction)
        ) {
            static::raiseError(__METHOD__ .'(), $direction parameter is invalid!');
            return false;
        }

        global $ms;

        if (!$ms->hasOption("hashkey_ip") ||
            !$ms->hasOption("hashkey_mask") ||
            !$ms->hasOption("hashkey_matchon")) {
            static::raiseError(__METHOD__ .'(), hashkey filter is enabled, but mandatory parameters are missing!');
            return false;
        }

        if (($hashkey_matchon = $ms->getOption("hashkey_matchon")) === false) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        // if direction is out, we need to swap matchon key and src & dst targets of our chain
        if ($direction == "out") {
            if ($hashkey_matchon == "src") {
                $hashkey_matchon = "dst";
            } elseif ($hashkey_matchon == "dst") {
                $hashkey_matchon = "src";
            }
        }

        switch ($hashkey_matchon) {
            case 'src':
                $matchon = 12;
                break;
            case 'dst':
                $matchon = 16;
                break;
        }

        if (($haskey_mask = $ms->getOption("hashkey_mask")) === false) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        if (($hashkey_mask = $this->convertIpToHex($hashkey_mask)) === false) {
            static::raiseError(__CLASS__ .'::convertIpToHex() returned false!');
            return false;
        }

        if (!$this->ruleset->addFilter(
            $this,
            $parent,
            "protocol all prio 2 handle 10: u32 divisor 256"
        )) {
            static::raiseError(get_class($this->ruleset) .'::addFilter() returned false!');
            return false;
        }

        if (($haskey_ip = $ms->getOption("hashkey_ip")) === false) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        $filter = sprintf(
            "protocol all prio 2 u32 ht 800:: match ip %s %s hashkey mask 0x%s at %s link 10:",
            $hashkey_matchon,
            $hashkey_ip,
            $hashkey_mask['ip'],
            $matchon
        );

        if (!$this->ruleset->addFilter(
            $this,
            $parent,
            $filter
        )) {
            static::raiseError(get_class($this->ruleset) .'::addFilter() returned false!');
            return false;
        }

        return true;
    }

    /* Adds qdisc at the end of class for final queuing mechanism */
    protected function addSubQdisc($child, $parent, &$sl)
    {
        if (!isset($child) || empty($child) || !is_string($child)) {
            static::raiseError(__METHOD__ .'(), $child parameter is invalid!');
            return false;
        }

        if (!isset($parent) || empty($parent) || !is_string($parent)) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (!isset($sl) || (!is_null($sl) && !is_object($sl))) {
            static::raiseError(__METHOD__ .'(), $sl parameter is invalid!');
            return false;
        }

        if (is_null($sl)) {
            return true;
        }

        switch ($sl->getQdisc()) {
            default:
            case 'SFQ':
                $type = 'sfq';
                $params = array();

                if ($sl->hasSfqPerturb()) {
                    $params['perturb'] = $sl->getSfqPerturb();
                }
                if ($sl->hasSfqQuantum()) {
                    $params['quantum'] = $sl->getSfqQuantum();
                }
                break;

            case 'ESFQ':
                $type = 'esfq';
                if (($params = $this->ruleset->getEsfqParams($sl)) === false) {
                    static::raiseError(get_class($this->ruleset) .'::getEsfqParams() returned false!');
                    return false;
                }
                break;

            case 'HFSC':
                $type = 'hfsc';
                $params = array();
                break;

            case 'NETEM':
                $type = 'netem';
                if (($params = $this->ruleset->getNetemParams($sl)) === false) {
                    static::raiseError(get_class($this->ruleset) .'::getNetemParams() returned false!');
                    return false;
                }
                break;
        }

        if (!$this->ruleset->addQdisc(
            $this,
            $child,
            $type,
            $params,
            $parent
        )) {
            static::raiseError(get_class($this->ruleset) .'::addQdisc() returned false!');
            return false;
        }

        return true;
    }

    protected function addAckFilter($parent, $id, $hashtable_id = null)
    {
        if (!isset($parent) ||
            empty($parent) ||
            !is_string($parent)
        ) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (!isset($id) ||
            empty($id) ||
            !is_string($id)
        ) {
            static::raiseError(__METHOD__ .'(), $id parameter is invalid!');
            return false;
        }

        if (!isset($hashtable_id) ||
            empty($hashtable_id) ||
            !is_numeric($hashtable_id)
        ) {
            static::raiseError(__METHOD__ .'(), $hashtable_id parameter is invalid!');
            return false;
        }

        global $ms;

        switch ($ms->getOption("filter", true)) {
            default:
            case 'tc':
                if ($this->isGRE()) {
                    $filter = sprintf(
                        "protocol ip prio 1 u32 match u8 0x06 0xff at 33 match u8 0x05 0x0f at 24"
                        . "match u16 0x0000 0xffc0 at 26 match u8 0x10 0xff at 57 flowid %s",
                        $id
                    );
                } else {
                    if (!$ms->hasOption("use_hashkey") ||
                        $ms->getOption("use_hashkey") != 'Y'
                    ) {
                        $filter = "protocol ip prio 1 u32 match ip protocol 6 0xff match u8 0x05 0x0f at 0"
                            ."match u16 0x0000 0xffc0 at 2 match u8 0x10 0xff at 33 flowid ". $id;
                    } else {
                        $filter = sprintf(
                            "protocol all prio 2 u32 ht 10:%s match ip protocol 6 0xff match u8 0x05 0x0f at 0"
                                ."match u16 0x0000 0xffc0 at 2 match u8 0x10 0xff at 33 flowid %s",
                            $hashtable_id,
                            $id
                        );
                    }
                    //$this->addRule(static::$tc_bin ." filter add dev ". $this->getInterfaceName()
                    //  ." parent ". $parent ." protocol ip prio 1 u32 match ip protocol 6 0xff
                    // match u8 0x10 0xff at nexthdr+13 match u16 0x0000 0xffc0 at 2 flowid ". $id);
                }

                if (!$this->ruleset->addFilter(
                    $this,
                    $parent,
                    $filter
                )) {
                    static::raiseError(get_class($this->ruleset) .'::addFilter() returned false!');
                    return false;
                }

                break;

            case 'ipt':
                $this->addIptRule("-t mangle -A ms-postrouting -p tcp -m length --length :64"
                    ." -j CLASSIFY --set-class ". $id);
                $this->addIptRule("-t mangle -A ms-postrouting -p tcp -m length --length :64"
                    ." -j RETURN");
                break;
        }

        return true;
    }

    protected function iptInitRulesIf()
    {
        global $ms;

        if ($ms->getOption("msmode", true) == "router") {
            $this->addIptRule("-t mangle -A FORWARD -o ". $this->getInterfaceName() ." -j ms-forward");
            $this->addIptRule("-t mangle -A OUTPUT -o ". $this->getInterfaceName() ." -j ms-forward");
            $this->addIptRule("-t mangle -A POSTROUTING -o ". $this->getInterfaceName() ." -j ms-postrouting");
        } else {
            $this->addIptRule("-t mangle -A POSTROUTING -m physdev --physdev-out "
                . $this->getInterfaceName() ." -j ms-postrouting");
        }

        return true;
    }

    /**
     * initialize the current interface
     *
     * this function which initialize the current interface, which means
     * to prepare all the necessary tc-rules and add them to the buffer
     * to be executed later when loading the rules.
     */
    public function initialize($direction)
    {
        if (!isset($direction) ||
            empty($direction) ||
            !is_string($direction) ||
            ($direction !== 'in' && $direction !== 'out')
        ) {
            static::raiseError(__METHOD__ .'(), $direction parameter is invalid!');
            return false;
        }

        global $ms;

        if ($ms->hasOption("ack_sl") &&
            ($ack_sl = $ms->getOption("ack_sl")) === false
        ) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        $this->addRuleComment("Initialize Interface ". $this->getInterfaceName());

        if (!$this->addRootQdisc("1:")) {
            static::raiseError(__CLASS__ .'::addRootQdisc() returned false!');
            return false;
        }

        /* Initial iptables rules */
        if ($ms->hasOption("filter") &&
            $ms->getOption("filter") == "ipt" &&
            !$this->iptInitRulesIf()
        ) {
            static::raiseError(__CLASS__ .'::iptInitRulesIf() returned false!');
            return false;
        }

        if (!$this->addInitClass("1:", "1:1")) {
            static::raiseError(__CLASS__ .'::addInitClass() returned false!');
            return false;
        }

        if (!$this->addInitFilter("1:0")) {
            static::raiseError(__CLASS__ .'::addInitFilter() returned false!');
            return false;
        }

        /* ACK options */
        if (isset($ack_sl) && $ack_sl != 0) {
            $this->addRuleComment("boost ACK packets");
            if (!$this->addClassifier("1:1", "1:2", $ack_sl, $direction)) {
                static::raiseError(__CLASS__ .'::addClassifier() returned false!');
                return false;
            }
            if (!$this->addSubQdisc("2:", "1:2", $$ack_sl)) {
                static::raiseError(__CLASS__ .'::addSubQdisc() returned false!');
                return false;
            }
            // for hash key filters, the ACK filter needs to be add at another place
            if ((!$ms->hasOption("use_hashkey") ||
                $ms->getOption("use_hashkey") != "Y") &&
                !$this->addAckFilter("1:1", "1:2")
            ) {
                static::raiseError(__CLASS__ .'::addAckFilter() returned false!');
                return false;
            }
        }

        if ($ms->getOption('use_hashkey') &&
            $ms->getOption("use_hashkey") == "Y" &&
            !$this->addHashkeyFilter("1:0", $direction)
        ) {
            static::raiseError(__CLASS__ .'::addHashkeyFilter() retuned false!');
            return false;
        }

        $this->setStatus(true);
        return true;
    }

    /**
     * convert an IP address into a hex value
     *
     * @param string $IP
     * @return string
     */
    protected function convertIpToHex($host)
    {
        if (!isset($host) ||
            empty($host) ||
            !is_string($host)
        ) {
            static::raiseError(__METHOD__ .'(), $host parameter is invalid!');
            return false;
        }

        global $ms;

        $ipv4 = new Net_IPv4;
        $parsed = $ipv4->parseAddress($host);

        // if CIDR contains no netmask or was unparsable, we assume /32
        if (empty($parsed->netmask)) {
            $parsed->netmask = "255.255.255.255";
        }

        if (!$ipv4->validateIP($parsed->ip)) {
            $ms->throwError(_("Incorrect IP address! Can not convert it to hex!"));
        }

        if (!$ipv4->validateNetmask($parsed->netmask)) {
            $ms->throwError(_("Incorrect Netmask! Can not convert it to hex!"));
        }

        if (($hex_host = $ipv4->atoh($parsed->ip)) == false) {
            $ms->throwError(_("Failed to convert ". $parsed->ip ." to hex!"));
        }

        if (($hex_subnet = $ipv4->atoh($parsed->netmask)) == false) {
            $ms->throwError(_("Failed to convert ". $parsed->netmask ." to hex!"));
        }

        return array('ip' => $hex_host, 'netmask' => $hex_subnet);
    }

    /**
     * convert an Protocol ID number into a hex value
     *
     * @param int $ProtocolId
     * @return string
     */
    protected function convertProtoToHex($proto)
    {
        if (!isset($proto) ||
            empty($proto) ||
            !is_numeric($proto)
        ) {
            static::raiseError(__METHOD__ .'(), $proto parameter is invalіd!');
            return false;
        }

        return sprintf("%02x", $proto);
    }

    /**
     * convert an port number into a hex value
     *
     * @param int $PortNumber
     * @return string
     */
    protected function convertPortToHex($port)
    {
        if (!isset($port) ||
            empty($port) ||
            !is_numeric($port)
        ) {
            static::raiseError(__METHOD__ .'(), $port parameter is invalіd!');
            return false;
        }

        return sprintf("%04x", $port);
    }

    /*
     * returns the chain hashkey to match on
     *
     * @return string
     */
    protected function getChainHashKey($chain, $direction)
    {
        if (!isset($chain) ||
            empty($chain) ||
            is_object($chain) ||
            !is_a($chain, 'MasterShaper\Models\ChainModel')
        ) {
            static::raiseError(__METHOD__ .'(), $chain parameter is invalid!');
            return false;
        }

        if (!isset($direction) ||
            empty($direction) ||
            is_string($direction)
        ) {
            static::raiseError(__METHOD__ .'(), $direction parameter is invalid!');
            return false;
        }

        global $ms;

        if (!$ms->hasOption("hashkey_matchon")) {
            static::raiseError(get_class($ms) .'::hasOption() returned false!');
            return false;
        }

        if (!$ms->hasOption("hashkey_mask")) {
            static::raiseError(get_class($ms) .'::hasOption() returned false!');
            return false;
        }

        if (($hashkey_matchon = $ms->getOption("hashkey_matchon")) === false) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        if (($hashkey_mask = $ms->getOption("hashkey_mask")) === false) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        if ($chain->hasSourceTarget() && ($chain_src = $chain->getSourceTarget()) === false) {
            static::raiseError(get_class($chain) .'::getSourceTarget() returned false!');
            return false;
        }

        if ($chain->hasDestinationTarget() && ($chain_dst = $chain->getDestinationTarget()) === false) {
            static::raiseError(get_class($chain) .'::getDestinationTarget() returned false!');
            return false;
        }

        // if direction is out, we need to swap matchon key and src & dst targets of our chain
        if ($direction == "out") {
            if ($hashkey_matchon == "src") {
                $hashkey_matchon = "dst";
            } elseif ($hashkey_matchon == "dst") {
                $hashkey_matchon = "src";
            }

            $tmp = $chain_src;
            $chain_src = $chain_dst;
            $chain_dst = $tmp;
        }

        if ($hashkey_matchon == "src" && $chain_src == 0) {
            return false;
        }
        if ($hashkey_matchon == "dst" && $chain_dst == 0) {
            return false;
        }

        switch ($hashkey_matchon) {
            case 'src':
                if (($matchobjs = $this->getTargetHosts($chain_src)) === false) {
                    static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                    return false;
                }
                break;
            case 'dst':
                if (($matchobjs = $this->getTargetHosts($chain_dst)) === false) {
                    static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                    return false;
                }
                break;
        }

        foreach ($matchobjs as $matchobj) {
            $matchobj_hex = $this->convertIptoHex($matchobj);

            $mask_hex = $this->convertIptoHex($hashkey_mask);

            // mask our wanted octet
            $result = hexdec($matchobj_hex['ip']) & hexdec($mask_hex['ip']);

            // bit shift
            switch ($hashkey_mask) {
                case '255.0.0.0':
                    $result = $result >> 24;
                    break;
                case '0.255.0.0':
                    $result = $result >> 16;
                    break;
                case '0.0.255.0':
                    $result = $result >> 8;
                    break;
                default:
                    static::raiseError(__METHOD__ .'(), unsupported hashkey mask found!');
                    return false;
            }

            $result = sprintf("%x", $result);
            return $result;
        }

        return true;
    }

    /**
     * all the pending jobs for that interface
     *
     * will be called from Ruleset class.
     */
    public function finish()
    {
        if ($this->network_interface->hasFallback()) {
            if (!$this->addInterfaceFallback()) {
                static::raiseError(__CLASS__ .'::addInterfaceFallback() returned false!');
                return false;
            }
        }

        return true;
    }

    /**
     * add a fallback service level class to the
     * actual interface.
     */
    protected function addInterfaceFallback()
    {
        $this->current_chain += 1;

        $this->addRuleComment("interface fallback");

        if (!$this->addClassifier(
            "1:1",
            "1:". $this->getCurrentChain() . $this->getCurrentClass(),
            $this->network_interface->getFallbcakServiceLevel(true)
        )) {
            static::raiseError(__CLASS__ .'::addClassifier() returned false!');
            return false;
        }

        if (!$this->addSubQdisc(
            $this->getCurrentChain() . $this->getCurrentClass() .":",
            "1:". $this->getCurrentChain() . $this->getCurrentClass(),
            $this->network_interface->getFallbackServiceLevel(true)
        )) {
            static::raiseError(__CLASS__ .'::addSubQdisc() returned false!');
            return false;
        }

        if (!$this->ruleset->addFallbackFilter(
            $if,
            "1:1",
            "1:". $this->getCurrentChain() . $this->getCurrentClass()
        )) {
            static::raiseError(get_class($this->ruleset) .'::addFallbackFilter() returned false!');
            return false;
        }

        return true;
    }

    /**
     * add comment-line to ruleset
     *
     * @param string $text
     */
    public function addRuleComment($text, $where = null)
    {
        if (!isset($text) || empty($text) || !is_string($text)) {
            static::raiseError(__METHOD__ .'(), $text parameter is invalid!');
            return false;
        }

        if (!$this->addRule("######### ". $text, $where)) {
            static::raiseError(__CLASS__ .'::addRule() returned false!');
            return false;
        }

        return true;
    }

    public function addIptRule($cmd, $where = null)
    {
        return $this->addRule(static::$ipt_bin ." ". $cmd, $where);
    }

    /**
     * add rule-lint to ruleset
     *
     * @param string $cmd
     */
    public function addRule($cmd, $where = null)
    {
        if (!isset($cmd) ||
            empty($cmd) ||
            (!is_string($cmd) && !is_object($cmd)) ||
            (is_object($cmd) &&
            !is_a($cmd, 'MasterShaper\Models\RulesetClassModel') &&
            !is_a($cmd, 'MasterShaper\Models\RulesetQdiscModel') &&
            !is_a($cmd, 'MasterShaper\Models\RulesetFilterModel'))
        ) {
            static::raiseError(__METHOD__ .'(), $cmd parameter is invalid!');
            return false;
        }

        array_push($this->rules, $cmd);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
