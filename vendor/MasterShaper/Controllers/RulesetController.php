<?php

/**
 * This file is part of MasterShaper.
 *
 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 */

namespace MasterShaper\Controllers;

class RulesetController extends DefaultController
{
    const IF_NOT_USED = -1;
    const MS_PRE = 10;
    const MS_POST = 13;
    const UNIDIRECTIONAL = 1;
    const BIDIRECTIONAL = 2;

    protected $host_profile;

    protected $ms_pre;
    protected $ms_post;
    protected $classes = array();
    protected $filters = array();
    protected $interfaces = array();
    protected $error;
    protected $rules = array();

    protected $current_chain  = 0;
    protected $current_class  = null;
    protected $current_filter = null;
    protected $current_pipe   = null;


    protected static $tc_bin;
    protected static $ipt_bin;

    public function __construct()
    {
        global $session, $config;

        $this->ms_pre = array();
        $this->ms_post = array();
        $this->error  = array();

        if (($this->host_profile = $session->getCurrentHostProfile()) === false) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned false!', true);
            return;
        }

        static::$tc_bin = $config->getTcPath();
        static::$ipt_bin = $config->getIptablesPath();

    } // __construct()

    public function load($debug = null)
    {
        global $ms;

        if (!$this->initRules()) {
            static::raiseError(__CLASS__ .'::initRules() returned false!');
            return false;
        }

        $retval = $debug ? $this->doItLineByLine() : $this->doIt();

        if (!$retval) {
            $ms->setOption("reload_timestamp", mktime());
        }

        return $retval;

    } // load()

    /**
     * unload MasterShaper ruleset
     */
    public function unload()
    {
        global $ms;

        $this->delActiveInterfaceQdiscs();
        $this->delIptablesRules();
        $ms->setShaperStatus(false);

    } // unload()

    protected function iptInitRules()
    {
        $this->addRule(static::$ipt_bin ." -t mangle -N ms-forward", static::MS_PRE);
        $this->addRule(static::$ipt_bin ." -t mangle -N ms-postrouting", static::MS_PRE);
        $this->addRule(static::$ipt_bin ." -t mangle -N ms-prerouting", static::MS_PRE);
        $this->addRule(static::$ipt_bin ." -t mangle -A PREROUTING -j ms-prerouting", static::MS_PRE);

        /* We must restore the connection mark in PREROUTING table first! */
        $this->addRule(static::$ipt_bin ." -t mangle -A ms-prerouting -j CONNMARK --restore-mark", static::MS_PRE);
        $this->addRule(static::$ipt_bin ." -t mangle -A ms-prerouting -j CONNMARK --save-mark", static::MS_POST);

    } // iptInitRules()

    protected function getRules($rules)
    {
        if (!isset($rules) ||
            empty($rules) ||
            !is_integer($rules)
        ) {
            static::raiseError(__METHOD__ .'(), $rules parameter is invalid!');
            return false;
        }

        switch ($rules) {
            case static::MS_PRE:
                return $this->ms_pre;
                break;

            case static::MS_POST:
                return $this->ms_post;
                break;

            default:
                static::raiseError(__METHOD__ .'(), invalid ruleset requested!');
                return false;
                break;
        }

        return false;
    }

    public function initRules()
    {
        global $ms, $db;

        if (!$this->flushTcIds()) {
            static::raiseError(__CLASS__ .'::flushTcIds() returned false!');
            return false;
        }

        if ($ms->hasOption("filter") &&
            $ms->getOption("filter") == "ipt"
            ) {
            if (!$this->iptInitRules()) {
                static::raiseError(__CLASS__ .'::iptInitRules() returned false!');
                return false;
            }
        }

        if (($netpaths = $this->getActiveNetpaths()) === false) {
            static::raiseError(__CLASS__ .'::getActiveNetpaths() returned false!');
            return false;
        }

        if (!$netpaths->hasItems()) {
            return true;
        }

        foreach ($netpaths->getItems() as $netpath) {
            $have_if2 = true;
            $do_nothing = false;

            if (!$netpath->hasInterface1()) {
                continue;
            }

            if (($if1 = $netpath->getInterface1(true)) === false) {
                static::raiseError(get_class($netpath) .'::getInterface1() returned false!');
                return false;
            }

            if ($netpath->hasInterface2() && ($if2 = $netpath->getInterface2(true)) === false) {
                static::raiseError(get_class($netpath) .'::getInterface2() returned false!');
                return false;
            }

            if (($if1_idx = $if1->getIdx()) === false) {
                static::raiseError(get_class($if1) .'::getIdx() returned false!');
                return false;
            }

            if (isset($if1_idx) &&
                !empty($if1_idx) &&
                is_numeric($if1_idx) &&
                !isset($this->interfaces[$if1_idx])
            ) {
                try {
                    $this->interfaces[$if1_idx] = new \MasterShaper\Models\RulesetInterfaceModel($this, $if1);
                } catch (\Exception $e) {
                    static::raiseError(__METHOD__ .'(), failed to load RulesetInterfaceModel!', false, $e);
                    return false;
                }
            }

            /* the second interface of the interface is no must, only create it when necessary */
            if (isset($if2) &&
                !empty($if2) &&
                is_object($if2)
            ) {
                if (($if2_idx = $if2->getIdx()) === false) {
                    static::raiseError(get_class($if2) .'::getIdx() returned false!');
                    return false;
                }
                if (!isset($this->interfaces[$if2_idx])) {
                    try {
                        $this->interfaces[$if2_idx] = new \MasterShaper\Models\RulesetInterfaceModel($this, $if2);
                    } catch (\Exception $e) {
                        static::raiseError(__METHOD__ .'(), failed to load RulesetInterfaceModel!', false, $e);
                        return false;
                    }
                }
            }

            /* get interface 2 parameters (if available) */
            if ($netpath->netpath_if2 == static::IF_NOT_USED) {
                $have_if2 = false;
            }

            /* If a interface on this network path is inactive, ignore it completely */
            if ($this->interfaces[$if1_idx]->isActive() != "Y") {
                $do_nothing = true;
            }

            if ($have_if2 && $this->interfaces[$if2_idx]->isActive() != "Y") {
                $do_nothing = true;
            }

            if ($do_nothing) {
                continue;
            }

            //$if->addRuleComment("Rules for Network Path ". $netpath->netpath_name, static::MS_PRE);

            /* tc structure
                1: root qdisc
                1:1 root class (dev. bandwidth limit)
                1:2
                1:3
                1:4
             */

            /* only initialize the interface if it isn't already */
            if (!$this->interfaces[$netpath->netpath_if1]->getStatus()) {
                $this->interfaces[$netpath->netpath_if1]->initialize("in");
            }

            /* only initialize the interface if it isn't already */
            if ($have_if2 && !$this->interfaces[$netpath->netpath_if2]->getStatus()) {
                $this->interfaces[$netpath->netpath_if2]->initialize("out");
            }

            if ($netpath->netpath_imq == "Y") {
                if (!$this->buildChains(
                    $this->interfaces[$if1_idx],
                    $netpath->isInterface1InsideGre(),
                    $netpath,
                    'in'
                )) {
                    static::raiseError(__CLASS__ .'::buildChains() returned false!');
                    return false;
                }
                if ($have_if2) {
                    if (!$this->buildChains(
                        $this->interfaces[$if2_idx],
                        $netpath->isInterface2InsideGre(),
                        $netpath,
                        'out'
                    )) {
                        static::raiseError(__CLASS__ .'::buildChains() returned false!');
                        return false;
                    }
                }
            } else {
                if (!$this->buildChains(
                    $this->interfaces[$if1_idx],
                    $netpath->isInterface1InsideGre(),
                    $netpath,
                    'in'
                )) {
                    static::raiseError(__CLASS__ .'::buildChains() returned false!');
                    return false;
                }
                if ($have_if2) {
                    if (!$this->buildChains(
                        $this->interfaces[$if2_idx],
                        $netpath->isInterface2InsideGre(),
                        $netpath,
                        'out'
                    )) {
                        static::raiseError(__CLASS__ .'::buildChains() returned false!');
                        return false;
                    }
                }
            }
        }

        // finish all interfaces (ex. add a interface fallback class, ...)
        foreach ($this->interfaces as $if) {
            $if->finish();
        }

        return true;
    }

    /* Delete parent qdiscs */
    protected function delQdisc($interface)
    {
        if (!isset($interface) ||
            empty($interface) ||
            !is_string($interface)
        ) {
            static::raiseError(__METHOD__ .'(), $interface parameter is invalid!');
            return false;
        }

        $this->runProc("tc", static::$tc_bin . " qdisc del dev ". $interface ." root", true);

    } // delQdisc()

    protected function delIptablesRules()
    {
        $this->runProc("cleanup", null, true);

    } // delIptablesRules

    protected function doIt()
    {
        global $ms;

        $error = array();
        $found_error = 0;

        /* Delete current root qdiscs */
        $this->delActiveInterfaceQdiscs();

        $this->delIptablesRules();

        /* Prepare the tc batch file */
        $temp_tc  = tempnam(TEMP_PATH, "FOOTC");
        $output_tc  = fopen($temp_tc, "w");

        /* If necessary prepare iptables batch files */
        if ($ms->hasOption("filter") &&
            $ms->getOption("filter") == "ipt") {
            $temp_ipt = tempnam(TEMP_PATH, "FOOIPT");
            $output_ipt = fopen($temp_ipt, "w");
        }

        foreach ($this->getCompleteRuleset() as $line) {
            $line = trim($line);
            if (!preg_match("/^#/", $line)) {
                /* tc filter task */
                if (strstr($line, static::$tc_bin) !== false && $line != "") {
                    $line = str_replace(static::$tc_bin ." ", "", $line);
                    fputs($output_tc, $line ."\n");
                }
                /* iptables task */
                if (strstr($line, static::$ipt_bin) !== false &&
                    $ms->hasOption("filter") &&
                    $ms->getOption("filter") == "ipt"
                ) {
                    fputs($output_ipt, $line ."\n");
                }
            }
        }

        /* flush batch files */
        fclose($output_tc);

        if ($ms->hasOption("filter") &&
            $ms->getOption("filter") == "ipt"
        ) {
            fclose($output_ipt);
        }

        /* load tc filter rules */
        if (($error = $this->runProc("tc", static::$tc_bin . " -b ". $temp_tc)) != true) {
            static::raiseError(
                "Error on mass loading tc rules.<br>"
                ."Try load ruleset in debug mode to figure incorrect or not supported rule."
            );
            $found_error = 1;
        }

        /* load iptables rules */
        if ($ms->hasOption("filter") &&
            $ms->getOption("filter") == "ipt" &&
            !$found_error
        ) {
            if (($error = $this->runProc("iptables", $temp_ipt)) != true) {
                static::raiseError(
                    "Error on mass loading iptables rule.<br />"
                    ."Try load ruleset in debug mode to figure incorrect or not supported rule."
                );
                $found_error = 1;
            }
        }

        //unlink($temp_tc);
        if ($ms->hasOption("filter") &&
            $ms->getOption("filter") == "ipt"
        ) {
            unlink($temp_ipt);
        }

        if (!$found_error) {
            $ms->setShaperStatus(true);
        } else {
            $ms->setShaperStatus(false);
        }

        return $found_error;

    } // doIt()

    protected function doItLineByLine()
    {
        global $ms;

        /* Delete current root qdiscs */
        $this->delActiveInterfaceQdiscs();
        $this->delIptablesRules();

        $ipt_lines = array();

        foreach ($this->getCompleteRuleset() as $line) {
            // output comments as they are
            if (preg_match("/^#/", $line)) {
                $ms->_print($line, MSLOG_DEBUG, 'display');
                continue;
            }

            if (strstr($line, static::$tc_bin) !== false) {
                $ms->_print($line, MSLOG_DEBUG, 'display');
                if (($tc = $this->runProc("tc", $line)) !== true) {
                    $ms->_print($tc, MSLOG_DEBUG, 'display');
                }
            }

            // iptables output will follow later
            if (strstr($line, static::$ipt_bin) !== false) {
                array_push($ipt_lines, $line);
            }
        }

        // output iptables commands
        foreach ($ipt_lines as $line) {
            $ms->_print($line, MSLOG_DEBUG, 'display');
            if (($ipt = $this->runProc("iptables", $line)) !== true) {
                $ms->_print($ipt, MSLOG_DEBUG, 'display');
            }
        }

    } // doItLineByLine()

    protected function output($text)
    {
        if ($_GET['output'] == "noisy") {
            print $text ."\n";
        }

    } // output()

    protected function getCompleteRuleset()
    {
        $ruleset = array();

        foreach ($this->ms_pre as $tmp) {
            array_push($ruleset, $tmp);
        }

        foreach ($this->interfaces as $interface) {
            if (!isset($interface) ||
                empty($interface) ||
                !is_object($interface) ||
                !is_a($interface, 'MasterShaper\Models\RulesetInterfaceModel')
            ) {
                static::raiseError(__METHOD__ .'(), invalid RulesetInterfaceModel found!');
                return false;
            }
            if (($rules = $interface->getRules()) === false) {
                static::raiseError(get_class($interfaces) .'::getRules() returned false!');
                return false;
            }
            foreach ($rules as $rule) {
                if (is_string($rule)) {
                    if (empty($rule)) {
                        static::raiseError(__METHOD__ .'(), invalid rule found!');
                        return false;
                    }
                    array_push($ruleset, $rule);
                } elseif (is_object($rule)) {
                    if (($rule_str = sprintf("%s", $rule)) === false) {
                        static::raiseError(get_class($rule) .'::__toString() returned false!');
                        return false;
                    }
                    if (!is_string($rule_str) || empty($rule_str)) {
                        static::raiseError(__METHOD__ .'(), invalid rule found!');
                        return false;
                    }
                    array_push($ruleset, $rule_str);
                } else {
                    static::raiseError(__METHOD__ .'(), invalid rule found!');
                    return false;
                }
            }
        }

        foreach ($this->ms_post as $tmp) {
            array_push($ruleset, $tmp);
        }

        return $ruleset;
    }

    public function showIt()
    {
        $string = "";

        if (($rules = $this->getCompleteRuleset()) === false) {
            static::raiseError(__CLASS__ .'::getCompleteRuleset() returned false!');
            return false;
        }

        foreach ($rules as $tmp) {
            foreach (preg_split("/\n/", $tmp) as $line) {
                $line = trim($line);
                if ($line != "") {
                    $string.= "<font style='color: ". $this->getColor($line) .";'>". $line ."</font><br />\n";
                }
            }
        }

        return $string;

    } // showIt()

    protected function getColor($text)
    {
        if (strstr($text, "########")) {
            return "#666666";
        } elseif (strstr($text, static::$tc_bin)) {
            return "#AF0000";
        } elseif (strstr($text, static::$ipt_bin)) {
            return "#0000AF";
        }

        return "#000000";

    } // getColor()

    protected function runProc($option, $cmd = "", $ignore_err = null)
    {
        global $ms;

        $retval = "";
        $error = "";

        $desc = array(
                0 => array('pipe','r'), /* STDIN */
                1 => array('pipe','w'), /* STDOUT */
                2 => array('pipe','w'), /* STDERR */
                );

        $process = proc_open(
            SUDO_BIN ." ". MASTERSHAPER_BASE ."/shaper_loader.sh ". $option ." \"". $cmd ."\"",
            $desc,
            $pipes
        );

        if (is_resource($process)) {
            $stdin = $pipes[0];
            $stdout = $pipes[1];
            $stderr = $pipes[2];

            while (!feof($stdout)) {
                $retval.= trim(fgets($stdout));
            }
            while (!feof($stderr)) {
                $error.= trim(fgets($stderr));
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exit_code = proc_close($process);
        }

        if (is_null($ignore_err)) {
            if (!empty($error) || $retval != "OK") {
                print("An error occured: ". $error);
            }
        }

        return $retval;
    } // runProc()

    protected function delActiveInterfaceQdiscs()
    {
        global $ms;

        $result = $ms->getActiveInterfaces();
        while ($row = $result->fetch()) {
            $this->delQdisc($row->if_name);
        }
    } // delActiveInterfaceQdiscs()

    protected function getActiveNetpaths()
    {
        try {
            $netpaths = new \MasterShaper\Models\NetworkPathsModel(array(
                'host_idx' => $this->getHost(),
                'active' => 'Y'
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkPathsModel!', false, $e);
            return false;
        }

        return $netpaths;
    }

    public function smartyRulesetOutput($params, &$smarty)
    {
        if ($this->initRules()) {
            return $this->showIt();
        }
    }

    public function getHost()
    {
        if (!isset($this->host_profile) ||
            empty($this->host_profile) ||
            !is_numeric($this->host_profile)
        ) {
            static::raiseError(__METHOD__ .'(), no host selected!');
            return false;
        }

        return $this->host_profile;
    }

    final protected function flushTcIds()
    {
        try {
            $tc_ids = new \MasterShaper\Models\TcIdsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load TcIdsModel!', false, $e);
            return false;
        }

        if (!$tc_ids->flush()) {
            static::raiseError(get_class($tc_ids) .'::flush() returned false!');
            return false;
        }

        return true;
    }

    /**
     * build chain-ruleset
     *
     * this function will build up the chain-ruleset necessary
     * for the provided network path and direction.
     */
    public function buildChains(&$if, $is_gre, $netpath, $direction)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($is_gre) || !is_bool($is_gre)) {
            static::raiseError(__METHOD__ .'(), $is_gre parameter is invalid!');
            return false;
        }

        if (!isset($netpath) ||
            empty($netpath) ||
            !is_object($netpath) ||
            !is_a($netpath, 'MasterShaper\Models\NetworkPathModel')
        ) {
            static::raiseError(__METHOD__ .'(), $netpath parameter is invalid!');
            return false;
        }

        if (!isset($direction) ||
            empty($direction) ||
            !is_string($direction)
        ) {
            static::raiseError(__METHOD__ .'(), $direction parameter is invalid!');
            return false;
        }

        global $ms, $cache;

        $if->addRuleComment("Rules for interface ". $if->getInterfaceName());

        if (!$cache->has('chains')) {
            try {
                $chains = new \MasterShaper\Models\ChainsModel(array(/*
                    'active' => 'Y',
                    'netpath_idx' => $netpath_idx,
                    'host_idx' => $this->getHost(),*/
                ), array(
                    'position' => 'ASC',
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load ChainsModel!', false, $e);
                return false;
            }
            if (!$cache->add($chains, 'chains')) {
                static::raiseError(get_class($cache) .'::add() returned false!');
                return false;
            }
        } else {
            if (($chains = $cache->get('chains')) === false) {
                static::raiseError(get_class($cache) .'::get() returned false!');
                return false;
            }
        }

        if (!$chains->hasItems()) {
            return true;
        }

        $filter = array(
            'active' => 'Y',
            'netpath_idx' => $netpath->getIdx(),
            'host_idx' => $this->getHost(),
        );

        foreach ($chains->getItems(null, null, $filter) as $chain) {
            // prepare class identifiers for the now to-be-handled chain
            $this->current_chain += 1;
            $this->current_class  = 1;
            $this->current_pipe   = 1;
            $this->current_filter = 1;

            $if->addRuleComment("chain ". $chain->getName());
            /* chain doesn't ignore QoS? */
            if ($chain->hasServiceLevel()) {
                if (!$this->addClassifier(
                    $if,
                    "1:1",
                    "1:". $this->getCurrentChain() . $this->getCurrentClass(),
                    $chain->getServiceLevel(true),
                    $direction
                )) {
                    static::raiseError(__CLASS__ .'::addClassifier() returned false!');
                    return false;
                }
            }

            /* remember the assigned chain id */
            if (!$this->setChainID(
                $if,
                $chain->getIdx(),
                "1:". $this->getCurrentChain() . $this->getCurrentClass(),
                "dst",
                "src"
            )) {
                static::raiseError(__CLASS__ .'::setChainID() returned false!');
                return false;
            }

            if ($ms->hasOption("filter") &&
                $ms->getOption("filter") == "ipt"
            ) {
                $if->addRule(static::$ipt_bin ." -t mangle -N ms-chain-". $this->getInterfaceName() ."-1:". $this->getCurrentChain() . $this->getCurrentFilter());
                $if->addRule(static::$ipt_bin ." -t mangle -A ms-postrouting -m connmark --mark ". $ms->getConnmarkId($this->getInterfaceId(), "1:". $this->getCurrentChain() . $this->getCurrentFilter()) ." -j ms-chain-". $this->getInterfaceName() ."-1:". $this->getCurrentChain() . $this->getCurrentFilter());
            }

            /*if (!$chain->hasServiceLevel()) {
              $filter_flow_target = "1:1";
              else*/
            $filter_flow_target = "1:". $this->getCurrentChain() . $this->getCurrentFilter();

            /* setup the filter definition to match traffic which should go into this chain */
            if ($chain->hasSourceTarget() || $chain->hasDestinationTarget()) {
                if (!$ms->hasOption("use_hashkey") ||
                    $ms->getOption("use_hashkey") != 'Y'
                ) {
                    $this->addChainFilter($if, $is_gre, "1:1", $chain, $filter_flow_target, $direction);
                } else {
                    $this->addChainFilter($if, $is_gre, "1:0", $chain, $filter_flow_target, $direction);
                }
            } else {
                $this->addChainMatchallFilter($if, "1:1", $filter_flow_target);
            }

            /* chain does ignore QoS? then skip further processing */
            if (!$chain->hasServiceLevel()) {
                continue;
            }

            /* chain uses fallback service level? if no, add a qdisc
               and skip further processing of this chain
             */
            if (!$chain->hasFallbackServiceLevel()) {
                $if->addRuleComment("chain without fallback service level");
                $this->addSubQdisc($if, $this->getCurrentChain() . $this->getCurrentClass() .":", "1:". $this->getCurrentChain() . $this->getCurrentClass(), $chain->getServiceLevel(true));
                continue;
            }

            $if->addRuleComment("generating pipes for ". $chain->getName());
            if (!$ms->hasOption("use_hashkey") ||
                $ms->getOption("use_hashkey") != 'Y'
            ) {
                if (!$this->buildPipes($if, $is_gre, $chain->getIdx(), "1:". $this->getCurrentChain() . $this->getCurrentClass(), $direction, $chain->getServiceLevel(true))) {
                    static::raiseError(__CLASS__ .'::buildPipes() returned false!');
                    return false;
                }
            } else {
                if (($chain_hex_id = $this->getChainHashKey($chain, $direction)) === false) {
                    static::raiseError(__CLASS__ .'::getChainHashKey() returned false!');
                    return false;
                }
                if (!$this->buildPipes($if, $is_gre, $chain->getIdx(), "1:". $this->getCurrentChain() . $this->getCurrentClass(), $direction, $chain->getServiceLevel(true), $chain_hex_id)) {
                    static::raiseError(__CLASS__ .'::buildPipes() returned false!');
                    return false;
                }
            }

            // Fallback
            if (($fallback_sl = $chain->getFallbackServiceLevel(true)) === false) {
                static::raiseError(get_class($chain) .'::getFallbackServiceLevel() returned false!');
                return false;
            }

            $if->addRuleComment("fallback pipe");
            $this->addClassifier($if, "1:". $this->getCurrentChain() . $this->getCurrentClass(), "1:". $this->getCurrentChain() ."00", $fallback_sl, $direction, $chain->getServiceLevel(true));
            $this->addSubQdisc($if, $this->getCurrentChain() ."00:", "1:". $this->getCurrentChain() ."00", $fallback_sl);
            if ($ms->hasOption("use_hashkey") ||
                $ms->getOption("use_hashkey") != 'Y'
            ) {
                $this->addFallbackFilter($if, "1:". $this->getCurrentChain() . $this->getCurrentClass(), "1:". $this->getCurrentChain() ."00");
            } else {
                $this->addFallbackFilter($if, "1:". $this->getCurrentChain() . $this->getCurrentClass(), "1:". $this->getCurrentChain() ."00", $chain_hex_id);
            }
            $this->setPipeID($if, -1, $chain->getIdx(), "1:". $this->getCurrentChain() ."00");
        }

        return true;
    }

    /**
     * add rule-lint to ruleset
     *
     * @param string $cmd
     */
    protected function addRule($cmd, $where = null)
    {
        die("NOT USE ME!");

        if (!isset($cmd) ||
            empty($cmd) ||
            (!is_string($cmd) && !is_object($cmd))
        ) {
            static::raiseError(__METHOD__ .'(), $cmd parameter is invalid!');
            return false;
        }

        switch ($where) {
            case static::MS_PRE:
                array_push($this->ms_pre, $cmd);
                break;

            case static::MS_POST:
                array_push($this->ms_post, $cmd);
                break;

            default:
                array_push($this->rules, $cmd);
                break;
        }

        return true;
    }

    public function addQdisc(&$if, $id, $type, $params, $parent = null)
    {
        if (!isset($if) || empty($if) || !is_object($if)) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($id) || empty($id) || !is_string($id)) {
            static::raiseError(__METHOD__ .'(), $id parameter is invalid!');
            return false;
        }

        if (!isset($type) || empty($type) || !is_string($type)) {
            static::raiseError(__METHOD__ .'(), $type parameter is invalid!');
            return false;
        }

        if (!isset($params) || empty($params) || !is_array($params)) {
            static::raiseError(__METHOD__ .'(), $params parameter is invalid!');
            return false;
        }

        if (isset($parent) && !empty($parent) && !is_string($parent)) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        try {
            $qdisc = new \MasterShaper\Models\RulesetQdiscModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load RulesetQdiscModel!', false, $e);
            return false;
        }

        if (!$qdisc->setInterface($if)) {
            static::raiseError(get_class($qdisc) .'::setInterface() returned false');
            return false;
        }

        if (!$qdisc->setHandle($id)) {
            static::raiseError(get_class($qdisc) .'::setHandle() returned false!');
            return false;
        }

        if (isset($parent) && !empty($parent) && !$qdisc->setParent($parent)) {
            static::raiseError(get_class($qdisc) .'::setParent() returned false!');
            return false;
        }

        if (!$qdisc->setType($type)) {
            static::raiseError(get_class($qdisc) .'::setType() returned false!');
            return false;
        }

        if (!$qdisc->setParams($params)) {
            static::raiseError(get_class($qdisc) .'::setParams() returned false!');
            return false;
        }

        if (!$if->addRule($qdisc)) {
            static::raiseError(get_class($if) .'::addRule() returned false!');
            return false;
        }

        return true;
    }

    public function addClass(&$if, $id, $type, $params, $parent = null)
    {
        if (!isset($if) || empty($if) || !is_object($if)) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($id) || empty($id) || !is_string($id)) {
            static::raiseError(__METHOD__ .'(), $id parameter is invalid!');
            return false;
        }

        if (!isset($type) || empty($type) || !is_string($type)) {
            static::raiseError(__METHOD__ .'(), $type parameter is invalid!');
            return false;
        }

        if (!isset($params) || empty($params) || !is_array($params)) {
            static::raiseError(__METHOD__ .'(), $params parameter is invalid!');
            return false;
        }

        if (isset($parent) && !empty($parent) && !is_string($parent)) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        try {
            $class = new \MasterShaper\Models\RulesetClassModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load RulesetClassModel!', false, $e);
            return false;
        }

        if (!$class->setInterface($if)) {
            static::raiseError(get_class($class) .'::setInterface() returned false');
            return false;
        }

        if (!$class->setHandle($id)) {
            static::raiseError(get_class($class) .'::setHandle() returned false!');
            return false;
        }

        if (isset($parent) && !empty($parent) && !$class->setParent($parent)) {
            static::raiseError(get_class($class) .'::setParent() returned false!');
            return false;
        }

        if (!$class->setType($type)) {
            static::raiseError(get_class($class) .'::setType() returned false!');
            return false;
        }

        if (!$class->setParams($params)) {
            static::raiseError(get_class($class) .'::setParams() returned false!');
            return false;
        }

        if (!$if->addRule($class)) {
            static::raiseError(get_class($if) .'::addRule() returned false!');
            return false;
        }

        return true;
    }

    public function addFilter(&$if, $parent, $match)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($parent) || empty($parent) || !is_string($parent)) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (!isset($match) || empty($match) || !is_string($match)) {
            static::raiseError(__METHOD__ .'(), $match parameter is invalid!');
            return false;
        }

        try {
            $filter = new \MasterShaper\Models\RulesetFilterModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load RulesetFilterModel!', false, $e);
            return false;
        }

        if (!$filter->setInterface($if)) {
            static::raiseError(get_class($filter) .'::setInterface() returned false');
            return false;
        }

        if (!$filter->setParent($parent)) {
            static::raiseError(get_class($filter) .'::setParent() returned false');
            return false;
        }

        if (!$filter->setFilter($match)) {
            static::raiseError(get_class($filter) .'::setFilter() returned false');
            return false;
        }

        if (!$if->addRule($filter)) {
            static::raiseError(__CLASS__ .'::addRule() returned false!');
            return false;
        }

        return true;
    }

    /* Adds a class definition for a inbound chain */
    protected function addClassifier(&$if, $parent, $classid, $sl, $direction = "in", $parent_sl = null)
    {
        if (!isset($if) || empty($if) || !is_object($if)) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($parent) || empty($parent) || !is_string($parent)) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (!isset($classid) || empty($classid) || !is_string($classid)) {
            static::raiseError(__METHOD__ .'(), $classid parameter is invalid!');
            return false;
        }

        if (!isset($sl) || empty($sl) || !is_object($sl)) {
            static::raiseError(__METHOD__ .'(), $sl parameter is invalid!');
            return false;
        }

        if (!isset($direction) || empty($direction) || !is_string($direction)) {
            static::raiseError(__METHOD__ .'(), $direction parameter is invalid!');
            return false;
        }

        if (isset($parent_sl) && !empty($parent_sl) && !is_object($parent_sl)) {
            static::raiseError(__METHOD__ .'(), $parent_sl parameter is invalid!');
            return false;
        }

        global $ms;

        switch ($direction) {
            case 'in':
                switch ($ms->getOption("classifier", true)) {
                    default:
                    case 'HTB':
                        $type = 'htb';
                        $params = array();
                        if ($sl->hasHtbBandwidthInRate()) {
                            $params['rate'] = $sl->getHtbBandwidthInRate() .'Kbit';
                            if ($sl->hasHtbBandwidthInCeil()) {
                                $params['ceil'] = $sl->getHtbBandwidthInCeil() .'Kbit';
                            }
                            if ($sl->hasHtbBandwidthInBurst()) {
                                $params['burst'] = $sl->getHtbBandwidthInBurst() .'kb';
                            }
                            if ($sl->hasHtbPriority()) {
                                $params['prio'] = $sl->getHtbPriority();
                            }
                        } else {
                            if (isset($parent_sl) && $parent_sl->hasHtbBandwidthInRate()) {
                                $params['rate'] = $parent_sl->getHtbBandwidthInRate() .'Kbit';
                                if ($parent_sl->hasHtbBandwidthInCeil()) {
                                    $params['ceil'] = $parent_sl->getHtbBandwidthInCeil() .'Kbit';
                                }
                            } else {
                                $params['rate'] = '1Kbit';
                                $params['ceil'] = $this->getSpeed() .'Kbit';
                            }

                            if ($sl->hasHtbBandwidthInBurst()) {
                                $params['burst'] = $sl->getHtbBandwidthInBurst() .'kb';
                            }
                            if ($sl->hasHtbPriority()) {
                                $params['prio'] = $sl->getHtbPriority();
                            }
                        }
                        /* this value remains hardcoded here.
                         *******
                         It might be good time to touch concept of quantums
                         now. In fact when more classes want to borrow
                         bandwidth they are each given some number of bytes
                         before serving other competing class. This number
                         is called quantum. You should see that if several
                         classes are competing for parent's bandwidth then
                         they get it in proportion of their quantums. It is
                         important to know that for precise operation
                         quantums need to be as small as possible and
                         larger than MTU.
                         *******
                         */
                        $params['quantum'] = 1532;
                        /*if (isset($sl->hasSfqQuantum()) {
                            $string.= " quantum ". $sl->getSfqQuantum();
                        }*/
                        break;

                    case 'HFSC':
                        $type.= 'hfsc';
                        $params = array();
                        if ($sl->hasHfscInUmax()) {
                            $params['sc umax'] = $sl->getHfscInUmax() .'b';
                        }
                        if ($sl->hasHfscInDmax()) {
                            $params['sc dmax'] = $sl->getHfscInDmax() .'ms';
                        }
                        if ($sl->hasHfscInRate()) {
                            $params['sc rate'] = $sl->getHfscInRate() .'Kbit';
                        }
                        if ($sl->hasHfscInUlRate()) {
                            $params['ul rate'] = $sl->getHfscInUlRate() .'Kbit';
                        }

                        if ($sl->hasHfscInUmax()) {
                            $params['rt umax'] = $sl->getHfscInUmax() .'b';
                        }
                        if ($sl->hasHfscInDmax()) {
                            $params['rt dmax'] = $sl->getHfscInDmax() .'ms';
                        }
                        if ($sl->hasHfscInRate()) {
                            $params['rt rate'] = $sl->getHfscInRate() .'Kbit';
                        }
                        if ($sl->hasHfscInUlRate()) {
                            $params['ul rate'] = $sl->getHfscInUlRate() .'Kbit';
                        }
                        break;
                }
                break;

            case 'out':
                switch ($ms->getOption("classifier", true)) {
                    default:
                    case 'HTB':
                        $type = 'htb';
                        $params = array();

                        if ($sl->hasHtbBandwidthOutRate()) {
                            $params['rate'] = $sl->getHtbBandwidthOutRate() .'Kbit';
                            if ($sl->hasHtbBandwidthOutCeil()) {
                                $params['ceil'] = $sl->getHtbBandwidthOutCeil() .'Kbit';
                            }
                            if ($sl->hasHtbBandwidthOutBurst()) {
                                $params['burst'] = $sl->getHtbBandwidthOutBurst() .'kb';
                            }
                            if ($sl->hasHtbPriority()) {
                                $params['prio'] = $sl->getHtbPriority();
                            }
                        } else {
                            if (isset($parent_sl) && $parent_sl->hasHtbBandwidthOutRate()) {
                                $params['rate'] = $parent_sl->getHtbBandwidthOutRate() .'Kbit';
                                if ($parent_sl->hasHtbBandwidthOutCeil()) {
                                    $params['ceil'] = $parent_sl->getHtbBandwidthOutBurst() .'Kbit';
                                }
                            } else {
                                $params['rate'] = '1Kbit';
                                $params['ceil'] = $this->getSpeed() .'Kbit';
                            }

                            if ($sl->hasHtbBandwidthOutBurst()) {
                                $params['burst'] = $sl->getHtbBandwidthOutBurst() .'kb';
                            }
                            if ($sl->hasHtbPriority()) {
                                $params['prio'] = $sl->getHtbPriority();
                            }
                        }
                        if ($sl->hasSfqQuantum()) {
                            $params['quantum'] = $sl->getSfqQuantum();
                        }
                        break;

                    case 'HFSC':
                        $type = 'hfsc';
                        if ($sl->hasHfscOutUmax()) {
                            $params['sc umax'] = $sl->getHfscOutUmax() .'b';
                        }
                        if ($sl->hasHfscOutDmax()) {
                            $params['sc dmax'] = $sl->getHfscOutDmax() .'ms';
                        }
                        if ($sl->hasHfscOutRate()) {
                            $params['sc rate'] = $sl->getHfscOutRate() .'Kbit';
                        }
                        if ($sl->hasHfscOutUlrate()) {
                            $params['ul rate'] = $sl->getHfscOutUlrate() .'Kbit';
                        }
                        if ($sl->hasHfscOutUmax()) {
                            $params['rt umax'] = $sl->getHfscOutUmax() .'b';
                        }
                        if ($sl->hasHfscOutDmax()) {
                            $params['rt dmax'] = $sl->getHfscOutDmax() .'ms';
                        }
                        if ($sl->hasHfscOutRate()) {
                            $params['rt rate'] = $sl->getHfscOutRate() .'Kbit';
                        }
                        if ($sl->hasHfscOutUlrate()) {
                            $params['ul rate'] = $sl->getHfscOutUlrate() .'Kbit';
                        }
                        break;
                }
                break;
        }

        if (!$this->addClass(
            $if,
            $classid,
            $type,
            $params,
            $parent
        )) {
            static::raiseError(__CLASS__ .'::addClass() returned false!');
            return false;
        }

        return true;
    }

    /* get current chain ID in hex format
     *
     * @return string
     */
    protected function getCurrentChain()
    {
        return sprintf("%02x", 0xff - $this->current_chain);
    }


    /* get current pipe ID in hex format
     *
     * @return string
     */
    protected function getCurrentPipe()
    {
        return sprintf("%02x", 0xff - $this->current_pipe);
    }

    /* get current class ID in hex format
     *
     * @return string
     */
    protected function getCurrentClass()
    {
        return sprintf("%02x", 0xff - $this->current_class);
    }

    protected function getCurrentFilter()
    {
        return sprintf("%02x", 0xff - $this->current_filter);
    }

    /* set the actually tc handle ID for a chain */
    protected function setChainID(&$if, $chain_idx, $chain_tc_id)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($chain_idx) || empty($chain_idx) || !is_numeric($chain_idx)) {
            static::raiseError(__METHOD__ .'(), $chain_idx parameter is invalid!');
            return false;
        }

        if (!isset($chain_tc_id) || empty($chain_tc_id) || !is_string($chain_tc_id)) {
            static::raiseError(__METHOD__ .'(), $chain_tc_id parameter is invalid!');
            return false;
        }

        global $ms;

        try {
            $tc_id = new \MasterShaper\Models\TcIdModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load TcIdModel!');
            return false;
        }

        if (!$tc_id->setFieldValue('pipe_idx', 0)) {
            static::raiseError(get_class($tc_id) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tc_id->setFieldValue('chain_idx', $chain_idx)) {
            static::raiseError(get_class($tc_id) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tc_id->setFieldValue('if', $if->getInterfaceName())) {
            static::raiseError(get_class($tc_id) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tc_id->setFieldValue('tc_id', $chain_tc_id)) {
            static::raiseError(get_class($tc_id) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tc_id->setFieldValue('host_idx', $this->getHost())) {
            static::raiseError(get_class($tc_id) .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    /* set the actually tc handle ID for a pipe */
    protected function setPipeID(&$if, $pipe_idx, $chain_idx, $pipe_tc_id)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($pipe_idx) || empty($pipe_idx) || !is_numeric($pipe_idx)) {
            static::raiseError(__METHOD__ .'(), $pipe_idx parameter is invalid!');
            return false;
        }

        if (!isset($chain_idx) || empty($chain_idx) || !is_numeric($chain_idx)) {
            static::raiseError(__METHOD__ .'(), $chain_tc_id parameter is invalid!');
            return false;
        }

        if (!isset($pipe_tc_id) || empty($pipe_tc_id) || !is_string($pipe_tc_id)) {
            static::raiseError(__METHOD__ .'(), $pipe_tc_id parameter is invalid!');
            return false;
        }

        try {
            $tcid = new \MasterShaper\Models\TcIdModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load TcIdModel!', false, $e);
            return false;
        }

        if (!$tcid->setFieldValue('pipe_idx', $pipe_idx)) {
            static::raiseError(get_class($tcid) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tcid->setFieldValue('chain_idx', $chain_idx)) {
            static::raiseError(get_class($tcid) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tcid->setFieldValue('if', $if->getInterfaceName())) {
            static::raiseError(get_class($tcid) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tcid->setFieldValue('tc_id', $pipe_tc_id)) {
            static::raiseError(get_class($tcid) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tcid->setFieldValue('host_idx', $this->getHost())) {
            static::raiseError(get_class($tcid) .'::setFieldValue() returned false!');
            return false;
        }

        if (!$tcid->save()) {
            static::raiseError(get_class($tcid) .'::save() returned false!');
            return false;
        }

        return true;
    }

    /* create IP/host matching filters */
    protected function addChainFilter(&$if, $is_gre, $parent, $chain = null, $params = null, $chain_direction = null)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($is_gre) || !is_bool($is_gre)) {
            static::raiseError(__METHOD__ .'(), $is_gre parameter is invalid!');
            return false;
        }

        if (!isset($parent) ||
            empty($parent) ||
            !is_string($parent)
        ) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (isset($chain) && (
            empty($chain) ||
            !is_object($chain) ||
            !is_a($chain, 'MasterShaper\Models\ChainModel')
        )) {
            static::raiseError(__METHOD__ .'(), $chain parameter is invalid!');
            return false;
        }

        if (isset($params) && (
            empty($params) ||
            !is_string($params)
        )) {
            static::raiseError(__METHOD__ .'(), $params parameter is invalid!');
            return false;
        }

        if (!isset($chain_direction) ||
            empty($chain_direction) ||
            !is_string($chain_direction)
        ) {
            static::raiseError(__METHOD__ .'(), $chain_direction parameter is invalid!');
            return false;
        }

        global $ms;

        // if hash key filter is in place, we do not need to load any chain filters. this is matched by the hash key
        //$if->addRule(static::$tc_bin ." filter add dev ". $this->getInterfaceName() ." parent ". $parent ." handle ". $chain_hex_id .": u32 divisor 256");
        if ($ms->hasOption("use_hashkey") &&
            $ms->getOption("use_hashkey") == 'Y'
        ) {
            if ($ms->hasOption("ack_sl") &&
                $ms->getOption("ack_sl") != 0
            ) {
                $this->addAckFilter($if, $is_gre, "1:0", "1:2", $this->getChainHashKey($chain, $chain_direction));
            }
            return false;
        }

        switch ($ms->getOption("filter", true)) {
            default:
            case 'tc':
                if ($chain_direction == "out") {
                    if (!$chain->swapTargets()) {
                        static::raiseError(get_class($chain) .'::swapTargets() returned false!');
                        return false;
                    }
                }

                // matching on source address, but not on destination
                if ($chain->hasSourceTarget() && !$chain->hasDestinationTarget()) {
                    if (($hosts = $this->getTargetHosts($chain->getSourceTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($hosts as $host) {
                        if (!$this->checkIfMac($host)) {
                            if (isset($is_gre) && $is_gre === true) {
                                $hex_host = $this->convertIpToHex($host);
                                switch ($chain->chain_direction) {
                                    case static::UNIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36 flowid ". $params;
                                        break;
                                    case static::BIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36 flowid ". $params;
                                        break;
                                }
                            } else {
                                switch ($chain->chain_direction) {
                                    case static::UNIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match ip src ". $host ." flowid ". $params;
                                        break;
                                    case static::BIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match ip src ". $host ." flowid ". $params;
                                        break;
                                }
                            }
                        } else {
                            if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host)) {
                                list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                            } else {
                                list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);
                            }

                            $filter = "protocol all prio 2 u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ."  0xffffffff at -8 flowid ". $params;
                        }

                        if (!$this->addFilter(
                            $if,
                            $parent,
                            $filter
                        )) {
                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                            return false;
                        }
                    }
                // matching on destination address, but not on source
                } elseif (!$chain->hasSourceTarget() && $chain->hasDestinationTarget()) {
                    if (($hosts = $this->getTargetHosts($chain->getDestinationTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($hosts as $host) {
                        if (!$this->checkIfMac($host)) {
                            if (isset($is_gre) && $is_gre === true) {
                                $hex_host = $this->convertIpToHex($host);
                                switch ($chain->chain_direction) {
                                    case static::UNIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40 flowid ". $params;
                                        break;
                                    case static::BIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40 flowid ". $params;
                                        break;
                                }
                            } else {
                                switch ($chain->chain_direction) {
                                    case static::UNIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match ip dst ". $host ." flowid ". $params;
                                        break;
                                    case static::BIDIRECTIONAL:
                                        $filter = "protocol all prio 2 u32 match ip dst ". $host ." flowid ". $params;
                                        break;
                                }
                            }
                        } else {
                            if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host)) {
                                list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                            } else {
                                list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);
                            }

                            $filter = "protocol all prio 2 u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 flowid ". $params;
                        }

                        if (!$this->addFilter(
                            $if,
                            $parent,
                            $filter
                        )) {
                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                            return false;
                        }
                    }
                // matching on both, source and destination address
                } elseif ($chain->hasSourceTarget() && $chain->hasDestinationTarget()) {
                    if (($src_hosts = $this->getTargetHosts($chain->getSourceTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($src_hosts as $src_host) {
                        if (!$this->checkIfMac($src_host)) {
                            if (isset($is_gre) && $is_gre === true) {
                                $hex_host = $this->convertIpToHex($src_host);
                                $filter = "protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36 ";
                            } else {
                                $filter = "protocol all prio 2 u32 match ip src ". $src_host ." ";
                            }
                        } else {
                            if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $src_host)) {
                                list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $src_host);
                            } else {
                                list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $src_host);
                            }
                            $filter = "protocol all prio 2 u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ";
                        }

                        if (($dst_hosts = $this->getTargetHosts($chain->getDestinationTarget())) === false) {
                            static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                            return false;
                        }

                        $filters = array();
                        foreach ($dst_hosts as $dst_host) {
                            $tmp_filter = $filter;

                            if (!$this->checkIfMac($dst_host)) {
                                if (isset($is_gre) && $is_gre === true) {
                                    $hex_host = $this->convertIpToHex($dst_host);
                                    $tmp_filter.= "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40 flowid ". $params;
                                } else {
                                    $tmp_filter.= "match ip dst ". $dst_host ." flowid ". $params;
                                }
                            } else {
                                if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $dst_host)) {
                                    list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $dst_host);
                                } else {
                                    list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $dst_host);
                                }

                                $tmp_filter.= "match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 flowid ". $params;
                            }

                            array_push($filters, $tmp_filter);
                        }

                        foreach ($filters as $filter) {
                            // unidirectional or bidirectional matches
                            switch ($chain->chain_direction) {
                                case static::UNIDIRECTIONAL:
                                case static::BIDIRECTIONAL:
                                    if (!$this->addFilter(
                                        $if,
                                        $parent,
                                        $filter
                                    )) {
                                        static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                        return false;
                                    }
                                    break;
                            }
                        }
                    }
                }
                break;

            case 'ipt':
                if ($ms->hasOption("msmode") &&
                    $ms->getOption("msmode") == "router"
                ) {
                    $string = static::$ipt_bin ." -t mangle -A ms-forward -o ". $this->getInterfaceName();
                } elseif ($ms->hasOption("msmode") &&
                    $ms->getOption("msmode") == "bridge"
                ) {
                    $string = static::$ipt_bin ." -t mangle -A ms-forward -m physdev --physdev-in ". $params5;
                }

                if ($chain_direction == "out") {
                    if (!$chain->swapTargets()) {
                        static::raiseError(get_class($chain) .'::swapTargets() returned false!');
                        return false;
                    }
                }

                if ($chain->hasSourceTarget() && $chain->hasDestinationTarget()) {
                    if (($hosts = $this->getTargetHosts($chain->getSourceTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($hosts as $host) {
                        if ($this->checkIfMac($host)) {
                            $if->addRule($string ." -m mac --mac-source ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                            $if->addRule($string ." -m mac --mac-source ". $host ." -j RETURN");
                        } else {
                            if (strstr($host, "-") === false) {
                                $if->addRule($string ." -s ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                                $if->addRule($string ." -s ". $host ." -j RETURN");
                            } else {
                                $if->addRule($string ." -m iprange --src-range ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                                $if->addRule($string ." -m iprange --src-range ". $host ." -j RETURN");
                            }
                        }
                    }
                } elseif (!$chain->hasSourceTarget() && $chain->hasDestinationTarget()) {
                    if (($hosts = $this->getTargetHosts($chain->getDestinationTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($hosts as $host) {
                        if ($this->checkIfMac($host)) {
                            $if->addRule($string ." -m mac --mac-source ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                            $if->addRule($string ." -m mac --mac-source ". $host ." -j RETURN");
                        } else {
                            if (strstr($host, "-") === false) {
                                $if->addRule($string ." -d ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                                $if->addRule($string ." -d ". $host ." -j RETURN");
                            } else {
                                $if->addRule($string ." -m iprange --dst-range ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                                $if->addRule($string ." -m iprange --dst-range ". $host ." -j RETURN");
                            }
                        }
                    }
                } elseif ($chain->hasSourceTarget() && $chain->hasDestinationTarget()) {
                    if (($src_hosts = $this->getTargetHosts($chain->getSourceTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    if (($dst_hosts = $this->getTargetHosts($chain->getDestinationTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($src_hosts as $src_host) {
                        if (!$this->checkIfMac($src_host)) {
                            foreach ($dst_hosts as $dst_host) {
                                if ($this->checkIfMac($dst_host)) {
                                    $if->addRule($string ." -m mac --mac-source ". $src_host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                                    $if->addRule($string ." -m mac --mac-source ". $dst_host ." -j RETURN");
                                } else {
                                    if (strstr($host, "-") === false) {
                                        $if->addRule($string ." -s ". $src_host ." -d ". $dst_host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                                        $if->addRule($string ." -s ". $src_host ." -d ". $dst_host ." -j RETURN");
                                    } else {
                                        $if->addRule($string ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $params));
                                        $if->addRule($string ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." -j RETURN");
                                    }
                                }
                            }
                        }
                    }
                }
                break;
        }

        return true;
    }

    /**
     * return all host addresses
     *
     * this function returns a array of host addresses for a target definition
     */
    protected function getTargetHosts($target_idx)
    {
        global $ms, $cache;

        if (!isset($target_idx) || empty($target_idx) || !is_numeric($target_idx)) {
            static::raiseError(__METHOD__ .'(), $target_idx is invalid!');
            return false;
        }

        if (!$cache->has("target_${target_idx}")) {
            try {
                $target = new \MasterShaper\Models\TargetModel(array(
                    FIELD_IDX => $target_idx,
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load TargetModel!');
                return false;
            }
            if (!$cache->add($target, "target_${target_idx}")) {
                static::raiseError(get_class($cache) .'::add() returned false!');
                return false;
            }
        } else {
            if (($target = $cache->get("target_${target_idx}")) === false) {
                static::raiseError(get_class($cache) .'::del() returned false!');
                return false;
            }
            if (!$target->resetFields()) {
                static::raiseError(get_class($target) .'::resetFields() returned false!');
                return false;
            }
        }

        $hosts = array();

        switch ($target->getMatch()) {
            case 'IP':
                /* for tc-filter we need to need to resolve a IP range
                   iptables will use the IPRANGE match for this
                 */
                if ($ms->hasOption("filter") &&
                    $ms->getOption("filter") == "tc"
                ) {
                    if (strstr($target->getIp(), "-") !== false) {
                        list($host1, $host2) = preg_split("/-/", $target->getIp());
                        $host1 = ip2long($host1);
                        $host2 = ip2long($host2);

                        for ($i = $host1; $i <= $host2; $i++) {
                            array_push($hosts, long2ip($i));
                        }
                    } else {
                        array_push($hosts, $target->getIp());
                    }
                } else {
                    array_push($hosts, $target->getIp());
                }
                break;

            case 'MAC':
                $mac = str_replace("-", ":", $target->getMac());
                list($one, $two, $three, $four, $five, $six) = preg_split("/:/", $mac);
                $mac = sprintf("%02s:%02s:%02s:%02s:%02s:%02s", $one, $two, $three, $four, $five, $six);
                array_push($hosts, $mac);
                break;

            case 'GROUP':
                if (!$cache->has("atgs")) {
                    try {
                        $atg = new \MasterShaper\Models\AssignTargetToGroupsModel;
                    } catch (\Exception $e) {
                        static::raiseError(__METHOD__ .'(), failed to load AssignTargetsToGroupsModel!', false, $e);
                        return false;
                    }
                    if (!$cache->add($atg, "atgs")) {
                        static::raiseError(get_class($cache) .'::add() returned false!');
                        return false;
                    }
                } else {
                    if (($atg = $cache->get("atgs")) === false) {
                        static::raiseError(get_class($cache) .'::get() returned false!');
                        return false;
                    }
                }

                if (!$atg->hasItems()) {
                    break;
                }

                $items_filter = array(
                    'group_idx' => $target->getIdx(),
                );

                foreach ($atg->getItems(null, null, $items_filter) as $target) {
                    if (!$target->hasTarget()) {
                        continue;
                    }
                    if (($target_idx = $target->getTarget()) === false) {
                        static::raiseError(get_class($target) .'::getTarget() returned false!');
                        return false;
                    }
                    if (($members = $this->getTargetHosts($target_idx)) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($members as $member) {
                        array_push($hosts, $member);
                    }
                }
                break;
        }

        return $hosts;
    }

    /**
     * check if MAC address
     *
     * check if specified host consists a MAC address.
     * @return true, false
     */
    protected function checkIfMac($host)
    {
        if (!isset($host) ||
            empty($host) ||
            !is_string($host)
        ) {
            static::raiseError(__METHOD__ .'(), $host parameter is invalid!');
            return false;
        }

        if (!preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host) &&
            !preg_match("/(.*)-(.*)-(.*)-(.*)-(.*)-(.*)/", $host)
        ) {
            return false;
        }

        return true;
    }

    /* build ruleset for incoming pipes */
    protected function buildPipes(&$if, $is_gre, $chain_idx, $my_parent, $chain_direction, $chain_sl, $chain_hex_id = null)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($is_gre) || !is_bool($is_gre)) {
            static::raiseError(__METHOD__ .'(), $is_gre parameter is invalid!');
            return false;
        }

        if (!isset($chain_idx) ||
            empty($chain_idx) ||
            !is_numeric($chain_idx)
        ) {
            static::raiseError(__METHOD__ .'(), $chain_idx parameter is invalid!');
            return false;
        }

        if (!isset($my_parent) ||
            empty($my_parent) ||
            !is_string($my_parent)
        ) {
            static::raiseError(__METHOD__ .'(), $my_parent parameter is invalid!');
            return false;
        }

        if (!isset($chain_direction) ||
            empty($chain_direction) ||
            !is_string($chain_direction)
        ) {
            static::raiseError(__METHOD__ .'(), $chain_direction parameter is invalid!');
            return false;
        }

        if (!isset($chain_sl) ||
            empty($chain_sl) ||
            !is_object($chain_sl) ||
            !is_a($chain_sl, 'MasterShaper\Models\ServiceLevelModel')
        ) {
            static::raiseError(__METHOD__ .'(), $chain_sl parameter is invalid!');
            return false;
        }

        if (isset($chain_hex_id) &&
            (empty($chain_hex_id) || !is_string($chain_hex_id))
        ) {
            static::raiseError(__METHOD__ .'(), $chain_hex_id parameter is invalid!');
            return false;
        }

        global $cache;

        if (!$cache->has("apcs")) {
            try {
                $chain = new \MasterShaper\Models\AssignPipeToChainsModel(
                    array(),
                    array('pipe_pos' => 'ASC')
                );
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load AssignPipeToChainsModel model!', false, $e);
                return false;
            }
            if (!$cache->add($chain, "apcs")) {
                static::raiseError(get_class($cache) .'::add() returned false!');
                return false;
            }
        } else {
            if (($chain = $cache->get("apcs")) === false) {
                static::raiseError(get_class($cache) .'::get() returned false!');
                return false;
            }
        }

        if (!$chain->hasItems()) {
            return true;
        }

        /* get all active pipes for this chain */
        /*$sth = $db->db_prepare(
            "SELECT
            p.pipe_idx,
            p.pipe_active,
            apc.apc_sl_idx,
            apc.apc_pipe_active
            FROM
            ". MYSQL_PREFIX ."pipes p
            INNER JOIN
            ". MYSQL_PREFIX ."assign_pipes_to_chains apc
            ON
            p.pipe_idx=apc.apc_pipe_idx
            WHERE
            p.pipe_active='Y'
            AND
            apc.apc_chain_idx LIKE ?
            ORDER BY
            apc.apc_pipe_pos ASC"
        );

        $db->db_execute($sth, array(
                    $chain_idx
                    ));

        while ($active_pipe = $sth->fetch()) {*/

        $items_filter = array(
            'chain_idx' => $chain_idx,
            'pipe_active' => 'Y',
        );

        foreach ($chain->getItems(null, null, $items_filter) as $apc) {
            // if pipe has been locally (for this chain) disabled, we can skip it.
            if (!$apc->isPipeActive()) {
                continue;
            }

            if (($pipe = $apc->getPipe(true)) === false) {
                static::raiseError(get_class($apc) .'::getPipe() returned false!');
                return false;
            }

            $this->current_pipe+= 0x1;

            $my_id = sprintf(
                "1:%s%s",
                $this->getCurrentChain(),
                $this->getCurrentPipe()
            );

            $if->addRuleComment("pipe ". $pipe->getName());

            // check if pipes original service level has been overruled locally
            // for this chain. if so, we proceed with the local service level.
            if (isset($active_pipe->apc_sl_idx) && !empty($active_pipe->apc_sl_idx)) {
                if (!$pipe->setServiceLevel($active_pipe->apc_sl_idx)) {
                    static::raiseError(get_class($pipe) .'::setServiceLevel() returned false!');
                    return false;
                }
            }

            if (($sl = $pipe->getServiceLevel(true)) === false) {
                static::raiseError(get_class($pipe) .'::getServiceLevel() returned false!');
                return false;
            }

            /* add a new class for this pipe */
            if (!$this->addClassifier($if, $my_parent, $my_id, $sl, $chain_direction, $chain_sl)) {
                static::raiseError(__CLASS__ .'::addClassifier() returned false!');
                return false;
            }

            if (!$this->addSubQdisc($if, $this->getCurrentChain() . $this->getCurrentPipe() .":", $my_id, $sl)) {
                static::raiseError(__CLASS__ .'::addSubQdisc() returned false!');
                return false;
            }

            if (!$this->setPipeId(
                $if,
                $pipe->getIdx(),
                $chain_idx,
                $my_id
            )) {
                static::raiseError(__CLASS__ .'::setPipeId() returned false!');
                return false;
            }

            if (!$cache->has("afp")) {
                try {
                    $afp = new \MasterShaper\Models\AssignFilterToPipesModel;
                } catch (\Exception $e) {
                    static::raiseError(__METHOD__ .'(), failed to load AssignFilterToPipesModel!', false, $e);
                    return false;
                }
                if (!$cache->add($afp, "afp")) {
                    static::raiseError(get_class($cache) .'::add() returned false!');
                    return false;
                }
            } else {
                if (($afp = $cache->get("afp")) === false) {
                    static::raiseError(get_class($cache) .'::get() returned false!');
                    return false;
                }
            }

            if (!$afp->hasItems()) {
                if (!$this->addPipeFilter($my_parent, null, $my_id, $pipe, $chain_direction, $chain_hex_id)) {
                    static::raiseError(__CLASS__ .'::addPipeFilter() returned false!');
                    return false;
                }
                continue;
            }

            $items_filter = array(
                'pipe_idx' => $pipe->getIdx()
            );

            foreach ($afp->getItems(null, null, $items_filter) as $afp_item) {
                if (($filter = $afp_item->getFilter(true)) === false) {
                    static::raiseError(get_class($afp_item) .'::getFilter() returned false!');
                    return false;
                }
                if (!$this->addPipeFilter($my_parent, $filter, $my_id, $pipe, $chain_direction, $chain_hex_id)) {
                    static::raiseError(__CLASS__ .'::addPipeFilter() returned false!');
                    return false;
                }
            }
        }

        return true;
    }

    /* Adds qdisc at the end of class for final queuing mechanism */
    protected function addSubQdisc(&$if, $child, $parent, &$sl = null)
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
                if (($params = $this->getEsfqParams($sl)) === false) {
                    static::raiseError(__CLASS__ .'::getEsfqParams() returned false!');
                    return false;
                }
                break;

            case 'HFSC':
                $type = 'hfsc';
                $params = array();
                break;

            case 'NETEM':
                $type = 'netem';
                if (($params = $this->getNetemParams($sl)) === false) {
                    static::raiseError(__CLASS__ .'::getNetemParams() returned false!');
                    return false;
                }
                break;
        }

        if (!$this->addQdisc(
            $if,
            $child,
            $type,
            $params,
            $parent
        )) {
            static::raiseError(__CLASS__ .'::addQdisc() returned false!');
            return false;
        }

        return true;
    }

    /**
     * Generate code to add a pipe filter
     *
     * This function generates the tc/iptables code to filter traffic into a pipe
     * @param string $parent
     * @param string $option
     * @param Filter $filter
     * @param string $my_id
     * @param Pipe $pipe
     * @param string $chain_direction
     */
    protected function addPipeFilter($parent, $filter, $my_id, $pipe, $chain_direction, $chain_hex_id = null)
    {
        if (!isset($parent) ||
            empty($parent) ||
            !is_string($parent)
        ) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (!isset($filter) ||
            empty($filter) ||
            !is_object($filter) ||
            !is_a($filter, 'MasterShaper\Models\FilterModel')
        ) {
            static::raiseError(__METHOD__ .'(), $filter parameter is invalid!');
            return false;
        }

        if (!isset($my_id) ||
            empty($my_id) ||
            !is_string($my_id)
        ) {
            static::raiseError(__METHOD__ .'(), $my_id parameter is invalid!');
            return false;
        }

        if (!isset($pipe) ||
            empty($pipe) ||
            !is_object($pipe) ||
            !is_a($pipe, 'MasterShaper\Models\PipeModel')
        ) {
            static::raiseError(__METHOD__ .'(), $pipe parameter is invalid!');
            return false;
        }

        if (!isset($chain_direction) ||
            empty($chain_direction) ||
            !is_string($chain_direction)
        ) {
            static::raiseError(__METHOD__ .'(), $chain_direction parameter is invalid!');
            return false;
        }

        if (!isset($chain_direction) ||
            empty($chain_direction) ||
            !is_string($chain_direction)
        ) {
            static::raiseError(__METHOD__ .'(), $chain_direction parameter is invalid!');
            return false;
        }

        if (isset($chain_hex_id) &&
            (empty($chain_hex_id) || !is_string($chain_hex_id))
        ) {
            static::raiseError(__METHOD__ .'(), $chain_hex_id parameter is invalid!');
            return false;
        }


        global $ms;

        /* If this filter matches bidirectional, src & dst target has to be swapped */
        if ($pipe->hasDirection() && $pipe->getDirection() == static::BIDIRECTIONAL && $chain_direction == "out") {
            if (!$pipe->swapTargets()) {
                static::raiseError(get_class($pipe) .'::swapTargets() returned false!');
                return false;
            }
        }

        $tmp_str   = "";
        $tmp_array = array();

        switch ($ms->getOption("filter", true)) {
            default:
            case 'tc':
                if (!$ms->hasOption("use_hashkey") ||
                    $ms->getOption("use_hashkey") != 'Y'
                ) {
                    $match = "protocol all prio 1 [HOST_DEFS] ";
                } else {
                    $parent = "1:0";
                    $match = "protocol all prio 2 u32 ht 10:". $chain_hex_id ." [HOST_DEFS] ";
                }

                if (isset($filter)) {
                    if (isset($is_gre) && $is_gre === true) {
                        if ($filter->hasTos()) {
                            $match.= "match u8 ". sprintf("%02x", $filter->getTos()) ." 0xff at 25 ";
                        }
                        if ($filter->hasDscp()) {
                            $match.= "match u8 0x". $this->getDscpHexValue($filter->getDscp()) ." 0xfc at 25 ";
                        }
                    } else {
                        if ($filter->hasTos()) {
                            $match.= "match ip tos ". $filter->getTos() ." 0xff ";
                        }
                        if ($filter->hasDscp()) {
                            $match.= "match u8 0x". $this->getDscpHexValue($filter->getDscp()) ." 0xfc at 1 ";
                        }
                    }
                }

                /* filter matches a specific network protocol */
                if (isset($filter) && $filter->hasProtocol()) {
                    if (($proto = $filter->getProtocol(true)) === false) {
                        static::raiseError(get_class($filter) .'::getProtocol() returned false!');
                        return false;
                    }
                    if (is_object($proto) && $proto->hasNumber() && ($proto_num = $proto->getNumber()) === false) {
                        static::raiseError(get_class($proto) .'::getNumber() returned false!');
                        return false;
                    } else {
                        $proto_num = 0;
                    }
                    switch (intval($proto_num)) {
                        /* TCP */
                        case 6:
                            /* UDP */
                        case 17:
                            /* IP */
                        case 4:
                            if ($filter->hasPorts()) {
                                if (($ports = $filter->getPorts(true)) === false) {
                                    static::raiseError(get_class($filter) .'::getPorts() returned false!');
                                    return false;
                                }
                                if (isset($is_gre) && $is_gre === true) {
                                    $match.= "match u16 ";
                                } else {
                                    $match.= "match ip ";
                                }
                                $str_ports = "";
                                $cnt_ports = 0;

                                foreach ($ports as $port) {
                                    if ($port->hasNumber()) {
                                        continue;
                                    }
                                    if (($dst_ports = $port->getNumber()) === false) {
                                        static::raiseError(get_class($port) .'::getNumber() returned false!');
                                        return false;
                                    }
                                    foreach ($dst_ports as $dst_port) {
                                        if (isset($is_gre) && $is_gre === true) {
                                            $port_hex = $this->convertPortToHex($dst_port);
                                            $tmp_str = $match ." 0x". $port_hex ." 0xffff at [DIRECTION]";

                                            switch ($pipe->getDirection()) {
                                                case static::UNIDIRECTIONAL:
                                                    array_push($tmp_array, str_replace("[DIRECTION]", "46", $tmp_str));
                                                    break;
                                                case static::BIDIRECTIONAL:
                                                    array_push($tmp_array, str_replace("[DIRECTION]", "44", $tmp_str));
                                                    array_push($tmp_array, str_replace("[DIRECTION]", "46", $tmp_str));
                                                    break;
                                            }
                                        } else {
                                            $tmp_str = $match ." [DIRECTION] ". $dst_port ." 0xffff ";

                                            switch ($pipe->getDirection()) {
                                                case static::UNIDIRECTIONAL:
                                                    array_push($tmp_array, str_replace("[DIRECTION]", "dport", $tmp_str));
                                                    break;
                                                case static::BIDIRECTIONAL:
                                                    array_push($tmp_array, str_replace("[DIRECTION]", "dport", $tmp_str));
                                                    array_push($tmp_array, str_replace("[DIRECTION]", "sport", $tmp_str));
                                                    break;
                                            }
                                        }
                                    }
                                }
                                // we break here if there where ports selected.
                                // otherwise we go to the default: clause
                                // to match on IP, TCP and UDP protocols only.
                                break;
                            }
                            // there is no break; here for IP, TCP and UDP withouts ports. we use
                            // the default clause now!

                        default:
                            if (isset($is_gre) && $is_gre === true) {
                                $proto_hex = $this->convertProtoToHex($proto_num);
                                $match.= "match u8 0x". $proto_hex ." 0xff at 33";
                            } else {
                                $match.= "match ip protocol ". $proto_num ." 0xff ";
                            }
                            array_push($tmp_array, $match);
                            break;
                        case 0:
                        case false:
                            break;
                    }
                } else {
                    array_push($tmp_array, $match);
                }

                if ($pipe->hasSourceTarget() && !$pipe->hasDestinationTarget()) {
                    if (($hosts = $this->getTargetHosts($pipe->getSourceTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($hosts as $host) {
                        if (!$this->checkIfMac($host)) {
                            if (isset($is_gre) && $is_gre === true) {
                                foreach ($tmp_array as $tmp_arr) {
                                    $hex_host = $this->convertIpToHex($host);
                                    switch ($pipe->getDirection()) {
                                        case static::UNIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36", $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            } else {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36", $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            }
                                            break;
                                        case static::BIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                foreach (array(36, 40) as $pos) {
                                                    if (!$this->addFilter(
                                                        $if,
                                                        $parent,
                                                        str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at ". $pos, $tmp_arr) ." flowid ". $my_id
                                                    )) {
                                                        static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                        return false;
                                                    }
                                                }
                                            } else {
                                                foreach (array(36, 40) as $pos) {
                                                    if (!$this->addFilter(
                                                        $if,
                                                        $parent,
                                                        str_replace("[HOST_DEFS]", "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at ". $pos, $tmp_arr) ." flowid ". $my_id
                                                    )) {
                                                        static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                        return false;
                                                    }
                                                }
                                            }
                                            break;
                                    }
                                }
                            } else {
                                foreach ($tmp_array as $tmp_arr) {
                                    switch ($pipe->getDirection()) {
                                        case static::UNIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "u32 match ip src ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            } else {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "match ip src ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            }
                                            break;
                                        case static::BIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "u32 match ip src ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            } else {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "match ip src ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            }
                                            break;
                                    }
                                }
                            }
                        } else {
                            foreach ($tmp_array as $tmp_arr) {
                                if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host)) {
                                    list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                                } else {
                                    list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);
                                }

                                switch ($pipe->getDirection()) {
                                    case static::UNIDIRECTIONAL:
                                        if (!$this->addFilter(
                                            $if,
                                            $parent,
                                            str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ", $tmp_arr) ." flowid ". $my_id
                                        )) {
                                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                            return false;
                                        }
                                        break;
                                    case static::BIDIRECTIONAL:
                                        if (!$this->addFilter(
                                            $if,
                                            $parent,
                                            str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ", $tmp_arr) ." flowid ". $my_id
                                        )) {
                                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                            return false;
                                        }
                                        if (!$this->addFilter(
                                            $if,
                                            $parent,
                                            str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 ", $tmp_arr) ." flowid ". $my_id
                                        )) {
                                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                            return false;
                                        }
                                        break;
                                }
                            }
                        }
                    }
                } elseif (!$pipe->hasSourceTarget() && $pipe->hasDestinationTarget()) {
                    if (($hosts = $this->getTargetHosts($pipe->getDestinationTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($hosts as $host) {
                        if (!$this->checkIfMac($host)) {
                            if (isset($is_gre) && $is_gre === true) {
                                foreach ($tmp_array as $tmp_arr) {
                                    $hex_host = $this->convertIpToHex($host);
                                    switch ($pipe->getDirection()) {
                                        case static::UNIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40", $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            } else {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40", $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            }
                                            break;
                                        case static::BIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40", $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            } else {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40", $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            }
                                            break;
                                    }
                                }
                            } else {
                                foreach ($tmp_array as $tmp_arr) {
                                    switch ($pipe->getDirection()) {
                                        case static::UNIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "u32 match ip dst ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            } else {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "match ip dst ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            }
                                            break;
                                        case static::BIDIRECTIONAL:
                                            if (!$ms->hasOption("use_hashkey") ||
                                                $ms->getOption("use_hashkey") != 'Y'
                                            ) {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "u32 match ip dst ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            } else {
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    str_replace("[HOST_DEFS]", "match ip dst ". $host, $tmp_arr) ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                            }
                                            break;
                                    }
                                }
                            }
                        } else {
                            foreach ($tmp_array as $tmp_arr) {
                                if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host)) {
                                    list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                                } else {
                                    list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);
                                }
                                switch ($pipe->getDirection()) {
                                    case static::UNIDIRECTIONAL:
                                        if (!$this->addFilter(
                                            $if,
                                            $parent,
                                            str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 ", $tmp_arr) ." flowid ". $my_id
                                        )) {
                                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                            return false;
                                        }
                                        break;
                                    case static::BIDIRECTIONAL:
                                        if (!$this->addFilter(
                                            $if,
                                            $parent,
                                            str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 ", $tmp_arr) ." flowid ". $my_id
                                        )) {
                                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                            return false;
                                        }
                                        if (!$this->addFilter(
                                            $if,
                                            $parent,
                                            str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ", $tmp_arr) ." flowid ". $my_id
                                        )) {
                                            static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                            return false;
                                        }
                                        break;
                                }
                            }
                        }
                    }
                } elseif ($pipe->hasSourceTarget() && $pipe->hasDestinationTarget()) {
                    if (($src_hosts = $this->getTargetHosts($pipe->getSourceTarget())) === false) {
                        static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                        return false;
                    }
                    foreach ($src_hosts as $src_host) {
                        if (!$this->checkIfMac($src_host)) {
                            if (isset($is_gre) && $is_gre === true) {
                                $hex_host = $this->convertIpToHex($src_host);
                                $tmp_str = "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at [DIR1] ";
                            } else {
                                if (!$ms->hasOption("use_hashkey") ||
                                    $ms->getOption("use_hashkey") != 'Y'
                                ) {
                                    $tmp_str = "u32 match ip [DIR1] ". $src_host ." ";
                                } else {
                                    $tmp_str = "match ip [DIR1] ". $src_host ." ";
                                }
                            }
                        } else {
                            if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $src_host)) {
                                list($sm1, $sm2, $sm3, $sm4, $sm5, $sm6) = preg_split("/:/", $src_host);
                            } else {
                                list($sm1, $sm2, $sm3, $sm4, $sm5, $sm6) = preg_split("/-/", $src_host);
                            }

                            $tmp_str = "u32 [DIR1] [DIR2]";
                        }

                        if (($dst_hosts = $this->getTargetHosts($pipe->getDestinationTarget())) === false) {
                            static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                            return false;
                        }

                        foreach ($dst_hosts as $dst_host) {
                            if (!$this->checkIfMac($dst_host)) {
                                if (isset($is_gre) && $is_gre === true) {
                                    foreach ($tmp_array as $tmp_arr) {
                                        $hex_host = $this->convertIpToHex($dst_host);
                                        switch ($pipe->getDirection()) {
                                            case static::UNIDIRECTIONAL:
                                                $string = str_replace("[HOST_DEFS]", $tmp_str . "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at [DIR2] ", $tmp_arr);
                                                $string = str_replace("[DIR1]", "36", $string);
                                                $string = str_replace("[DIR2]", "40", $string);
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    $string ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                                break;

                                            case static::BIDIRECTIONAL:
                                                $string = str_replace("[HOST_DEFS]", $tmp_str . "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at [DIR2] ", $tmp_arr);
                                                $string = str_replace("[DIR1]", "36", $string);
                                                $string = str_replace("[DIR2]", "40", $string);
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    $string ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                                break;
                                        }
                                    }
                                } else {
                                    foreach ($tmp_array as $tmp_arr) {
                                        switch ($pipe->getDirection()) {
                                            case static::UNIDIRECTIONAL:
                                                $string = str_replace("[HOST_DEFS]", $tmp_str . "match ip [DIR2] ". $dst_host, $tmp_arr);
                                                $string = str_replace("[DIR1]", "src", $string);
                                                $string = str_replace("[DIR2]", "dst", $string);
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    $string ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                                break;

                                            case static::BIDIRECTIONAL:
                                                $string = str_replace("[HOST_DEFS]", $tmp_str . "match ip [DIR2] ". $dst_host, $tmp_arr);
                                                $string = str_replace("[DIR1]", "src", $string);
                                                $string = str_replace("[DIR2]", "dst", $string);
                                                if (!$this->addFilter(
                                                    $if,
                                                    $parent,
                                                    $string ." flowid ". $my_id
                                                )) {
                                                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                    return false;
                                                }
                                                break;
                                        }
                                    }
                                }
                            } else {
                                if (preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $dst_host)) {
                                    list($dm1, $dm2, $dm3, $dm4, $dm5, $dm6) = preg_split("/:/", $dst_host);
                                } else {
                                    list($dm1, $dm2, $dm3, $dm4, $dm5, $dm6) = preg_split("/-/", $dst_host);
                                }

                                foreach ($tmp_array as $tmp_arr) {
                                    switch ($pipe->getDirection()) {
                                        case static::UNIDIRECTIONAL:
                                            $string = str_replace("[HOST_DEFS]", $tmp_str . "match ip [DIR2] ". $dst_host, $tmp_arr);
                                            $string = str_replace("[DIR1]", "src", $string);
                                            $string = str_replace("[DIR2]", "dst", $string);
                                            if (!$this->addFilter(
                                                $if,
                                                $parent,
                                                $string ." flowid ". $my_id
                                            )) {
                                                static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                return false;
                                            }
                                            break;

                                        case static::BIDIRECTIONAL:
                                            $string = str_replace("[HOST_DEFS]", $tmp_str, $tmp_arr);
                                            $string = str_replace("[DIR1]", "match u16 0x0800 0xffff at -2 match u16 0x". $sm5 . $sm6 ." 0xffff at -4 match u32 0x". $sm1 . $sm2 . $sm3 . $sm4 ." 0xffffffff at -8", $string);
                                            $string = str_replace("[DIR2]", "match u16 0x0800 0xffff at -2 match u32 0x". $dm3 . $dm4 . $dm5 .$dm6 ." 0xffffffff at -12 match u16 0x". $dm1 . $dm2 ." 0xffff at -14", $string);

                                            if (!$this->addFilter(
                                                $if,
                                                $parent,
                                                $string ." flowid ". $my_id
                                            )) {
                                                static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                                return false;
                                            }
                                            break;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    foreach ($tmp_array as $tmp_arr) {
                        if (!$ms->hasOption("use_hashkey") ||
                            $ms->getOption("use_hashkey") != 'Y'
                        ) {
                            if (!$this->addFilter(
                                $if,
                                $parent,
                                str_replace("[HOST_DEFS]", "u32 match u32 0 0", $tmp_arr) ." flowid ". $my_id
                            )) {
                                static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                return false;
                            }
                        } else {
                            if (!$this->addFilter(
                                $if,
                                $parent,
                                str_replace("[HOST_DEFS]", " match u32 0 0", $tmp_arr) ." flowid ". $my_id
                            )) {
                                static::raiseError(__CLASS__ .'::addFilter() returned false!');
                                return false;
                            }
                        }
                    }
                }
                break;
            case 'ipt':
                $match_str = "";
                $cnt= 0;
                $match_ary = array();
                $proto_ary = array();

                // Construct a string with all used ipt matches

                /* If this filter should match on ftp data connections add the rules here */
                if ($filter->isMatchFtpData()) {
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-chain-". $this->getInterfaceName() ."-". $parent ." --match conntrack --ctproto tcp --ctstate RELATED,ESTABLISHED --match helper --helper ftp -j CLASSIFY --set-class ". $my_id);
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-chain-". $this->getInterfaceName() ."-". $parent ." --match conntrack --ctproto tcp --ctstate RELATED,ESTABLISHED --match helper --helper ftp -j RETURN");
                }

                /* If this filter should match on SIP data streans (RTP / RTCP) add the rules here */
                if ($filter->isMatchSip()) {
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-chain-". $this->getInterfaceName() ."-". $parent ." --match conntrack --ctproto udp --ctstate RELATED,ESTABLISHED --match helper --helper sip -j CLASSIFY --set-class ". $my_id);
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-chain-". $this->getInterfaceName() ."-". $parent ." --match conntrack --ctproto udp --ctstate RELATED,ESTABLISHED --match helper --helper sip -j RETURN");
                }

                // filter matches on protocols
                if ($filter->hasProtocol()) {
                    if (($proto = $filter->getProtocol(true)) === false) {
                        static::raiseError(get_class($filter) .'::getProtocol() returned false!');
                        return false;
                    }
                    if (is_object($proto) && $proto->hasNumber() && ($proto_num = $proto->getNumber()) === false) {
                        static::raiseError(get_class($proto) .'::getNumber() returned false!');
                        return false;
                    } else {
                        $proto_num = 0;
                    }
                    switch (intval($proto_num)) {
                        /* IP */
                        case 4:
                            array_push($proto_ary, " -p 6");
                            array_push($proto_ary, " -p 17");
                            break;
                        default:
                            array_push($proto_ary, " -p ". $proto_num);
                            break;
                    }

                    // Select for TCP flags (only valid for TCP protocol)
                    if ($proto_num == 6) {
                        $str_tcpflags = "";
                        if ($filter->isMatchTcpFlagSyn()) {
                            $str_tcpflags.= "SYN,";
                        }
                        if ($filter->isMatchTcpFlagAck()) {
                            $str_tcpflags.= "ACK,";
                        }
                        if ($filter->isMatchTcpFlagFin()) {
                            $str_tcpflags.= "FIN,";
                        }
                        if ($filter->isMatchTcpFlagRst()) {
                            $str_tcpflags.= "RST,";
                        }
                        if ($filter->isMatchTcpFlagUrg()) {
                            $str_tcpflags.= "URG,";
                        }
                        if ($filter->isMatchTcpFlagPsh()) {
                            $str_tcpflags.= "PSH,";
                        }

                        if (!empty($str_tcpflags)) {
                            $match_str.= " --tcp-flags ". substr($str_tcpflags, 0, strlen($str_tcpflags)-1) ." ". substr($str_tcpflags, 0, strlen($str_tcpflags)-1);
                        }
                    }

                    // Get all the used ports for IP, TCP or UDP
                    switch ($proto_num) {
                        case 4:  // IP
                        case 6:  // TCP
                        case 17: // UDP
                            $all_ports = array();
                            $cnt_ports = 0;

                            if ($filter->hasPorts()) {
                                if (($ports = $filter->getPorts(true)) === false) {
                                    static::raiseError(get_class($filter) .'::getPorts() returned false!');
                                    return false;
                                }
                                foreach ($ports as $port) {
                                    if ($port->hasNumber()) {
                                        continue;
                                    }
                                    if (($dst_ports = $port->getNumber()) === false) {
                                        static::raiseError(get_class($port) .'::getNumber() returned false!');
                                        return false;
                                    }
                                    // If this port is definied as range or list get all the single ports
                                    if (!empty($dst_ports)) {
                                        foreach ($dst_ports as $dst_port) {
                                            array_push($all_ports, $dst_port);
                                            $cnt_ports++;
                                        }
                                    }
                                }
                            }
                            break;
                    }
                } else {
                    array_push($proto_ary, "");
                }

                // TOS flags matching
                if ($filter->hasTos()) {
                    $match_str.= " -m tos --tos ". $filter->getTos();
                }

                // DSCP flags matching
                if ($filter->hasDscp()) {
                    $match_str.= " -m dscp --dscp-class ". $filter->getDscp();
                }

                // packet length matching
                if ($filter->hasPacketLength()) {
                    $match_str.= " -m length --length ". $filter->getPacketLength();
                }

                // time range matching
                if ($filter->hasTimeRange()) {
                    $start = strftime("%Y:%m:%d:%H:%M:00", $filter->hasTimeRangeStart());
                    $stop = strftime("%Y:%m:%d:%H:%M:00", $filter->hasTimeRangeStop());
                    $match_str.= " -m time --datestart ". $start ." --datestop ". $stop;
                } else {
                    $str_days = "";
                    if ($filter->hasTimeRangeDayMon()) {
                        $str_days.= "Mon,";
                    }
                    if ($filter->hasTimeRangeDayTue()) {
                        $str_days.= "Tue,";
                    }
                    if ($filter->hasTimeRangeDayWed()) {
                        $str_days.= "Wed,";
                    }
                    if ($filter->hasTimeRangeDayThu()) {
                        $str_days.= "Thu,";
                    }
                    if ($filter->hasTimeRangeDayFri()) {
                        $str_days.= "Fri,";
                    }
                    if ($filter->hasTimeRangeDaySat()) {
                        $str_days.= "Sat,";
                    }
                    if ($filter->hasTimeRangeDaySun()) {
                        $str_days.= "Sun,";
                    }

                    if (!empty($str_days)) {
                        $match_str.= " -m time --days ". substr($str_days, 0, strlen($str_days)-1);
                    }
                }

                // End of match string

                /* All port matches will be matched with the iptables multiport */
                /* (advantage is that src&dst matches can be done with a simple */
                /* --port */

                /* filter matches a specific network protocol */
                if ($filter->hasProtocol()) {
                    if (($proto = $filter->getProtocol(true)) === false) {
                        static::raiseError(get_class($filter) .'::getProtocol() returned false!');
                        return false;
                    }
                    if (is_object($proto) && $proto->hasNumber() && ($proto_num = $proto->getNumber()) === false) {
                        static::raiseError(get_class($proto) .'::getNumber() returned false!');
                        return false;
                    } else {
                        $proto_num = 0;
                    }

                    switch (intval($proto_num)) {
                        /* TCP, UDP or IP */
                        case 4:
                        case 6:
                        case 17:
                            if ($cnt_ports > 0) {
                                switch ($pipe->getDirection()) {
                                    /* 1 = incoming, 3 = both */
                                    case static::UNIDIRECTIONAL:
                                        $match_str.= " -m multiport --dport ";
                                        break;
                                    case static::BIDIRECTIONAL:
                                        $match_str.= " -m multiport --port ";
                                        break;
                                }

                                $j = 0;
                                for ($i = 0; $i <= $cnt_ports; $i++) {
                                    if ($j == 0) {
                                        $tmp_ports = "";
                                    }

                                    if (isset($all_ports[$i])) {
                                        $tmp_ports.= $all_ports[$i] .",";
                                    }

                                    // with one multiport match iptables can max. match 14 single ports
                                    if ($j == 14 || $i == $cnt_ports-1) {
                                        $tmp_str = $match_str . substr($tmp_ports, 0, strlen($tmp_ports)-1);
                                        array_push($match_ary, $tmp_str);
                                        $j = 0;
                                    } else {
                                        $j++;
                                    }
                                }
                            }
                            break;

                        default:
                            array_push($match_ary, $match_str);
                            break;
                    }
                }

                foreach ($match_ary as $match_str) {
                    $ipt_tmpl = static::$ipt_bin ." -t mangle -A ms-chain-". $this->getInterfaceName() ."-". $parent;

                    if ($pipe->hasSourceTarget() && !$pipe->hasDestinationTarget()) {
                        if (($src_hosts = $this->getTargetHosts($pipe->getSourceTarget())) === false) {
                            static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                            return false;
                        }
                        foreach ($src_hosts as $src_host) {
                            foreach ($proto_ary as $proto_str) {
                                if (strstr("-", $src_host) === false) {
                                    $if->addRule($ipt_tmpl ." -s ". $src_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                                    $if->addRule($ipt_tmpl ." -s ". $src_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                                } else {
                                    $if->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                                    $if->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                                }
                            }
                        }
                    } elseif (!$pipe->hasSourceTarget() && $pipe->hasDestinationTarget()) {
                        if (($dst_hosts = $this->getTargetHosts($pipe->getDestinationTarget())) === false) {
                            static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                            return false;
                        }
                        foreach ($dst_hosts as $dst_host) {
                            foreach ($proto_ary as $proto_str) {
                                if (strstr("-", $dst_host) === false) {
                                    $if->addRule($ipt_tmpl ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                                    $if->addRule($ipt_tmpl ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                                } else {
                                    $if->addRule($ipt_tmpl ." -m iprange --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                                    $if->addRule($ipt_tmpl ." -m iprange --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                                }
                            }
                        }
                    } elseif ($pipe->hasSourceTarget() && $pipe->hasDestinationTarget()) {
                        if (($src_hosts = $this->getTargetHosts($pipe->getSourceTarget())) === false) {
                            static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                            return false;
                        }
                        if (($dst_hosts = $this->getTargetHosts($pipe->getDestinationTarget())) === false) {
                            static::raiseError(__CLASS__ .'::getTargetHosts() returned false!');
                            return false;
                        }
                        foreach ($src_hosts as $src_host) {
                            foreach ($dst_hosts as $dst_host) {
                                foreach ($proto_ary as $proto_str) {
                                    if (strstr("-", $dst_host) === false) {
                                        $if->addRule($ipt_tmpl ." -s ". $src_host ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                                        $if->addRule($ipt_tmpl ." -s ". $src_host ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                                    } else {
                                        $if->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                                        $if->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                                    }
                                }
                            }
                        }
                    } elseif (!$pipe->hasSourceTarget() && !$pipe->hasDestinationTarget()) {
                        foreach ($proto_ary as $proto_str) {
                            $if->addRule($ipt_tmpl ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                            $if->addRule($ipt_tmpl ." ". $proto_str ." ". $match_str ." -j RETURN");
                        }
                    }
                }
                break;
        }

        return true;
    }

    protected function getDscpHexValue($dscp_class)
    {
        if (!isset($dscp_class) ||
            empty($dscp_class) ||
            !is_string($dscp_class)
        ) {
            static::raiseError(__METHOD__ .'(), $dscp_classp parameter is invalid!');
            return false;
        }

        /* below we have to shift into 6-bit DSCP class value
           two further bits so we have the actual value we can
           match in the 8-bit long TOS field.
         */
        switch ($dscp_class) {
            // AF11 = 0x0a
            case 'AF11':
                $dscp = 10 << 2;
                break;
            // AF12 = 0x0c
            case 'AF12':
                $dscp = 12 << 2;
                break;
            // AF13 = 0x0e
            case 'AF13':
                $dscp = 14 << 2;
                break;
            // AF21 = 0x12
            case 'AF21':
                $dscp = 18 << 2;
                break;
            // AF22 = 0x14
            case 'AF22':
                $dscp = 20 << 2;
                break;
            // AF23 = 0x16
            case 'AF23':
                $dscp = 22 << 2;
                break;
            // AF31 = 0x1a
            case 'AF31':
                $dscp = 26 << 2;
                break;
            // AF32 = 0x1c
            case 'AF32':
                $dscp = 28 << 2;
                break;
            // AF33 = 0x1e
            case 'AF33':
                $dscp = 30 << 2;
                break;
            // AF41 = 0x22
            case 'AF41':
                $dscp = 34 << 2;
                break;
            // AF42 = 0x24
            case 'AF42':
                $dscp = 36 << 2;
                break;
            // AF43 = 0x26
            case 'AF43':
                $dscp = 38 << 2;
                break;
            // EF = 0x2e
            case 'EF':
                $dscp = 46 << 2;
                break;
            default:
                $dscp = 0 << 2;
                break;
        }

        return sprintf("%02x", $dscp);
    }

    /*
     * get NETEM parameter string
     *
     * this function returns the parameter string for the NETEM qdisc
     *
     * @param mixed $sl
     * @return string
     */
    protected function getNetemParams($sl)
    {
        if (!isset($sl) ||
            empty($sl) ||
            !is_object($sl) ||
            !is_a($sl, 'MasterShaper\Models\ServiceLevelModel')
        ) {
            static::raiseError(__METHOD__ .'(), $sl parameter is invalid!');
            return false;
        }

        if ($sl->hasNetemDelay()) {
            $params.= "delay ". $sl->getNetemDelay() ."ms ";

            if ($sl->hasNetemJitter()) {
                $params.= $sl->getNetemJitter() ."ms ";

                if ($sl->hasNetemRandom()) {
                    $params.= $sl->getNetemRandom() ."% ";
                }
            }

            if ($sl->hasNetemDistribution() && $sl->getNetemDistribution() != "ignore") {
                $params.= "distribution ". $sl->getNetemDistribution() ." ";
            }
        }

        if ($sl->hasNetemLoss()) {
            $params.= "loss ". $sl->getNetemLoss() ."% ";
        }

        if ($sl->hasNetemDuplication()) {
            $params.= "duplicate ". $sl->getNetemDuplication() ."% ";
        }

        if ($sl->hasNetemGap()) {
            $params.= "gap ". $sl->getNetemGap() ." ";
        }

        if ($sl->hasNetemReorderPercentage()) {
            $params.= "reorder ". $sl->hasNetemReorderPercentage() ."% ";
            if ($sl->hasNetemReorderCorrelation()) {
                $params.= $sl->getNetemReorderCorrelation() ."% ";
            }
        }

        return $params;
    }

    /**
     * get ESFQ parameter string
     *
     * this function returns the parameter string for the ESFQ qdisc
     *
     * @param mixed $sl
     * @return string
     */
    protected function getEsfqParams($sl)
    {
        if (!isset($sl) ||
            empty($sl) ||
            !is_object($sl) ||
            !is_a($sl, 'MasterShaper\Models\ServiceLevelModel')
        ) {
            static::raiseError(__METHOD__ .'(), $sl parameter is invalid!');
            return false;
        }

        $params = "";

        if ($sl->hasEsfqPerturb()) {
            $params.= "perturb ". $sl->getEsfqPerturb() ." ";
        }

        if ($sl->hasEsfqLimit()) {
            $params.= "limit ". $sl->getEsfqLimit() ." ";
        }

        if ($sl->hasEsfqDepth()) {
            $params.= "depth ". $sl->getEsfqDepth() ." ";
        }

        if ($sl->hasEsfqDivisor()) {
            $params.= "divisor ". $sl->getEsfqDivisor() ." ";
        }

        if ($sl->hasEsfqHash()) {
            $params.= "hash ". $sl->getEsfqHash();
        }

        return $params;
    }

    protected function addFallbackFilter(&$if, $parent, $filter, $chain_hex_id = null)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        if (!isset($parent) ||
            empty($parent) ||
            !is_string($parent)
        ) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        if (!isset($filter) ||
            empty($filter) ||
            !is_string($filter)
        ) {
            static::raiseError(__METHOD__ .'(), $filter parameter is invalid!');
            return false;
        }

        if (isset($chain_hex_id) &&
            (empty($chain_hex_id) || !is_string($chain_hex_id))
        ) {
            static::raiseError(__METHOD__ .'(), $chain_hex_id parameter is invalid!');
            return false;
        }

        global $ms;

        switch ($ms->getOption("filter", true)) {
            default:
            case 'tc':
                if (!$ms->getOption("use_hashkey") ||
                    $ms->getOption("use_hashkey") != 'Y'
                ) {
                    $match = "protocol all prio 5 u32 match u32 0 0 flowid ". $filter;
                } else {
                    $parent = "1:0";
                    $match = "protocol all prio 5 u32 ht 10:". $chain_hex_id ." match u32 0 0 flowid ". $filter;
                }
                if (!$this->addFilter(
                    $if,
                    $parent,
                    $match
                )) {
                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                    return false;
                }
                break;
            case 'ipt':
                $if->addRule(static::$ipt_bin ." -t mangle -A ms-chain-". $this->getInterfaceName() ."-". $parent ." -j CLASSIFY --set-class ". $filter);
                $if->addRule(static::$ipt_bin ." -t mangle -A ms-chain-". $this->getInterfaceName() ."-". $parent ." -j RETURN");
                break;
        }

        return true;
    }

    protected function addChainMatchallFilter(&$if, $parent, $filter = "")
    {
        global $ms;

        switch ($ms->getOption("filter", true)) {
            default:
            case 'tc':
                if (!$this->addFilter(
                    $if,
                    $parent,
                    " protocol all prio 2 u32 match u32 0 0 classid ". $filter
                )) {
                    static::raiseError(__CLASS__ .'::addFilter() returned false!');
                    return false;
                }
                break;

            case 'ipt':
                if ($ms->hasOption("msmode") && $ms->getOption("msmode") == "router") {
                    //$if->addRule(static::$ipt_bin ." -t mangle -A ms-forward -o ". $this->getInterfaceName() ." -j ms-chain-". $this->getInterfaceName() ."-". $filter);
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-forward -o ". $this->getInterfaceName() ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $filter));
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-forward -o ". $this->getInterfaceName() ." -j RETURN");
                } elseif ($ms->hasOption("msmode") && $ms->getOption("msmode") == "bridge") {
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-forward -m physdev --physdev-in ". $this->getInterfaceName() ." -j MARK --set-mark ". $ms->getConnmarkId($this->getInterfaceId(), $filter));
                    $if->addRule(static::$ipt_bin ." -t mangle -A ms-forward -m physdev --physdev-in ". $this->getInterfaceName() ." -j RETURN");
                }
                break;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
