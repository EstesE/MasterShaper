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

namespace MasterShaper\Views;

class FiltersView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'filters';
    private $filters;
    private $protocols;
    private $ports;

    public function __construct()
    {
        try {
            $this->filters = new \MasterShaper\Models\FiltersModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load FiltersModel!', false, $e);
            return false;
        }

        parent::__construct();
    }

    public function showList($pageno = null, $items_limit = null)
    {
        global $session, $tmpl;

        if (!isset($pageno) || empty($pageno) || !is_numeric($pageno)) {
            if (($current_page = $this->getSessionVar("current_page")) === false) {
                $current_page = 1;
            }
        } else {
            $current_page = $pageno;
        }

        if (!isset($items_limit) || is_null($items_limit) || !is_numeric($items_limit)) {
            if (($current_items_limit = $this->getSessionVar("current_items_limit")) === false) {
                $current_items_limit = -1;
            }
        } else {
            $current_items_limit = $items_limit;
        }

        if (!$this->filters->hasItems()) {
            return parent::showList();
        }

        try {
            $pager = new \MasterShaper\Controllers\PagingController(array(
                'delta' => 2,
            ));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load PagingController!', false, $e);
            return false;
        }

        if (!$pager->setPagingData($this->filters->getItems())) {
            $this->raiseError(get_class($pager) .'::setPagingData() returned false!');
            return false;
        }

        if (!$pager->setCurrentPage($current_page)) {
            $this->raiseError(get_class($pager) .'::setCurrentPage() returned false!');
            return false;
        }

        if (!$pager->setItemsLimit($current_items_limit)) {
            $this->raiseError(get_class($pager) .'::setItemsLimit() returned false!');
            return false;
        }

        global $tmpl;
        $tmpl->assign('pager', $pager);

        if (($data = $pager->getPageData()) === false) {
            $this->raiseError(get_class($pager) .'::getPageData() returned false!');
            return false;
        }

        if (!isset($data) || empty($data) || !is_array($data)) {
            $this->raiseError(get_class($pager) .'::getPageData() returned invalid data!');
            return false;
        }

        $this->avail_items = array_keys($data);
        $this->items = $data;

        if (!$this->setSessionVar("current_page", $current_page)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        if (!$this->setSessionVar("current_items_limit", $current_items_limit)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        return parent::showList();

    } // showList()

    public function filtersList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.item_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!isset($this->avail_items) || empty($this->avail_items)) {
            $repeat = false;
            return $content;
        }

        if ($index >= count($this->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->avail_items[$index];
        $item =  $this->items[$item_idx];

        $smarty->assign("item", $item);

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        try {
            $item = new \MasterShaper\Models\FilterModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load FilterModel!', false, $e);
            return false;
        }

        $tmpl->registerPlugin(
            "function",
            "protocol_select_list",
            array(&$this, "smarty_protocol_select_list"),
            false
        );
        $tmpl->registerPlugin(
            "function",
            "port_select_list",
            array(&$this, "smarty_port_select_list"),
            false
        );

        if (!$this->loadProtocols()) {
            static::raiseError(__CLASS__ .'::loadProtocols() returned false!');
            return false;
        }

        if (!$this->loadPorts()) {
            static::raiseError(__CLASS__ .'::loadPorts() returned false!');
            return false;
        }

        $tmpl->assign('filter', $item);
        return parent::showEdit($id, $guid);
    }

    public function smarty_filter_list($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.filter_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_filters)) {
            $filter_idx = $this->avail_filters[$index];
            $filter =  $this->filters[$filter_idx];

            $smarty->assign('filter_idx', $filter_idx);
            $smarty->assign('filter_name', $filter->filter_name);
            $smarty->assign('filter_active', $filter->filter_active);

            $index++;
            $smarty->assign('smarty.IB.filter_list.index', $index);
            $repeat = true;
        } else {
            $repeat =  false;
        }

        return $content;

    }

    final private function loadProtocols()
    {
        global $db;

        try {
            $protocols = new \MasterShaper\Models\ProtocolsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ProtocolsModel!', false, $e);
            return false;
        }

        if (($this->protocols = $protocols->getItems()) === false) {
            static::raiseError(get_class($protocols) .'::getItems() returned false!');
            return false;
        }

        return true;
    }

    final private function loadPorts()
    {
        global $db;

        try {
            $ports = new \MasterShaper\Models\PortsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PortsModel!', false, $e);
            return false;
        }

        if (($this->ports = $ports->getItems()) === false) {
            static::raiseError(get_class($ports) .'::getItems() returned false!');
            return false;
        }

        return true;
    }

    public function smarty_protocol_select_list($params, &$smarty)
    {
        if (!array_key_exists('proto_idx', $params)) {
            $tmpl->trigger_error("getSLList: missing 'proto_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $string = "";

        foreach ($this->protocols as $protocol) {
            $string.= "<option value=\"". $protocol->getId() ."\"";
            if ($protocol->getId() == $params['proto_idx']) {
                $string.= "selected=\"selected\"";
            }
            $string.= ">". $protocol->getName() ."</option>\n";
        }

        return $string;
    }

    public function smarty_port_select_list($params, &$smarty)
    {
        if (!array_key_exists('filter_idx', $params)) {
            $tmpl->trigger_error("getSLList: missing 'filter_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }
        if (!array_key_exists('mode', $params)) {
            $tmpl->trigger_error("getSLList: missing 'mode' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        global $ms, $db;

        switch ($params['mode']) {
            case 'unused':
                $sth = $db->prepare(
                    "SELECT
                        port_idx,
                        port_name,
                        port_number
                    FROM
                        TABLEPREFIXports
                    LEFT JOIN
                        TABLEPREFIXassign_ports_to_filters
                    ON
                        port_idx=TABLEPREFIXassign_ports_to_filters.afp_port_idx
                    WHERE
                        TABLEPREFIXassign_ports_to_filters.afp_filter_idx <> ?
                    OR
                        ISNULL(TABLEPREFIXassign_ports_to_filters.afp_filter_idx)
                    ORDER BY
                        port_name ASC"
                );

                $db->execute($sth, array(
                    $params['filter_idx']
                ));
                break;

            case 'used':
                $sth = $db->prepare(
                    "SELECT
                        p.port_idx,
                        p.port_name,
                        p.port_number
                    FROM
                        TABLEPREFIXassign_ports_to_filters
                    LEFT JOIN
                        TABLEPREFIXports p
                    ON
                        p.port_idx = afp_port_idx
                    WHERE
                        afp_filter_idx LIKE ?
                    ORDER BY
                        p.port_name ASC"
                );

                $db->execute($sth, array(
                    $params['filter_idx']
                ));
                break;

            default:
                static::raiseError('unknown mode', true);
                break;
        }

        $string = "";
        while ($port = $sth->fetch()) {
            $string.= "<option value=\"". $port->port_idx ."\">"
                . $port->port_name ." (". $port->port_number .")</option>\n";
        }

        $db->freeStatement($sth);

        return $string;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
