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

class HostProfilesView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'host_profiles';
    private $hostprofiles;

    public function __construct()
    {
        try {
            $this->hostprofiles = new \MasterShaper\Models\HostProfilesModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load HostProfilesModel!', false, $e);
            return false;
        }

        parent::__construct();
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

        if (!$this->hostprofiles->hasItems()) {
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

        if (!$pager->setPagingData($this->hostprofiles->getItems())) {
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

        global $db, $tmpl, $page;

        $this->avail_chains = array();
        $this->chains = array();

        if (isset($page->id) && $page->id != 0) {
            $hostprofile = new Host_Profile($page->id);
            $tmpl->assign('is_new', false);
        } else {
            $hostprofile = new Host_Profile;
            $tmpl->assign('is_new', true);
            $page->id = null;
        }

        $tmpl->assign('host', $hostprofile);

        return $tmpl->fetch("host_profiles_edit.tpl");

    } // showEdit()

    /**
     * template function which will be called from the host listing template
     */
    public function host_profilesList($params, $content, &$smarty, &$repeat)
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

        /* load host profile */
        if (isset($new)) {
            $hostprofile = new Host_Profile;
        } else {
            $hostprofile = new Host_Profile($_POST['host_idx']);
        }

        if (!isset($_POST['host_name']) || $_POST['host_name'] == "") {
            $ms->raiseError(_("Please specify a host profile name!"));
        }
        if (isset($new) && $ms->check_object_exists('hostprofile', $_POST['host_name'])) {
            $ms->raiseError(_("A host profile with that name already exists!"));
        }
        if (!isset($new) && $hostprofile->host_name != $_POST['host_name'] &&
                $ms->check_object_exists('hostprofile', $_POST['host_name'])) {
            $ms->raiseError(_("A host profile with that name already exists!"));
        }

        $hostprofile_data = $ms->filter_form_data($_POST, 'host_');

        if (!$hostprofile->update($hostprofile_data)) {
            return false;
        }

        if (!$hostprofile->save()) {
            return false;
        }

        if (isset($_POST['add_another']) && $_POST['add_another'] == 'Y') {
            return true;
        }

        $ms->set_header('Location', $rewriter->get_page_url('Host Profiles List'));
        return true;

    } // store()
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
