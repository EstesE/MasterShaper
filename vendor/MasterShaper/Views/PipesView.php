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
    /**
     * Page_Pipes constructor
     *
     * Initialize the Page_Pipes class
     */
    public function __construct()
    {
        $this->rights = 'user_manage_chains';

    } // __construct()

    /**
     * display all pipes
     */
    public function showList()
    {
        global $ms, $db, $tmpl;

        $this->avail_pipes = array();
        $this->pipes = array();

        $res_pipes = $db->query("
                SELECT DISTINCT
                p.*
                FROM
                TABLEPREFIXpipes p
                LEFT JOIN
                TABLEPREFIXassign_pipes_to_chains apc
                ON
                p.pipe_idx=apc.apc_pipe_idx
                LEFT JOIN
                TABLEPREFIXchains c
                ON
                apc.apc_chain_idx=c.chain_idx
                WHERE
                c.chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
                OR /* if pipe is not assigned to chain yet */
                c.chain_host_idx IS NULL
                ORDER BY
                p.pipe_name ASC
                ");

        $cnt_pipes = 0;

        while ($pipe = $res_pipes->fetch()) {
            $this->avail_pipes[$cnt_pipes] = $pipe->pipe_idx;
            $this->pipes[$pipe->pipe_idx] = $pipe;
            $cnt_pipes++;
        }

        $tmpl->registerPlugin("block", "pipe_list", array(&$this, "smartyPipeList"));
        return $tmpl->fetch("pipes_list.tpl");

    } // showList()

    /**
     * pipe for handling
     */
    public function showEdit()
    {
        global $ms, $db, $page, $tmpl;

        if ($this->is_storing()) {
            $this->store();
        }

        if (isset($page->id) && $page->id != 0) {
            $pipe = new Pipe($page->id);
            $tmpl->assign('is_new', false);
        } else {
            $pipe = new Pipe();
            $tmpl->assign('is_new', true);
            $page->id = null;
        }

        /* get a list of chains that use this pipe */
        $sth = $db->prepare("
                SELECT
                c.chain_idx,
                c.chain_name
                FROM
                TABLEPREFIXchains c
                INNER JOIN
                TABLEPREFIXassign_pipes_to_chains apc
                ON
                apc.apc_chain_idx=c.chain_idx
                WHERE
                apc.apc_pipe_idx LIKE ?
                AND
                c.chain_host_idx LIKE ?
                ORDER BY
                c.chain_name ASC
                ");

        $db->execute($sth, array(
                    $page->id,
                    $ms->get_current_host_profile(),
                    ));

        if ($sth->rowCount() > 0) {
            $chain_use_pipes = array();
            while ($chain = $sth->fetch()) {
                $chain_use_pipes[$chain->chain_idx] = $chain->chain_name;
            }
            $tmpl->assign('chain_use_pipes', $chain_use_pipes);
        }

        $db->db_sth_free($sth);

        $tmpl->assign('pipe', $pipe);

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

        return $tmpl->fetch("pipes_edit.tpl");

    } // showEdit()

    /**
     * template function which will be called from the pipe listing template
     */
    public function smartyPipeList($params, $content, &$smarty, &$repeat)
    {
        global $ms;

        $index = $smarty->getTemplateVars('smarty.IB.pipe_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_pipes)) {

            $pipe_idx = $this->avail_pipes[$index];
            $pipe =  $this->pipes[$pipe_idx];

            $filters = $ms->getFilters($pipe->pipe_idx, true);

            if (count($filters) > 0) {
                $pipe_use_filters = array();
                foreach ($filters as $filter) {
                    $pipe_use_filters[$filter->apf_filter_idx] = $filter->filter_name;
                }
                $smarty->assign('pipe_use_filters', $pipe_use_filters);
            } else {
                $smarty->assign('pipe_use_filters', '*none*');
            }

            $smarty->assign('pipe_idx', $pipe_idx);
            $smarty->assign('pipe_name', $pipe->pipe_name);
            $smarty->assign('pipe_active', $pipe->pipe_active);

            $index++;
            $smarty->assign('smarty.IB.pipe_list.index', $index);
            $repeat = true;
        } else {
            $repeat =  false;
        }

        return $content;

    } // smartyPipeList()

    public function smartyUnusedFiltersSelectList($params, &$smarty)
    {
        if (!array_key_exists('pipe_idx', $params)) {
            $tmpl->trigger_error("smartyUnusedFiltersSelectList: missing 'pipe_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        global $db;

        if (!isset($params['pipe_idx'])) {
            $sth = $db->query("
                    SELECT
                    filter_idx, filter_name
                    FROM
                    TABLEPREFIXfilters
                    ORDER BY
                    filter_name
                    ");
        } else {
            $sth = $db->prepare("
                    SELECT DISTINCT
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
                    apf.apf_pipe_idx IS NULL
                    ");

            $db->execute($sth, array(
                        $params['pipe_idx']
                        ));

        }

        $string = "";
        while ($filter = $sth->fetch()) {
            $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
        }

        $db->db_sth_free($sth);

        return $string;

    } // smartyUnusedFiltersSelectList()

    public function smartyUsedFiltersSelectList($params, &$smarty)
    {
        if (!array_key_exists('pipe_idx', $params)) {
            $tmpl->trigger_error("smartyUsedFiltersSelectList: missing 'pipe_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        global $db;

        $sth = $db->prepare("
                SELECT DISTINCT
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
                apf.apf_filter_idx=f.filter_idx
                ");

        $db->execute($sth, array(
                    $params['pipe_idx']
                    ));

        $string = "";
        while ($filter = $sth->fetch()) {
            $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
        }

        $db->db_sth_free($sth);

        return $string;

    } // smartyUsedFiltersSelectList()

    /**
     * handle updates
     */
    public function store()
    {
        global $ms, $db, $rewriter;

        if (isset($_POST['assign-pipe']) && $_POST['assign-pipe'] == 'true') {
            return $this->assignPipeToChains();
        }

        /* load chain */
        if (isset($new) || !isset($_POST['pipe_idx'])) {
            $pipe = new Pipe;
        } else {
            $pipe = new Pipe($_POST['pipe_idx']);
        }

        isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = null;

        if (!isset($_POST['pipe_name']) || $_POST['pipe_name'] == "") {
            $ms->raiseError(_("Please enter a pipe name!"));
        }
        if (isset($new) && $ms->check_object_exists('pipe', $_POST['pipe_name'])) {
            $ms->raiseError(_("A pipe with that name already exists for that chain!"));
        }
        if (!isset($new) && $pipe->pipe_name != $_POST['pipe_name'] &&
                $ms->check_object_exists('pipe', $_POST['pipe_name'])) {
            $ms->raiseError(_("A pipe with that name already exists for that chain!"));
        }

        $pipe_data = $ms->filter_form_data($_POST, 'pipe_');

        if (!$pipe->update($pipe_data)) {
            return false;
        }

        if (!$pipe->save()) {
            return false;
        }

        if (isset($_POST['add_another']) && $_POST['add_another'] == 'Y') {
            return true;
        }

        $ms->set_header('Location', $rewriter->get_page_url('Pipes List'));
        return true;

    } // store()

    private function assignPipeToChains()
    {
        global $db, $page;

        if (!isset($_POST['chains'])) {
            return false;
        }

        if (!is_array($_POST['chains'])) {
            return false;
        }

        /* delete all connection between chains and this pipe */

        $sth = $db->prepare("
                DELETE FROM
                TABLEPREFIXassign_pipes_to_chains
                WHERE
                apc_pipe_idx LIKE ?
                ");

        $db->execute($sth, array(
                    $page->id
                    ));

        $db->db_sth_free($sth);

        foreach ($_POST['chains'] as $chain) {

            $sth = $db->prepare("
                    INSERT INTO
                    TABLEPREFIXassign_pipes_to_chains
                    (
                     apc_pipe_idx,
                     apc_chain_idx,
                     apc_sl_idx,
                     apc_pipe_active,
                     apc_pipe_pos
                    ) VALUES (
                        ?,
                        ?,
                        0,
                        'Y',
                        (
                         /* a workaround to trigger a temp-table
                            as MySQL do not allow query a to-be-
                            updated table
                          */
                         SELECT
                         MAX(apc_pipe_pos)+1
                         FROM (
                             SELECT
                             apc_pipe_pos,
                             apc_chain_idx
                             FROM
                             TABLEPREFIXassign_pipes_to_chains
                             ) as temp
                         WHERE
                         temp.apc_chain_idx LIKE ?
                        )
                            )
                            ");

            $db->execute($sth, array(
                        $page->id,
                        $chain,
                        $chain,
                        ));

            $db->db_sth_free($sth);
        }

        return true;

    } // assignPipeToChains()
} // class Page_Pipes

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
