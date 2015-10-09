<?php

/**
 *
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2015 Andreas Unterkircher <unki@netshadow.net>

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

namespace MasterShaper\Controllers;

define('MSLOG_WARN', 1);
define('MSLOG_INFO', 2);
define('MSLOG_DEBUG', 3);

class MasterShaperController extends DefaultController
{
    const VERSION = "0.3";
    const LOGLEVEL = LOG_WARNING;

    private $cfg;
    private $headers;

    public function __construct($mode = null)
    {
        $GLOBALS['ms'] =& $this;

        try {
            $config = new ConfigController;
        } catch (\Exception $e) {
            $this->raiseError('Failed to load ConfigController!', true);
            return false;
        }
        $GLOBALS['config'] =& $config;

        try {
            $requirements = new RequirementsController;
        } catch (\Exception $e) {
            $this->raiseError('Failed to load RequirementsController!', true);
            return false;
        }
        if (!$requirements->check()) {
            $this->raiseError('Not all requirements are met - please check on your own!', true);
            return false;
        }
        unset($requirements);

        try {
            $db = new DatabaseController;
        } catch (\Exception $e) {
            $this->raiseError('Failed to load DatabaseController!', true);
            return false;
        }

        $GLOBALS['db'] =& $db;

        if (!$this->isCmdline()) {
            try {
                $router = new HttpRouterController;
            } catch (\Exception $e) {
                $this->raiseError('Failed to load HttpRouterController!', true);
                return false;
            }
            $GLOBALS['router'] =& $router;
            $GLOBALS['query'] = $router->getQuery();
            global $query;
        }

        if (isset($query) && isset($query->view) && $query->view == "install") {
            $mode = "install";
        }

        if ($mode != "install" && $this->checkUpgrade()) {
            return false;
        }

        if (isset($mode) and $mode == "install") {

            try {
                $installer = new InstallerController;
            } catch (\Exception $e) {
                $this->raiseError('Failed to load InstallerController!');
                return false;
            }

            if (!$installer->setup()) {
                exit(1);
            }

            unset($installer);
            exit(0);
        }

        try {
            $session = new SessionController;
        } catch (\Exception $e) {
            $this->raiseError('Failed to load SessionController!', true);
            return false;
        }

        $GLOBALS['session'] =& $session;

        return true;
    }

    public function startup()
    {
        global $config, $db, $router, $query;

        if (!isset($query->view)) {
            $this->raiseError("Error - parsing request URI hasn't unveiled what to view!");
            return false;
        }

        try {
            $views = new ViewsController;
        } catch (\Exception $e) {
            $this->raiseError('Failed to load ViewsController!');
            return false;
        }

        $GLOBALS['views'] =& $views;

        if (!$views->loadSkeleton()) {
            $this->raiseError('Failed to load page skeleton!');
            return false;
        }

        if (!($page = $views->getMatchingView())) {
            $this->raiseError(get_class($views) .'::getMatchingView() returned false!');
            return false;
        }

        /* page request handled by MS class itself */
        if (isset($page->includefile) && $page->includefile == "[internal]") {
            $this->handlePageRequest();
        }

        /* show login box, if not already logged in */
        if (!$this->isLoggedIn()) {
            /* do not return anything for a RPC call */
            if ($router->isRpcCall()) {
                return false;
            }

            /* return login page */
            if (!($content = $views->load("LoginView"))) {
                $this->raiseError(get_class($views) .'::load() returned false!');
                return false;
            }
            print $content;
            return;
        }

        if ($router->isRpcCall()) {
            if (!$this->rpcHandler()) {
                $this->raiseError("rpcHandler() returned false!");
                return false;
            }
            return true;
        } elseif ($page_name = $views->getViewName($query->view)) {

            if (!$page = $views->load($page_name)) {
                $this->raiseError("ViewController:load() returned false!");
                return false;
            }

            print $page;
            return true;
        }

        if (!$page->includefile || $page->includefile == '[internal]') {
            $page->setPage($rewriter->default_page);
        }

        $fqpn = MASTERSHAPER_BASE ."/class/pages/". $page->includefile;

        if (!file_exists($fqpn)) {
            $this->raiseError("Page not found. Unable to include ". $fqpn);
        }

        if (!is_file($fqpn)) {
            $this->raiseError("No file found at ". $fqpn);
        }

        if (!is_readable($fqpn)) {
            $this->raiseError("Unable to read ". $fqpn);
        }

        include $fqpn;

        $tmpl->show("index.tpl");


        $this->raiseError("Unable to find a view for ". $query->view);
        return false;
    }

    public function __destruct()
    {

    } // __destruct()

    /**
     * load - load ruleset
     *
     * this function invokes the ruleset generator.
     */
    public function load()
    {
        $debug = 0;

        if (!$this->isCmdline()) {
            die("This function must be called from command line!");
        }

        if (isset($_SERVER['argv']) && isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] == 'debug') {
            $debug = 1;
        }

        require_once "class/rules/ruleset.php";
        require_once "class/rules/interface.php";

        $ruleset = new Ruleset;
        $retval = $ruleset->load($debug);

        exit($retval);

    } // load()

    /**
     * unload - unload ruleset
     *
     * this function clears all loaded rules.
     */
    public function unload()
    {
        $debug = 0;

        if (!$this->isCmdline()) {
            die("This function must be called from command line!");
        }

        require_once "class/rules/ruleset.php";
        require_once "class/rules/interface.php";

        $ruleset = new Ruleset;
        $retval = $ruleset->unload();

        exit($retval);

    } // unload()

    /**
     * check login status
     *
     * return true if user is logged in
     * return false if user is not yet logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        global $tmpl;

        /* if authentication is disabled, return true */
        if (!$this->getOption('authentication')) {
            return true;
        }

        if (isset($_SESSION['user_name'])) {
            $tmpl->assign('user_name', $_SESSION['user_name']);
            return true;
        }

        return false;

    } // isLoggedIn()

    /**
     * check login
     */
    private function login()
    {
        global $session;

        if (!isset($_POST['user_name']) || empty($_POST['user_name'])) {
            $this->raiseError(_("Please enter Username and Password."));
        }
        if (!isset($_POST['user_pass']) || empty($_POST['user_pass'])) {
            $this->raiseError(_("Please enter Username and Password."));
        }

        if (!($user = $session->getUserDetails($_POST['user_name']))) {
            $this->raiseError(_("Invalid or inactive User."));
        }

        if ($user->user_pass != md5($_POST['user_pass'])) {
            $this->raiseError(_("Invalid Password."));
        }

        $_SESSION['user_name'] = $_POST['user_name'];
        $_SESSION['user_idx'] = $user->user_idx;

        if (
            !isset($_SESSION['host_profile']) ||
            empty($_SESSION['host_profile']) ||
            !is_numeric($_SESSION['host_profile'])
        ) {
            $_SESSION['host_profile'] = 1;
        }

        return true;

    } // login()

    /**
     * general logout function
     *
     * this function will take care to destroy the active
     * user session to force a logout.
     *
     * @return bool
     */
    private function logout()
    {
        if (!$this->destroySession()) {
            print "failed to destroy user session!";
            return false;
        }

        return true;

    } // logout()

    /**
     * return value of requested setting
     */
    public function getOption($object)
    {
        global $db;

        $result = $db->fetchSingleRow("
                SELECT setting_value
                FROM TABLEPREFIXsettings
                WHERE setting_key like '". $object ."'
                ");

        if (isset($result->setting_value)) {
            return $result->setting_value;
        }

        /* return default options if not set yet */
        if ($object == "filter") {
            return "HTB";
        }

        if ($object == "msmode") {
            return "router";
        }

        if ($object == "authentication") {
            return "Y";
        }

        return "unknown";

    } // getOption()

    /**
     * set value of requested setting
     */
    public function setOption($key, $value)
    {
        global $db;

        $sth = $db->prepare("
                REPLACE INTO TABLEPREFIXsettings (
                    setting_key,
                    setting_value
                    ) VALUES (
                        ?,
                        ?
                        )
                ");

        $db->execute($sth, array(
                    $key,
                    $value
                    ));

        $db->db_sth_free($sth);

    } // setOption()

    /**
     * return true if the current user has the requested
     * permission.
     */
    public function checkPermissions($permission)
    {
        global $db;
        $user = $db->fetchSingleRow("
                SELECT ". $permission ."
                FROM TABLEPREFIXusers
                WHERE user_idx='". $_SESSION['user_idx'] ."'
                ");

        if (isset($user) && isset($user->$permission) && $user->$permission == "Y") {
            return true;
        }

        return false;

    } // checkPermissions()

    /**
     * return human readable priority name
     */
    public function getPriorityName($prio)
    {
        switch($prio) {
            case 0:
                return _("Ignored");
                break;
            case 1:
                return _("Highest");
                break;
            case 2:
                return _("High");
                break;
            case 3:
                return _("Normal");
                break;
            case 4:
                return _("Low");
                break;
            case 5:
                return _("Lowest");
                break;
        }
    } // getPriorityName()

    /**
     * this function validates the provided bandwidth
     * and will return true if correctly specified
     */
    public function validateBandwidth($bw)
    {
        if (!is_numeric($bw)) {
            if (preg_match("/^(\d+)(k|m)$/i", $bw)) {
                return true;
            }
        } else {
            return true;
        }

    } // validateBandwidth()

    /**
     * this function will return the interface name
     * of the interface provided with its index number
     */
    public function getInterfaceName($if_idx)
    {
        /* we are going on to handle positive indexes */
        if ($if_idx <= 0) {
            return;
        }

        if (!$if = new Network_Interface($if_idx)) {
            return false;
        }

        return $if->if_name;

    } // getInterfaceName()

    public function getYearList($current = "")
    {
        $string = "";
        for ($i = date("Y"); $i <= date("Y")+2; $i++) {
            $string.= "<option value=\"". $i ."\"";
            if ($i == date("Y", (int) $current)) {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $i ."</option>";
        }
        return $string;

    } // getYearList()

    public function getMonthList($current = "")
    {
        $string = "";
        for ($i = 1; $i <= 12; $i++) {
            $string.= "<option value=\"". $i ."\"";
            if ($i == date("n", (int) $current)) {
                $string.= " selected=\"selected\"";
            }
            if (date("m") == $i && $current == "") {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $i ."</option>";
        }
        return $string;

    } // getMonthList()

    public function getDayList($current = "")
    {
        $string = "";
        for ($i = 1; $i <= 31; $i++) {
            $string.= "<option value=\"". $i ."\"";
            if ($i == date("d", (int) $current)) {
                $string.= " selected=\"selected\"";
            }
            if (date("d") == $i && $current == "") {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $i ."</option>";
        }
        return $string;

    } // getDayList()

    public function getHourList($current = "")
    {
        $string = "";
        for ($i = 0; $i <= 23; $i++) {
            $string.= "<option value=\"". $i ."\"";
            if ($i == date("H", (int) $current)) {
                $string.= " selected=\"selected\"";
            }
            if (date("H") == $i && $current == "") {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". sprintf("%02d", $i) ."</option>";
        }
        return $string;

    } // getHourList()

    public function getMinuteList($current = "")
    {
        $string = "";
        for ($i = 0; $i <= 59; $i++) {
            $string.= "<option value=\"". $i ."\"";
            if ($i == date("i", (int) $current)) {
                $string.= " selected=\"selected\"";
            }
            if (date("i") == $i && $current == "") {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". sprintf("%02d", $i)  ."</option>";
        }
        return $string;

    } // getMinuteList()

    /**
     * returns IANA protocol number
     *
     * this function returns the IANA protocol number
     * for the specified database entry in protocol table
     */
    public function getProtocolNumberById($proto_idx)
    {
        if (!$proto = new Protocol($proto_idx)) {
            return false;
        }

        return $proto->proto_number;

    } // getProtocolNumberById()

    /**
     * return IANA protocol name
     *
     * this function returns the IANA protocol name
     * for the specified database entry in the protocol table
     */
    public function getProtocolNameById($proto_idx)
    {
        if (!$proto = new Protocol($proto_idx)) {
            return false;
        }

        return $proto->proto_name;

    } // getProtocolNameById()

    /**
     * return kbit/s in integer value
     *
     * this function will transform user entered bandwidth
     * values (kilobit, megabit) into integer values).
     */
    public function getKbit($bw)
    {
        if (preg_match("/^(\d+)k$/i", $bw)) {
            return preg_replace("/k/i", "", $bw);
        }
        if (preg_match("/^(\d+)m$/i", $bw)) {
            return (preg_replace("/m/i", "", $bw) * 1024);
        }

        return $bw;

    } // getKbit

    /**
     * get service level information
     *
     * this function will return all details of the requested
     * service level.
     *
     * @param int $sl_idx
     * @return Service_Level
     */
    public function getServiceLevel($sl_idx)
    {
        global $ms;

        if (empty($sl_idx)) {
            return false;
        }

        if (!$sl = new Service_Level($sl_idx)) {
            return false;
        }

        /* without IMQ we have to swap in & out */
        if ($ms->getOption('msmode') == "router") {
            $sl->swapInOut();
        }

        return $sl;

    } // getServiceLevel()

    /**
     * get service level name
     *
     * this function will return the name of the requested
     * service level.
     */
    public function getServiceLevelName($sl_idx)
    {
        if (!$sl = new Service_Level($sl_idx)) {
            return false;
        }

        return $sl->sl_name;

    } // getServiceLevelName()

    /**
     * get target name
     *
     * this function will return the name of the requested
     * target.
     */
    public function getTargetName($target_idx)
    {
        if (!$target = new Target($target_idx)) {
            return false;
        }

        return $target->target_name;

    } // getTargetName()

    /**
     * get chain name
     *
     * this function will return the name of the requested
     * chain.
     */
    public function getChainName($chain_idx)
    {
        if (!$chain = new Chain($chain_idx)) {
            return false;
        }

        return $chain->chain_name;

    } // getChainName()

    /**
     * get all filters for that pipe
     *
     * this function will return all assigned filters
     * for the specified pipe
     *
     * @param int $pipe_idx
     * @param bool $with_name
     * @return array
     */
    public function getFilters($pipe_idx, $with_name = false)
    {
        global $db;

        $query = "
            SELECT
            af.apf_filter_idx as apf_filter_idx
            ";

        if ($with_name) {
            $query.= ",
                f.filter_name as filter_name
                    ";
        }

        $query.= "
            FROM
            TABLEPREFIXassign_filters_to_pipes af
            INNER JOIN
            TABLEPREFIXfilters f
            ON
            af.apf_filter_idx=f.filter_idx
            WHERE
            af.apf_pipe_idx LIKE ?
            AND
            f.filter_active='Y'
            ";

        $sth = $db->prepare($query);

        $db->execute($sth, array(
                    $pipe_idx
                    ));

        $res = $sth->fetchAll();

        $db->db_sth_free($sth);

        return $res;

    } // getFilters()

    /**
     * get all ports for that filters
     *
     * this function will return all assigned ports
     * for the specified filter
     */
    public function getPorts($filter_idx)
    {
        global $db;

        $list = null;
        $numbers = "";

        /* first get all the port id's for that filter */
        if (!isset($this->sth_get_ports)) {
            $this->sth_get_ports = $db->prepare("
                    SELECT
                    p.port_name as port_name,
                    p.port_number as port_number
                    FROM
                    TABLEPREFIXassign_ports_to_filters afp
                    INNER JOIN
                    TABLEPREFIXports p
                    ON
                    afp.afp_port_idx=p.port_idx
                    WHERE
                    afp_filter_idx LIKE ?
                    ");
        }

        $db->execute($this->sth_get_ports, array(
                    $filter_idx
                    ));

        $numbers = array();

        while ($port = $this->sth_get_ports->fetch()) {
            array_push($numbers, array(
                        'name' => $port->port_name,
                        'number' => $port->port_number
                        ));
        }

        $db->db_sth_free($this->sth_get_ports);

        /* now look up the IANA port numbers for that ports */
        if (empty($numbers)) {
            return null;
        }

        return $numbers;

    } // getPorts()

    /* extract all ports from a string */
    public function extractPorts($string)
    {
        if (empty($string) || preg_match("/any/", $string)) {
            return null;
        }

        $string = str_replace(" ", "", $string);
        $ports = preg_split("/,/", $string);

        $targets = array();

        foreach ($ports as $port) {

            $port = trim($port);

            if (!preg_match("/.*-.*/", $port)) {
                array_push($targets, $port);
                continue;
            }

            list($start, $end) = preg_split("/-/", $port);
            // if the user try to fool us...
            if ($end < $start) {
                $tmp = $end;
                $end = $start;
                $start = $tmp;
            }
            for ($i = $start*1; $i <= $end*1; $i++) {
                array_push($targets, $i);
            }
        }

        return $targets;

    } // extractPorts()

    /**
     * this function generates the value used for CONNMARK
     */
    public function getConnmarkId($string1, $string2)
    {
        // if dechex returned string longer than 8 chars,
        // we are running 64 kernel, so we have to shift
        // first 8 chars from left.

        $tmp = dechex((float) crc32($string1 . str_replace(":", "", $string2))* -1);
        if (strlen($tmp)> 8) {
            $tmp = substr($tmp, 8);
        }

        return "0x".$tmp;

    } // getConnmarkId()

    /**
     * return all assigned l7 protocols
     *
     * this function will return all assigned l7 protocol which
     * are assigned to the provided filter
     */
    public function getL7Protocols($filter_idx)
    {
        global $db;

        $list = null;
        $numbers = "";

        if (!isset($this->sth_get_l7_protocols)) {
            $this->sth_get_l7_protocols = $db->prepare("
                    SELECT
                    afl7_l7proto_idx
                    FROM
                    TABLEPREFIXassign_l7_protocols_to_filters
                    WHERE
                    afl7_filter_idx LIKE ?
                    ");
        }

        $db->execute($this->sth_get_l7_protocols, array(
                    $filter_idx
                    ));

        while ($protocol = $this->sth_get_l7_protocols->fetch()) {
            $numbers.= $protocol->afl7_l7proto_idx .",";
        }

        $db->db_sth_free($this->sth_get_l7_protocols);

        if (empty($numbers)) {
            return null;
        }

        $numbers = substr($numbers, 0, strlen($numbers)-1);
        $sth = $db->prepare("
                SELECT
                l7proto_name
                FROM
                TABLEPREFIXl7_protocols
                WHERE
                l7proto_idx IN (?)
                ");

        $list = $db->execute($sth, array(
                    $numbers
                    ));

        $db->db_sth_free($sth);
        return $list;

    } // getL7Protocols

    /**
     * return content around monitor
     */
    public function monitor($mode)
    {
        $obj = new MASTERSHAPER_MONITOR($this);
        $obj->show($mode);

    } // monitor()

    /**
     * return JSON data for jqPlot
     *
     * @return string
     */
    private function rpcGraphData()
    {
        require_once "class/pages/monitor.php";
        $obj = new Page_Monitor;
        print $obj->get_jqplot_values();

    } // rpc_graph_data()

    /**
     * change settings which graph is going to be displayed
     */
    private function rpcGraphMode()
    {
        if (isset($_POST['graphmode']) && $this->is_valid_graph_mode($_POST['graphmode'])) {
            $_SESSION['graphmode'] = $_POST['graphmode'];
        }

        if (isset($_POST['scalemode']) && $this->is_valid_scale_mode($_POST['scalemode'])) {
            $_SESSION['scalemode'] = $_POST['scalemode'];
        }

        if (isset($_POST['interface']) && $this->is_valid_interface($_POST['interface'])) {
            $_SESSION['showif'] = $_POST['interface'];
        }

        if (isset($_POST['chain']) && $this->is_valid_chain($_POST['chain'])) {
            $_SESSION['showchain'] = $_POST['chain'];
        }

        print "ok";

    } // rpc_change_graph()

    /**
     * change host profile
     */
    private function rpcSetHostProfile()
    {
        if (!isset($_POST['hostprofile']) || !is_numeric($_POST['hostprofile'])) {
            print "invalid host profile";
            return false;
        }

        if (!$this->is_valid_host_profile($_POST['hostprofile'])) {
            print "invalid host profile";
            return false;
        }

        $_SESSION['host_profile'] = $_POST['hostprofile'];
        print "ok";

    } // rpc_change_graph()

    /**
     * return current host state (task queue)
     *
     * @return bool
     */
    private function rpcGetHostState()
    {
        if (!isset($_POST['idx']) || !is_numeric($_POST['idx'])) {
            print "invalid host profile";
            return false;
        }

        if (!$this->is_valid_host_profile($_POST['idx'])) {
            print "invalid host profile";
            return false;
        }

        // has host updated its heartbeat recently
        $hb = $this->get_host_heartbeat($_POST['idx']);

        if (time() > ($hb + 60)) {
            print WEB_PATH .'/icons/absent.png';
            return false;
        }

        if ($this->is_running_task($_POST['idx'])) {
            print WEB_PATH .'/icons/busy.png';
        } else {
            print WEB_PATH .'/icons/ready.png';
        }

        return true;

    } // rpc_get_host_state()

    /**
     * check if requested graph mode is valid
     *
     * @param int $mode
     * @return boolean
     */
    private function isValidGraphMode($mode)
    {
        if (!is_numeric($mode)) {
            return false;
        }

        if (!in_array($mode, array(0,1,2,3))) {
            return false;
        }

        return true;

    } // is_valid_graph_mode()

    /**
     * check if requested scale mode is valid
     *
     * @param string $mode
     * @return boolean
     */
    private function isValidScaleMode($mode)
    {
        if (in_array($mode, array('bit', 'byte', 'kbit', 'kbyte', 'mbit', 'mbyte'))) {
            return true;
        }

        return false;

    } // is_valid_scale_mode()

    /**
     * check if requested interface is valid
     *
     * @param string $if
     * @return boolean
     */
    private function isValidInterface($if)
    {
        $interfaces = $this->getActiveInterfaces();

        while ($interface = $interfaces->fetch()) {
            if ($if === $interface->if_name) {
                return true;
            }
        }

        return false;

    } // is_valid_interface()

    /**
     * check if requested chain is valid
     *
     * @param int $chain_idx
     * @return boolean
     */
    private function isValidChain($chain_idx)
    {
        if (!is_numeric($chain_idx)) {
            return false;
        }

        if (!($obj = new Chain($chain_idx))) {
            return false;
        }

        return true;

    } // is_valid_chain()

    /**
     * checks if provided host profile id is valid
     *
     * @return boolean
     */
    private function isValidHostProfile($host_idx)
    {
        global $db;

        if ($db->fetchSingleRow(
            "SELECT
                host_idx
            FROM
                TABLEPREFIXhost_profiles
            WHERE
                host_idx LIKE '". $host_idx ."'"
        )) {
            return true;
        }

        return false;

    } // is_valid_host_profile()

    public function getActiveInterfaces()
    {
        global $db;

        $result = $db->db_query("
                SELECT
                DISTINCT if_name
                FROM
                shaper2_interfaces iface
                INNER JOIN
                shaper2_network_paths np
                ON (
                    np.netpath_if1=iface.if_idx
                    OR
                    np.netpath_if2=iface.if_idx
                   )
                WHERE
                np.netpath_active LIKE 'Y'
                AND
                np.netpath_host_idx LIKE ". $this->get_current_host_profile() ."
                AND
                iface.if_host_idx LIKE ". $this->get_current_host_profile() ."
                ");

        return $result;

    } // getActiveInterfaces()

    public function setShaperStatus($status)
    {
        $this->setOption("status", $status);

    } // setShaperStatus()

    /**
     * return the current process-user name
     */
    public function getuid()
    {
        if ($uid = posix_getuid()) {
            if ($user = posix_getpwuid($uid)) {
                return $user['name'];
            }
        }

        return 'n/a';

    } // getuid()

    public function raiseError($string, $stop = false)
    {
        if (defined('DB_NOERROR')) {
            $this->last_error = $string;
            return;
        }

        print "<br /><br />". $string ."<br /><br />\n";

        try {
            throw new ExceptionController;
        } catch (ExceptionController $e) {
            print "<br /><br />\n";
            $this->write($e, LOG_WARNING);
        }

        if ($stop) {
            die;
        }

        $this->last_error = $string;

    } // raiseError()

    public function write($logtext, $loglevel = LOG_INFO, $override_output = null, $no_newline = null)
    {
        if (isset($this->config->logging)) {
            $logtype = $this->config->logging;
        } else {
            $logtype = 'display';
        }

        if (isset($override_output) || !empty($override_output)) {
            $logtype = $override_output;
        }

        if ($loglevel > $this->getVerbosity()) {
            return true;
        }

        switch($logtype) {
            default:
            case 'display':
                print $logtext;
                if (!$this->isCmdline()) {
                    print "<br />";
                } elseif (!isset($no_newline)) {
                    print "\n";
                }
                break;
            case 'errorlog':
                error_log($logtext);
                break;
            case 'logfile':
                error_log($logtext, 3, $this->config->log_file);
                break;
        }

        return true;

    }

    public function getProcessUserId()
    {
        if ($uid = posix_getuid()) {
            return $uid;
        }

        return false;
    }

    public function getProcessGroupId()
    {
        if ($gid = posix_getgid()) {
            return $gid;
        }

        return false;
    }

    public function getProcessUserName()
    {
        if (!$uid = $this->getProcessUserId()) {
            return false;
        }

        if ($user = posix_getpwuid($uid)) {
            return $user['name'];
        }

        return false;

    }

    public function getProcessGroupName()
    {
        if (!$uid = $this->getProcessGroupId()) {
            return false;
        }

        if ($group = posix_getgrgid($uid)) {
            return $group['name'];
        }

        return false;
    }

    private function handlePageRequest()
    {
        if (!isset($_POST) || !is_array($_POST)) {
            return;
        }

        if (!isset($_POST['action'])) {
            return;
        }

        switch($_POST['action']) {

            case 'do_login':
                if ($this->login()) {
                    /* on successful login, redirect browser to start page */
                    Header("Location: ". WEB_PATH ."/");
                    exit(0);
                }
                break;

            case 'do_logout':
                if ($this->logout()) {
                    /* on successful logout, redirect browser to login page */
                    Header("Location: ". WEB_PATH ."/");
                    exit(0);
                }
                break;

        }

    } // handle_page_request()

    /**
     * return if request rpc action is ok
     *
     * @return bool
     */
    private function isValidRpcAction()
    {
        global $page;

        $valid_actions = array(
                'delete',
                'toggle',
                'clone',
                'alter-position',
                'graph-data',
                'graph-mode',
                'get-content',
                'get-sub-menu',
                'set-host-profile',
                'get-host-state',
                'idle',
                );

        if (in_array($page->action, $valid_actions)) {
            return true;
        }

        return false;

    }  // is_valid_rpc_action()

    public function filterFormData($data, $filter)
    {
        if (!is_array($data)) {
            return false;
        }

        $filter_result = array();

        foreach ($data as $key => $value) {

            if (strstr($key, $filter) === false) {
                continue;
            }

            $filter_result[$key] = $value;
        }

        return $filter_result;

    }  // filter_form_data

    /**
     * return true if the provided chain name with the specified
     * name already exists
     *
     * @param string $object_type
     * @param string $object_name
     * @return bool
     */
    public function checkObjectExists($object_type, $object_name)
    {
        global $ms, $db;

        switch($object_type) {
            case 'chain':
                $table = 'chains';
                $column = 'chain';
                break;
            case 'filter':
                $table = 'filters';
                $column = 'filter';
                break;
            case 'pipe':
                $table = 'pipes';
                $column = 'pipe';
                break;
            case 'target':
                $table = 'targets';
                $column = 'target';
                break;
            case 'port':
                $table = 'ports';
                $column = 'port';
                break;
            case 'protocol':
                $table = 'protocols';
                $column = 'proto';
                break;
            case 'service_level':
                $table = 'service_levels';
                $column = 'sl';
                break;
            case 'user':
                $table = 'users';
                $column = 'user';
                break;
            case 'interface':
                $table = 'interfaces';
                $column = 'if';
                break;
            case 'netpath':
                $table = 'network_paths';
                $column = 'netpath';
                break;
            case 'hostprofile':
                $table = 'host_profiles';
                $column = 'host';
                break;
            default:
                $ms->raiseError('unknown object type');
                return;
        }

        if ($db->fetchSingleRow(
            "Select ". $column ."_idx from ". mysql_prefix . $table
            ."where ". $column ."_name like binary '". $object_name ."'"
        )) {
            return true;
        }

        return false;

    } // check_object_exist


    /**
     * update position
     *
     */
    public function updatePositions($obj_type, $ms_objects = null)
    {
        global $db;

        if ($obj_type == "pipes") {

            // loop through all provided chain ids
            foreach ($ms_objects as $chain) {

                // get all pipes used by chain
                $pipes = $db->db_query("
                        select
                        apc_pipe_idx as pipe_idx
                        from
                        TABLEPREFIXassign_pipes_to_chains
                        where
                        apc_chain_idx like '". $chain ."'
                        order by
                        apc_pipe_pos asc
                        ");

                // update all pipes position assign to this chain
                $pos = 1;

                while ($pipe = $pipes->fetch()) {

                    $sth = $db->prepare("
                            update
                            TABLEPREFIXassign_pipes_to_chains
                            set
                            apc_pipe_pos=?
                            where
                            apc_pipe_idx=?
                            and
                            apc_chain_idx=?
                            ");

                    $db->execute($sth, array(
                                $pos,
                                $pipe->pipe_idx,
                                $chain
                                ));

                    $db->db_sth_free($sth);
                    $pos++;
                }
            }
        }

        if ($obj_type == "chains") {

            // get all chains assign to this network-path
            $sth = $db->prepare("
                    select
                    chain_idx
                    from
                    TABLEPREFIXchains
                    where
                    chain_netpath_idx like ?
                    and
                    chain_host_idx like ?
                    order by
                    chain_position asc
                    ");

            $db->execute($sth, array(
                        $ms_objects,
                        $this->get_current_host_profile(),
                        ));

            $pos = 1;

            while ($chain = $sth->fetch()) {

                $sth_update = $db->prepare("
                        update
                        TABLEPREFIXchains
                        set
                        chain_position=?
                        where
                        chain_idx like ?
                        and
                        chain_netpath_idx like ?
                        and
                        chain_host_idx like ?
                        ");

                $db->execute($sth_update, array(
                            $pos,
                            $chain->chain_idx,
                            $ms_objects,
                            $this->get_current_host_profile(),
                            ));

                $db->db_sth_free($sth_update);
                $pos++;
            }

            $db->db_sth_free($sth);
        }

        if ($obj_type == "networkpaths") {

            $pos = 1;

            $sth = $db->prepare("
                    select
                    netpath_idx
                    from
                    TABLEPREFIXnetwork_paths
                    where
                    netpath_host_idx like ?
                    order by
                    netpath_position asc
                    ");

            $db->execute($sth, array(
                        $this->get_current_host_profile(),
                        ));

            $pos = 1;

            while ($np = $sth->fetch()) {

                $sth_update = $db->prepare("
                        update
                        TABLEPREFIXnetwork_paths
                        set
                        netpath_position=?
                        where
                        netpath_idx like ?
                        and
                        netpath_host_idx like ?
                        ");

                $db->execute($sth_update, array(
                            $pos,
                            $np->netpath_idx,
                            $this->get_current_host_profile(),
                            ));

                $db->db_sth_free($sth_update);
                $pos++;
            }

            $db->db_sth_free($sth);
        }

    } // update_positions()

    public function getCurrentHostProfile()
    {
        if (isset($_session['host_profile']) && !empty($_session['host_profile'])) {
            return $_session['host_profile'];
        }

        return 1;

    } // get_current_host_profile()

    /**
     * update host heartbeat indicator
     *
     * @param int $host_idx
     */
    public function updateHostHeartbeat($host_idx)
    {
        global $db;

        if (!isset($this->sth_update_host_heartbeat)) {
            $this->sth_update_host_heartbeat = $db->prepare("
                    update
                    TABLEPREFIXhost_profiles
                    set
                    host_heartbeat=unix_timestamp()
                    where
                    host_idx like ?
                    ");
        }

        $db->execute($this->sth_update_host_heartbeat, array(
                    $host_idx
                    ));

        $db->db_sth_free($this->sth_update_host_heartbeat);

    } // update_host_heartbeat()

    public function getHostHeartbeat($host_idx)
    {
        global $db;

        $result = $db->db_query("
                select
                host_heartbeat
                from
                TABLEPREFIXhost_profiles
                where
                host_idx like '". $host_idx ."'
                ");

        if ($row = $result->fetch()) {
            return $row->host_heartbeat;
        }

        return false;

    } // get_host_heartbeat()

    /**
     * return global unique identifier
     *
     * original author
     * http://www.rodsdot.com/php/how-to-obtain-a-guid-using-php-pseudo.php
     * @return string
     */
    public function createGuid()
    {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);

        return $uuid;

    } // create_guid()

    public function addTask($job_cmd)
    {
        /* task_state's

           n = new
           r = running
           f = finished
           e = error/failed

         */

        global $db;

        $host_idx = $this->get_current_host_profile();

        if (!$this->is_valid_task($job_cmd)) {
            $ms->raiseError('invalid task '. $job_cmd .' submitted');
        }

        /* if there is an rules_unload request, we can remove
           any pending rules_load(_debug) task that is not yet
           processed.
         */
        if ($job_cmd == 'rules_unload') {
            $db->db_query("
                    delete from
                    TABLEPREFIXtasks
                    where (
                        task_job like 'rules_load'
                        or
                        task_job like 'rules_load_debug'
                        ) and (
                            task_host_idx like '". $host_idx ."'
                            and
                            task_state like 'n'
                            )
                    ");
        }

        $sth = $db->prepare("
                insert into TABLEPREFIXtasks (
                    task_job,
                    task_submit_time,
                    task_run_time,
                    task_host_idx,
                    task_state
                    ) values (
                        ?,
                        ?,
                        ?,
                        ?,
                        'n'
                        ) on duplicate key update task_submit_time=unix_timestamp()
                ");

        $db->execute($sth, array(
                    $job_cmd,
                    time(),
                    -1,
                    $host_idx
                    ));

        $db->db_sth_free($sth);

    } // add_task()

    public function getTasks()
    {
        global $db;

        $host_idx = $this->get_current_host_profile();
        $this->update_host_heartbeat($host_idx);

        if ($this->is_running_task()) {
            $this->_print("there is a running task", mslog_warn);
            return false;
        }

        if (!isset($this->sth_get_tasks)) {

            $this->sth_get_tasks = $db->prepare("
                    select
                    task_idx,
                    task_job,
                    task_submit_time,
                    task_run_time
                    from
                    TABLEPREFIXtasks
                    where
                    task_state like 'n'
                    and
                    task_host_idx like ?
                    order by
                    task_submit_time asc
                    ");
        }

        $db->execute($this->sth_get_tasks, array(
                    $host_idx
                    ));

        while ($task = $this->sth_get_tasks->fetch()) {
            $this->task_handler($task);
        }

        $db->db_sth_free($this->sth_get_tasks);
        unset($tasks);

    } // get_tasks()

    public function isRunningTask($host_idx = null)
    {
        global $db;

        if (!isset($host_idx)) {
            $host_idx = $this->get_current_host_profile();
        }

        if (!isset($this->sth_is_running_task)) {
            $this->sth_is_running_task = $db->prepare("
                    select
                    task_idx
                    from
                    TABLEPREFIXtasks
                    where
                    task_state like 'r'
                    and
                    task_host_idx like ?
                    order by
                    task_submit_time asc
                    ");
        }

        $db->execute($this->sth_is_running_task, array(
                    $host_idx
                    ));

        if ($task = $this->sth_is_running_task->fetch()) {
            $db->db_sth_free($this->sth_is_running_task);
            return true;
        }

        $db->db_sth_free($this->sth_is_running_task);
        return false;

    } // is_running_task()

    private function taskHandler($task)
    {
        $this->set_task_state($task->task_idx, 'running');

        $this->_print(
            "running task '". $task->task_job ."' submitted at ".
            strftime("%y-%m-%d %h:%m:%s", $task->task_submit_time).
            ".",
            mslog_warn,
            null,
            1
        );

        switch($task->task_job) {
            case 'rules_load':
                require_once "class/rules/ruleset.php";
                require_once "class/rules/interface.php";
                $ruleset = new ruleset;
                $retval = $ruleset->load(0);
                unset($ruleset);
                break;
            case 'rules_load_debug':
                require_once "class/rules/ruleset.php";
                require_once "class/rules/interface.php";
                $ruleset = new ruleset;
                $retval = $ruleset->load(1);
                unset($ruleset);
                break;
            case 'rules_unload':
                require_once "class/rules/ruleset.php";
                require_once "class/rules/interface.php";
                $ruleset = new ruleset;
                $retval = $ruleset->unload();
                unset($ruleset);
                break;
            default:
                $this->raiseError('unknown task '. $task->task_job);
                break;
        }

        if ($retval == 0) {
            $this->set_task_state($task->task_idx, 'done', $retval);
        } else {
            $this->set_task_state($task->task_idx, 'failed', $retval);
        }

        $this->_print(" done. ". strftime("%y-%m-%d %h:%m:%s", time()), mslog_warn);

    } // task_handler()

    private function setTaskState($task_idx, $task_state, $retval = null)
    {
        global $db;

        if (!in_array($task_state, array('running', 'done'))) {
            $this->raiseError('invalid task state '. $task_state);
        }

        if (!is_numeric($task_idx) || $task_idx < 0) {
            $this->raiseError('invalid task index '. $task_idx);
        }

        if ($task_state == 'running') {
            $task_state = 'r';
        }
        if ($task_state == 'done' && $retval == 0) {
            $task_state = 'f';
        }
        if ($task_state == 'done' && $retval != 0) {
            $task_state = 'e';
        }

        if (!isset($this->sth_set_task_state)) {

            $this->sth_set_task_state = $db->prepare("
                    update
                    TABLEPREFIXtasks
                    set
                    task_state = ?,
                    task_run_time = unix_timestamp()
                    where
                    task_idx like ?
                    ");
        }

        $db->execute($this->sth_set_task_state, array(
                    $task_state,
                    $task_idx
                    ));

    } // set_task_state()

    public function isValidTask($job_cmd)
    {
        switch($job_cmd) {
            case 'rules_load':
            case 'rules_load_debug':
            case 'rules_unload':
                return true;
        }

        return false;

    } // is_valid_task()

    /**
     * add a http header to mastershapers headers variable
     * that gets included when the template engine prints
     * out the document body.
     *
     * @return bool
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return true;

    } // set_header()

    /**
     * calculate the summary of guaranteed bandwidths from the
     * provided array of pipes.
     *
     * @params mixed $pipes
     * @return array
     */
    public function getBandwidthSummaryFromPipes($pipes)
    {
        $bw_in = 0;
        $bw_out = 0;

        foreach ($pipes as $pipe) {

            // skip pipes not active in this chain
            if (!isset($pipe->apc_pipe_active) || $pipe->apc_pipe_active != 'y') {
                continue;
            }

            // check if pipes original service level got overruled
            if (isset($pipe->apc_sl_idx) && !empty($pipe->apc_sl_idx)) {
                $sl = $pipe->apc_sl_idx;
            } else {
                $sl = $pipe->pipe_sl_idx;
            }

            $sl_details = $this->get_service_level($sl);

            if (isset($sl_details->sl_htb_bw_in_rate) && !empty($sl_details->sl_htb_bw_in_rate)) {
                $bw_in+=$sl_details->sl_htb_bw_in_rate;
            }
            if (isset($sl_details->sl_htb_bw_out_rate) && !empty($sl_details->sl_htb_bw_out_rate)) {
                $bw_out+=$sl_details->sl_htb_bw_out_rate;
            }

        }

        return array($bw_in, $bw_out);

    } // get_bandwidth_summary_from_pipes()

    /**
     * get a specific http to be set by mastershapers headers variable
     *
     * @return string
     */
    public function getHeader($key)
    {
        if (!isset($this->headers[$key])) {
            return null;
        }

        return $this->headers[$key];

    } // get_header()

    public function collectStats()
    {
        global $db;

        $sec_counter = 0;
        $class_id    = 0;
        $bandwidth   = array();
        $counter     = array();
        $last_bytes  = array();
        $counter     = array();

        while (!system_daemon::isdying()) {

            $sec_counter++;

            // get active interfaces
            $interfaces = $this->getactiveinterfaces();

            foreach ($interfaces as $interface) {

                $tc_if = $interface->if_name;

# get the current stats from tc
                $lines = $this->run_proc(tc_bin ." -s class show dev ". $tc_if);

# just example lines
#class htb 1:eefd parent 1:eefe leaf eefd: prio 5 rate 256000bit ceil 1024kbit burst 1600b cburst 1599b
# sent 263524 bytes 825 pkt (dropped 0, overlimits 0 requeues 0)
#class htb 1:eefc parent 1:eefe leaf eefc: prio 2 rate 1000bit ceil 97280kbit burst 1600b cburst 1580b
# sent 6419843 bytes 35270 pkt (dropped 0, overlimits 0 requeues 0)

                // analyze the lines
                foreach ($lines as $line) {

                    // if the line doesn't contain anything we are looking for we skip it
                    if (empty($line) || !preg_match('/(^class htb|^sent)/', $line)) {
                        continue;
                    }

                    // do we currently handle a specific class_id?
                    if ($class_id == 0) {

                        // extract class id from string
                        $class_id = $this->extract_class_id($line);

                        // if we have no valid class_id
                        if (empty($class_id)) {
                            $this->_print("no classid found in ". $line, mslog_debug);
                            continue;
                        }

                        $arkey = $tc_if ."_". $class_id;

                        $this->_print("fetching data interface: ". $tc_if .", class: ". $class_id, mslog_debug);

# we already counting this class?
                        if (!isset($counter[$arkey])) {
                            $counter[$arkey] = 0;
                        }
                        if (!isset($bandwidth[$arkey])) {
                            $bandwidth[$arkey] = 0;
                        }
                        // we must have a "sent" line here
                    } else {

                        // extract currently transfered bytes from string
                        $current_bytes = $this->extract_bytes($line);

                        // we have not located a counter, skip no next class_id
                        if ($current_bytes < 0) {
                            $this->_print("no traffic found in ". $line, mslog_debug);
                            $class_id = 0;
                            continue;
                        }

                        // if counter is zero, we can skip this class_id
                        if ($current_bytes == 0) {
                            $this->_print(
                                "no traffic for interface: ". $tc_if .", class: ".
                                $class_id .", ". $current_bytes ." bytes",
                                mslog_debug
                            );
                            $class_id = 0;
                            continue;
                        }

                        $arkey = $tc_if ."_". $class_id;

                        // have we recorded this class_id already before
                        if (isset($last_bytes[$arkey])) {

                            // calculate the bandwidth for this run
                            $current_bw = $current_bytes - $last_bytes[$arkey];

                        } else {
                            $current_bw = 0;
                        }

                        // store the currently transfered bytes for the next run
                        $last_bytes[$arkey] = $current_bytes;
                        // add bandwidth to summary array
                        $bandwidth[$arkey]+=$current_bw;
                        // increment the counter for this class_id
                        $counter[$arkey]+=1;

                        // prepare for the next class_id to fetch
                        $class_id = 0;
                    }
                }
            }

            // we record tenth samples before we record to database
            if ($sec_counter < 10) {
                system_daemon::iterate(1);
                continue;
            }

            $tcs = array_keys($bandwidth);
            $data = "";

            $this->_print("try: ". count($tcs) ."\n", mslog_debug);
            $this->_print("storing tc statistic now.", mslog_debug);

            foreach ($tcs as $tc) {

                list($tc_if, $class_id) = preg_split('/_/', $tc);

                // calculate the average bandwidth based on our recorded samples
                if ($counter[$tc] > 0) {
                    $aver_bw = $bandwidth[$tc]/($counter[$tc]);
                } else {
                    $aver_bw = 0;
                }

                // bytes to bits
                $aver_bw = round($aver_bw*8);

                $this->_print(
                    "recording interface: ". $tc_if .", class: ".
                    $class_id .", transferred: ". $aver_bw ." ".
                    $counter[$tc],
                    mslog_info
                );

                $data.= $tc ."=". $aver_bw .",";

                // this class has been calculated, become ready for the next one
                unset($counter[$tc]);
                unset($bandwidth[$tc]);
                unset($last_bytes[$tc]);
            }

            // get current time
            $now = time();

            if (!empty($data)) {

                $data = substr($data, 0, strlen($data)-1);

                if (!isset($this->sth_collect_stats)) {
                    $this->sth_collect_stats = $db->prepare("
                            insert into TABLEPREFIXstats (
                                stat_time,
                                stat_data,
                                stat_host_idx
                                ) values (
                                    ?,
                                    ?,
                                    ?
                                    )
                            ");
                }

                try {
                    $this->sth_collect_stats->execute(array(
                                $now,
                                $data,
                                $this->get_current_host_profile(),
                                ));
                } catch (pdoexception $e) {
                    $this->_print("exception: ". $e->getmessage(), mslog_warn);
                }

                $db->db_sth_free($this->sth_collect_stats);

                $this->_print("statistics stored in mysql database.", mslog_debug);
            } else {
                $this->_print("no data available for statistics. tc rules loaded?", mslog_info);
            }

# delete old samples
            $db->db_query("
                    delete from
                    TABLEPREFIXstats
                    where
                    stat_host_idx like ". $this->get_current_host_profile() ."
                    and
                    stat_time < ". ($now-300) ."
                    ");

# reset helper vars
            $sec_counter = 0;

            system_daemon::iterate(1);

        }

    } // collect_stats()

    private function runProc($cmd = "", $ignore_err = null)
    {
        $retval = array();
        $error = "";

        $desc = array(
                0 => array('pipe','r'), /* stdin */
                1 => array('pipe','w'), /* stdout */
                2 => array('pipe','w'), /* stderr */
                );

        $process = proc_open($cmd, $desc, $pipes);

        if (is_resource($process)) {

            $stdin = $pipes[0];
            $stdout = $pipes[1];
            $stderr = $pipes[2];

            while (!feof($stdout)) {
                array_push($retval, trim(fgets($stdout)));
            }
            /*while (!feof($stderr)) {
              $error.= trim(fgets($stderr));
              }*/

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exit_code = proc_close($process);

        }

        /*if (is_null($ignore_err)) {
          if (!empty($error) || $retval != "ok")
          throw new exception($error);
          }*/

        return $retval;

    } // run_proc()

    private function extractClassId($line)
    {

        if (!preg_match('/class htb/', $line)) {
            return false;
        }

        $temp_array = array();
        $temp_array = preg_split('/\s/', $line);
        return $temp_array[2];

    } // extract_class_id()

    private function extractBytes($line)
    {

        if (!preg_match('/sent/', $line)) {
            return -1;
        }

        $temp_array = array();
        $temp_array = preg_split('/\s/', $line);
        return $temp_array[1];

    } // extract_bytes()

    public function initTaskManager()
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            $this->raiseError("unable to create child process for task-manager");
            die;
        }

        if ($pid) {
            return $pid;
        }

        //setproctitle("shaper_agent.php - tasks");

        // reconnect spawned child to database
        $globals['db'] = new mastershaper_db;

        while (!system_daemon::isdying()) {
            $this->get_tasks();
            if (function_exists("gc_collect_cycles")) {
                gc_collect_cycles();
            }
            // sleep a second
            system_daemon::iterate(1);
        }

    } // init_task_manager()

    public function initStatsCollector()
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            $this->raiseError("unable to create child process for stats-collector");
            return false;
        }

        if ($pid) {
            return $pid;
        }

        //setproctitle("shaper_agent.php - stats");

        // reconnect spawned child to database
        $globals['db'] = new mastershaper_db;

        $this->collect_stats();

    } // init_stats_collector()

    public function getverbosity()
    {
        return self::LOGLEVEL;

    } // get_verbosity()

    public function iscmdline()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        return false;

    } // iscmdline()

    private function rpchandler()
    {
        if (!$this->is_logged_in()) {
            print "you need to login first";
            return false;
        }


        $this->loadcontroller("rpc", "rpc");
        global $rpc;

        ob_start();
        if (!$rpc->perform()) {
            $this->raiseerror("rpccontroller::perform() returned false!");
            return false;
        }
        unset($rpc);

        $size = ob_get_length();
        header("content-length: $size");
        header('connection: close');
        ob_end_flush();
        ob_flush();
        session_write_close();

        // invoke the messagebus processor so pending tasks can
        // be handled. but suppress any output.
        if (!$this->performactions()) {
            $this->raiseerror('performactions() returned false!');
            return false;
        }

        return true;
    }

    public function checkupgrade()
    {
        global $db, $config;

        if (!($base_path = $config->getwebpath())) {
            $this->raiseerror("configcontroller::getwebpath() returned false!");
            return false;
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        if (!$db->checkTableExists("TABLEPREFIXmeta")) {
            $this->raiseError(
                "You are missing meta table in database! "
                ."You may run <a href=\"{$base_path}/install\">"
                ."Installer</a> to fix this.",
                true
            );
            return true;
        }

        if ($db->getDatabaseSchemaVersion() < $db::SCHEMA_VERSION) {
            $this->raiseError(
                "The local schema version ({$db->getDatabaseSchemaVersion()}) is lower "
                ."than the programs schema version (". $db::SCHEMA_VERSION ."). "
                ."You may run <a href=\"{$base_path}/install\">Installer</a> "
                ."again to upgrade.",
                true
            );
            return true;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
