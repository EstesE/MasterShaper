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

    public function __construct()
    {
        try {
            $this->network_paths = new \MasterShaper\Models\NetworkPathsModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load NetworkPathsModel!', false, $e);
            return false;
        }

        parent::__construct();
        return;
    }

    public function showList($pageno = null, $items_limit = null)
    {
        global $session, $tmpl;

        if (!isset($pageno) || empty($pageno) || !is_numeric($pageno)) {
            if (($current_page = $session->getVariable("{$this->class_name}_current_page")) === false) {
                $current_page = 1;
            }
        } else {
            $current_page = $pageno;
        }

        if (!isset($items_limit) || is_null($items_limit) || !is_numeric($items_limit)) {
            if (($current_items_limit = $session->getVariable("{$this->class_name}_current_items_limit")) === false) {
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
            $this->raiseError(__METHOD__ .'(), failed to load PagingController!');
            return false;
        }

        if (!$pager->setPagingData($this->network_paths->getItems())) {
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

        if (!$session->setVariable("{$this->class_name}_current_page", $current_page)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        if (!$session->setVariable("{$this->class_name}_current_items_limit", $current_items_limit)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        return parent::showList();

    } // showList()

    /**
     * interface for handling
     */
    public function showEdit()
    {
        if ($this->is_storing()) {
            $this->store();
        }

        global $ms, $db, $tmpl, $page;

        $this->avail_chains = array();
        $this->chains = array();

        if (isset($page->id) && $page->id != 0) {
            $np = new Network_Path($page->id);
            $tmpl->assign('is_new', false);
        } else {
            $np = new Network_Path;
            $tmpl->assign('is_new', true);
            $page->id = null;
        }

        $sth = $db->prepare("
                SELECT DISTINCT
                c.chain_idx,
                c.chain_name,
                c.chain_active,
                c.chain_position IS NULL as pos_null
                FROM
                TABLEPREFIXchains c
                WHERE
                c.chain_netpath_idx LIKE ?
                AND
                c.chain_host_idx LIKE ?
                ORDER BY
                pos_null DESC,
                chain_position ASC
                ");

        $db->execute($sth, array(
                    $page->id,
                    $ms->get_current_host_profile(),
                    ));

        $cnt_chains = 0;

        while ($chain = $sth->fetch()) {
            $this->avail_chains[$cnt_chains] = $chain->chain_idx;
            $this->chains[$chain->chain_idx] = $chain;
            $cnt_chains++;
        }

        $db->db_sth_free($sth);

        $tmpl->assign('np', $np);
        $tmpl->registerPlugin("function", "if_select_list", array(&$this, "smartyIfSelectList"), false);

        $tmpl->registerPlugin("block", "chain_list", array(&$this, "smartyChainList"), false);

        return $tmpl->fetch("network_paths_edit.tpl");

    } // showEdit()

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
     * handle updates
     */
    public function store()
    {
        global $ms, $db, $rewriter;

        isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = null;

        /* load network path */
        if (isset($new)) {
            $np = new Network_Path;
        } else {
            $np = new Network_Path($_POST['netpath_idx']);
        }

        if (!isset($_POST['netpath_name']) || $_POST['netpath_name'] == "") {
            $ms->raiseError(_("Please specify a network path name!"));
        }
        if (isset($new) && $ms->check_object_exists('netpath', $_POST['netpath_name'])) {
            $ms->raiseError(_("A network path with that name already exists!"));
        }
        if (!isset($new) && $np->netpath_name != $_POST['netpath_name'] &&
                $ms->check_object_exists('netpath', $_POST['netpath_name'])) {
            $ms->raiseError(_("A network path with that name already exists!"));
        }
        if ($_POST['netpath_if1'] == $_POST['netpath_if2']) {
            $ms->raiseError(
                _("An interface within a network path can not be used "
                    ."twice! Please select different interfaces")
            );
        }

        if (!isset($_POST['netpath_if1_inside_gre']) || empty($_POST['netpath_if1_inside_gre'])) {
            $_POST['netpath_if1_inside_gre'] = 'N';
        }

        if (!isset($_POST['netpath_if2_inside_gre']) || empty($_POST['netpath_if2_inside_gre'])) {
            $_POST['netpath_if2_inside_gre'] = 'N';
        }

        $np_data = $ms->filter_form_data($_POST, 'netpath_');

        if (!$np->update($np_data)) {
            return false;
        }

        if (!$np->save()) {
            return false;
        }

        if (isset($_POST['add_another']) && $_POST['add_another'] == 'Y') {
            return true;
        }

        $ms->set_header('Location', $rewriter->get_page_url('Network Paths List'));
        return true;

    } // store()

    /**
     * this function will return a select list full of interfaces
     */
    public function smartyIfSelectList($params, &$smarty)
    {
        global $ms, $db;

        if (!array_key_exists('if_idx', $params)) {
            $smarty->trigger_error("getSLList: missing 'if_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $result = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIXinterfaces
                WHERE
                if_host_idx LIKE '". $ms->get_current_host_profile() ."'
                ORDER BY
                if_name ASC
                ");

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
        $index = $smarty->getTemplateVars('smarty.IB.chain_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_chains)) {

            $chain_idx = $this->avail_chains[$index];
            $chain =  $this->chains[$chain_idx];

            $smarty->assign('chain', $chain);

            $index++;
            $smarty->assign('smarty.IB.chain_list.index', $index);
            $repeat = true;
        } else {
            $repeat =  false;
        }

        return $content;

    } // smartyChainList()
} // class Page_Network_Paths

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
