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

class PipesView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'pipes';

    public function __construct()
    {
        try {
            $pipes = new \MasterShaper\Models\PipesModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PipesModel!', true, $e);
            return;
        }

        if (!$this->setViewData($pipes)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        parent::__construct();
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        try {
            $item = new \MasterShaper\Models\PipeModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PipeModel!', false, $e);
            return false;
        }

        $tmpl->registerPlugin(
            "function",
            "unused_filters_select_list",
            array(&$this, "smartyUnusedFiltersSelectList"),
            false
        );
        $tmpl->registerPlugin(
            "function",
            "used_filters_select_list",
            array(&$this, "smartyUsedFiltersSelectList"),
            false
        );

        $tmpl->assign('pipe', $item);
        return parent::showEdit($id, $guid);
    }

    public function smartyUnusedFiltersSelectList($params, &$smarty)
    {
        if (!array_key_exists('pipe_idx', $params)) {
            static::raiseError("smartyUnusedFiltersSelectList: missing 'pipe_idx' parameter");
            $repeat = false;
            return;
        }

        global $db;

        if (!isset($params['pipe_idx'])) {
            $sth = $db->query(
                "SELECT
                    filter_idx, filter_name
                FROM
                    TABLEPREFIXfilters
                ORDER BY
                    filter_name"
            );
        } else {
            $sth = $db->prepare(
                "SELECT DISTINCT
                    f.filter_idx, f.filter_name
                FROM
                    TABLEPREFIXfilters f
                LEFT OUTER JOIN (
                    SELECT DISTINCT
                        apf_filter_idx, apf_pipe_idx
                    FROM
                        TABLEPREFIXassign_filters_to_pipes
                    WHERE
                        apf_pipe_idx LIKE ?
                    ) apf
                ON
                    apf.apf_filter_idx=f.filter_idx
                WHERE
                    apf.apf_pipe_idx IS NULL"
            );

            $db->execute($sth, array(
                $params['pipe_idx']
            ));
        }

        $string = "";
        while ($filter = $sth->fetch()) {
            $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
        }

        $db->freeStatement($sth);

        return $string;

    } // smartyUnusedFiltersSelectList()

    public function smartyUsedFiltersSelectList($params, &$smarty)
    {
        if (!array_key_exists('pipe_idx', $params)) {
            static::raiseError("smartyUsedFiltersSelectList: missing 'pipe_idx' parameter");
            $repeat = false;
            return;
        }

        global $db;

        $sth = $db->prepare(
            "SELECT DISTINCT
                f.filter_idx,
                f.filter_name
            FROM
                TABLEPREFIXfilters f
            INNER JOIN (
                SELECT
                    apf_filter_idx
                FROM
                    TABLEPREFIXassign_filters_to_pipes
                WHERE
                    apf_pipe_idx LIKE ?
                ) apf
            ON
                apf.apf_filter_idx=f.filter_idx"
        );

        $db->execute($sth, array(
            $params['pipe_idx']
        ));

        $string = "";
        while ($filter = $sth->fetch()) {
            $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
        }

        $db->freeStatement($sth);

        return $string;

    } // smarty_used_filters_select_list()
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
