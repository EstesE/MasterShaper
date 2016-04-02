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

class NetworkPathsView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'network_paths';
    private $network_paths;
    private $chains;
    private $host_idx;

    public function __construct()
    {
        global $tmpl, $session;

        try {
            $this->network_paths = new \MasterShaper\Models\NetworkPathsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkPathsModel!', false, $e);
            return false;
        }

        if (($this->host_idx = $session->getCurrentHostProfile()) === false) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned false!');
            $repeat  = false;
            return false;
        }

        $tmpl->registerPlugin(
            "function",
            "if_select_list",
            array(&$this, "smartyIfSelectList"),
            false
        );

        $tmpl->registerPlugin(
            "block",
            "chain_list",
            array(&$this, "smartyChainList"),
            false
        );

        parent::__construct();
        return;
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

        if (!$this->network_paths->hasItems()) {
            return parent::showList();
        }

        try {
            $pager = new \MasterShaper\Controllers\PagingController(array(
                'delta' => 2,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PagingController!');
            return false;
        }

        if (!$pager->setPagingData($this->network_paths->getItems())) {
            static::raiseError(get_class($pager) .'::setPagingData() returned false!');
            return false;
        }

        if (!$pager->setCurrentPage($current_page)) {
            static::raiseError(get_class($pager) .'::setCurrentPage() returned false!');
            return false;
        }

        if (!$pager->setItemsLimit($current_items_limit)) {
            static::raiseError(get_class($pager) .'::setItemsLimit() returned false!');
            return false;
        }

        global $tmpl;
        $tmpl->assign('pager', $pager);

        if (($data = $pager->getPageData()) === false) {
            static::raiseError(get_class($pager) .'::getPageData() returned false!');
            return false;
        }

        if (!isset($data) || empty($data) || !is_array($data)) {
            static::raiseError(get_class($pager) .'::getPageData() returned invalid data!');
            return false;
        }

        $this->avail_items = array_keys($data);
        $this->items = $data;

        if (!$this->setSessionVar("current_page", $current_page)) {
            static::raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        if (!$this->setSessionVar("current_items_limit", $current_items_limit)) {
            static::raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        return parent::showList();

    } // showList()

    /**
     * template function which will be called from the netpath listing template
     */
    public function network_pathsList($params, $content, &$smarty, &$repeat)
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

    /**
     * this function will return a select list full of interfaces
     */
    public function smartyIfSelectList($params, &$smarty)
    {
        global $ms, $db, $session;

        if (!array_key_exists('if_idx', $params)) {
            static::raiseError("getSLList: missing 'if_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $result = $db->query(
            "SELECT
                *
            FROM
                TABLEPREFIXinterfaces
            WHERE
                if_host_idx LIKE '{$this->host_idx}'
            ORDER BY
                if_name ASC"
        );

        $string = "";
        while ($row = $result->fetch()) {
            $string.= "<option value=\"". $row->if_idx ."\"";
            if ($params['if_idx'] == $row->if_idx) {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $row->if_name ."</option>";
        }

        return $string;

    } // smartyIfSelectList()

    /**
     * template function which will be called from the network path editing template
     */
    public function smartyChainList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.chains_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!isset($this->chains) || empty($this->chains)) {
            $repeat = false;
            return $content;
        }

        if ($index >= count($this->chains)) {
            $repeat = false;
            return $content;
        }

        $chain =  $this->chains[$index];
        $smarty->assign("chain", $chain);

        $index++;
        $smarty->assign('smarty.IB.chains_list.index', $index);
        $repeat = true;

        return $content;

    } // smartyChainList()

    public function showEdit($id, $guid)
    {
        global $tmpl;

        try {
            $item = new \MasterShaper\Models\NetworkPathModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkPathModel!', false, $e);
            return false;
        }

        try {
            $chains = new \MasterShaper\Models\ChainsModel(array(
                'host_idx' => $id,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ChainsModel!', false, $e);
            return false;
        }

        if (($this->chains = $chains->getItems()) === false) {
            static::raiseError(get_class($chains) .'::getItems() returned false!');
            return false;
        }

        $tmpl->assign('netpath', $item);
        return parent::showEdit($id, $guid);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
