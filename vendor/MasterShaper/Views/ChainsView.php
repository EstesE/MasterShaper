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

class ChainsView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'chains';

    public function __construct()
    {
        try {
            $chains = new \MasterShaper\Models\ChainsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ChainsModel!', true, $e);
            return;
        }

        if (!$this->setViewData($chains)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        parent::__construct();
    }

    public function smartyTargetGroupSelectLists($params, &$smarty)
    {
        if (!array_key_exists('group', $params)) {
            static::raiseError(__METHOD__ .'(), missing "group" parameter!');
            $repeat = false;
            return;
        }

        global $db;

        /* either "used" or "unused" */
        $group = $params['group'];

        if (isset($params['idx']) && is_numeric($params['idx'])) {
            $idx = $params['idx'];
        } else {
            $idx = 0;
        }

        switch ($group) {
            case 'unused':
                $sql = "SELECT
                        t.target_idx,
                        t.target_name
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
                        t.target_name
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

        $sth = $db->db_prepare($sql);

        $db->db_execute($sth, array(
            $idx
        ));

        $string = "";
        while ($row = $sth->fetch()) {
            $string.= "<option value=\"". $row->target_idx ."\">". $row->target_name ."</option>";
        }

        $db->freeStatement($sth);
        return $string;
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        $tmpl->registerPlugin(
            "function",
            "target_group_select_list",
            array(&$this, "smartyTargetGroupSelectLists"),
            false
        );

        try {
            $item = new \MasterShaper\Models\ChainModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ChainModel!', false, $e);
            return false;
        }

        $tmpl->registerPlugin(
            "block",
            "pipe_list",
            array(&$this, "smartyPipeList"),
            false
        );

        $tmpl->assign('chain', $item);
        return parent::showEdit($id, $guid);
    }

    public function smartyPipeList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.pipe_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_pipes)) {
            $pipe_idx = $this->avail_pipes[$index];
            $pipe =  $this->pipes[$pipe_idx];

            // check if pipes original service level got overruled
            if (isset($pipe->apc_sl_idx) && !empty($pipe->apc_sl_idx)) {
                $pipe->sl_in_use = $pipe->apc_sl_idx;
            } else {
                // no override
                $pipe->sl_in_use = $pipe->pipe_sl_idx;
            }

            $smarty->assign('pipe', $pipe);

            $index++;
            $smarty->assign('smarty.IB.pipe_list.index', $index);
            $repeat = true;
        } else {
            $repeat =  false;
        }

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
