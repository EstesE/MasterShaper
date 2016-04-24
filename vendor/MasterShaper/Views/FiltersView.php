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
    private $protocols;
    private $ports;

    public function __construct()
    {
        try {
            $filters = new \MasterShaper\Models\FiltersModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load FiltersModel!', true, $e);
            return;
        }

        if (!$this->setViewData($filters)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        parent::__construct();
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
            static::raiseError(__METHOD__ .'(), failed to load FilterModel!', false, $e);
            return false;
        }

        $tmpl->registerPlugin(
            "function",
            "protocol_select_list",
            array(&$this, "smartyProtocolSelectList"),
            false
        );
        $tmpl->registerPlugin(
            "function",
            "port_select_list",
            array(&$this, "smartyPortSelectList"),
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

    public function smartyFilterList($params, $content, &$smarty, &$repeat)
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

    public function smartyProtocolSelectList($params, &$smarty)
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

    public function smartyPortSelectList($params, &$smarty)
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
