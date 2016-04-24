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

class TargetsView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'targets';
    private $targets_avail;
    private $targets_used;

    public function __construct()
    {
        try {
            $targets = new \MasterShaper\Models\TargetsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load TargetsModel!', true, $e);
            return;
        }

        if (!$this->setViewData($targets)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        parent::__construct();
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        try {
            $item = new \MasterShaper\Models\TargetModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load TargetModel!', false, $e);
            return false;
        }

        $tmpl->registerPlugin(
            "block",
            "target_group_select_list",
            array(&$this, "smartyTargetGroupSelectLists"),
            false
        );

        $tmpl->assign('target', $item);

        if (($this->targets_avail = $this->getTargets('avail', $item->getId())) === false) {
            static::raiseError(__CLASS__ .'::getTargets() returned false!');
            return false;
        }

        if (($this->targets_used = $this->getTargets('used', $item->getId())) === false) {
            static::raiseError(__CLASS__ .'::getTargets() returned false!');
            return false;
        }

        return parent::showEdit($id, $guid);
    }

    public function smartyTargetGroupSelectLists($params, $content, &$smarty, &$repeat)
    {
        if (!array_key_exists('group', $params)) {
            static::raiseError(__METHOD__ .'(), group parameter is missing!');
            $repeat = false;
            return false;
        }

        if ($params['group'] != 'avail' && $params['group'] != 'used') {
            static::raiseError(__METHOD__ .'(), group parameter is invalid!');
            $repeat = false;
            return false;
        }

        $index = $smarty->getTemplateVars("smarty.IB.{$params['group']}_item_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($params['group'] == 'avail') {
            $targets =& $this->targets_avail;
        } elseif ($params['group'] == 'used') {
            $targets =& $this->targets_used;
        }

        if (!isset($targets) || empty($targets) || $index >= count($targets)) {
            $repeat = false;
            return $content;
        }

        $smarty->assign("item", $targets[$index]);

        $index++;
        $smarty->assign("smarty.IB.{$params['group']}_item_list.index", $index);
        $repeat = true;

        return $content;
    }

    protected function getTargets($group, $idx)
    {
        if (!isset($group) || empty($group) || !is_string($group)) {
            static::raiseError(__METHOD__ .'(), $group parameter is invalid!');
            return;
        }

        global $db;

        switch ($group) {
            case 'avail':
                $sql = "SELECT
                        t.target_idx,
                        t.target_guid
                    FROM
                        TABLEPREFIXtargets t
                    LEFT JOIN
                        TABLEPREFIXassign_targets_to_targets atg
                    ON
                        t.target_idx=atg.atg_target_idx
                    WHERE
                        atg.atg_group_idx NOT LIKE ?
                    OR
                        ISNULL(atg.atg_group_idx)
                    ORDER BY
                        t.target_name ASC";
                break;
            case 'used':
                $sql = "SELECT
                        t.target_idx,
                        t.target_guid
                    FROM
                        TABLEPREFIXassign_targets_to_targets atg
                    LEFT JOIN
                        TABLEPREFIXtargets t
                    ON
                        t.target_idx = atg.atg_target_idx
                    WHERE
                        atg_group_idx LIKE ?
                    ORDER BY
                        t.target_name ASC";
                break;
        }

        $sth = $db->prepare($sql);

        $db->execute($sth, array(
            $idx
        ));

        $result = array();

        while ($row = $sth->fetch()) {
            try {
                $target = new \MasterShaper\Models\TargetModel(array(
                    'idx' => $row->target_idx,
                    'guid' => $row->target_guid
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load TargetModel!');
                return false;
            }

            array_push($result, $target);
        }

        $db->freeStatement($sth);
        return $result;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
