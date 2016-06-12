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

use MasterShaper\Models;

class OverviewView extends DefaultView
{
    protected static $view_default_mode = 'show';
    protected static $view_class_name = 'overview';
    protected $cnt_network_paths = 0;
    protected $cnt_chains = 0;
    protected $cnt_pipes = 0;
    protected $cnt_filters = 0;
    protected $avail_network_paths = array();
    protected $avail_chains = array();
    protected $avail_pipes = array();
    protected $avail_filters = array();
    protected $network_paths = array();
    protected $chains = array();
    protected $pipes = array();
    protected $filters = array();

    public function show()
    {
        global $ms, $db, $session;

        /* If authentication is enabled, check permissions */
        if ($ms->getOption("authentication") == "Y" &&
            !$ms->checkPermissions("user_show_rules")) {
            static::raiseError("You do not have enough permissions to access this module!");
            return false;
        }

        if (!($host_id = $session->getCurrentHostProfile())) {
            static::raiseError('Do not know for which host I am working for!');
            return false;
        }

        try {
            $np = new Models\NetworkPathsModel();
        } catch (\Exception $e) {
            static::raiseError('Failed to load NetworkPathsModel!');
            return false;
        }

        if ($np->hasItems()) {
            if (($network_paths = $np->getNetworkPaths($host_id)) === false) {
                static::raiseError(get_class($np) .'::getNetworkPaths() returned false!');
                return false;
            }

            foreach ($network_paths as $network_path) {
                if (($chains = $network_path->getActiveChains()) === false) {
                    static::raiseError(get_class($network_path) .'::getActiveChains() returned false!');
                    return false;
                }

                foreach ($chains as $chain) {
                    if (($pipes = $chain->getActivePipes()) === false) {
                        static::raiseError(get_class($chain) .'::getActivePipes() returned false!');
                        return false;
                    }

                    foreach ($pipes as $pipe) {
                        if (($filters = $pipe->getActiveFilters()) === false) {
                                static::raiseError(get_class($pipe) .'::getActiveFilters() returned false!');
                                return false;
                        }
                    }
                }
            }
        }

        /*if (!isset($this->smarty->registered_plugins['block']['ov_netpath'])) {
          $this->registerPlugin("block", "ov_netpath", array(&$this, "smartyOverviewNetpath"));
          }
          if (!isset($this->smarty->registered_plugins['block']['ov_chain'])) {
          $this->registerPlugin("block", "ov_chain", array(&$this, "smartyOverviewChain"));
          }
          if (!isset($this->smarty->registered_plugins['block']['ov_pipe'])) {
          $this->registerPlugin("block", "ov_pipe", array(&$this, "smartyOverviewPipe"));
          }
          if (!isset($this->smarty->registered_plugins['block']['ov_filter'])) {
          $this->registerPlugin("block", "ov_filter", array(&$this, "smartyOverviewFilter"));
          }*/

        return parent::show();
    }

    public function smartyOverviewNetpath($params, $content, &$smarty, &$repeat)
    {
        return false;
        $index = $smarty->getTemplateVars('smarty.IB.ov_netpath.index');
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_network_paths)) {
            $np_idx = $this->avail_network_paths[$index];
            $np =  $this->network_paths[$np_idx];
            $smarty->assign('netpath', $np);

            $index++;
            $smarty->assign('smarty.IB.ov_netpath.index', $index);
            $repeat = true;
        } else {
            $repeat = false;
        }
        return $content;

    } // smart_ov_netpath()

    public function smartyOverviewChain($params, $content, &$smarty, &$repeat)
    {
        return false;

        if (!array_key_exists('np_idx', $params)) {
            static::raiseError("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $np_idx = $params['np_idx'];

        $index = $smarty->getTemplateVars('smarty.IB.ov_chain.index-'. $np_idx);
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_chains[$np_idx])) {
            $chain_idx = $this->avail_chains[$np_idx][$index];
            $chain =  $this->chains[$np_idx][$chain_idx];

            $smarty->assign('chain', $chain);

            if ($chain->chain_sl_idx != 0) {
                $smarty->assign('chain_has_sl', true);
            } else {
                $smarty->assign('chain_has_sl', false);
            }

            $index++;
            $smarty->assign('smarty.IB.ov_chain.index-'. $np_idx, $index);

            $repeat = true;
        } else {
            $repeat = false;
        }

        return $content;

    } // smart_ov_chain()

    public function smartyOverviewPipe($params, $content, &$smarty, &$repeat)
    {
        return false;
        global $db, $ms;

        if (!array_key_exists('np_idx', $params)) {
            static::raiseError("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }
        if (!array_key_exists('chain_idx', $params)) {
            static::raiseError("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $np_idx = $params['np_idx'];
        $chain_idx = $params['chain_idx'];

        $index = $smarty->getTemplateVars('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx);
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_pipes[$np_idx][$chain_idx])) {
            $pipe_idx = $this->avail_pipes[$np_idx][$chain_idx][$index];
            $pipe = $this->pipes[$np_idx][$chain_idx][$pipe_idx];

            // check if pipes service level got overriden
            $ovrd_sl = $db->db_fetchSingleRow("
                    SELECT
                    apc_sl_idx
                    FROM
                    TABLEPREFIXassign_pipes_to_chains
                    WHERE
                    apc_chain_idx LIKE '". $chain_idx ."'
                    AND
                    apc_pipe_idx LIKE '". $pipe_idx ."'
                    ");

            if (isset($ovrd_sl->apc_sl_idx) && !empty($ovrd_sl->apc_sl_idx)) {
                $pipe->pipe_sl_idx = $ovrd_sl->apc_sl_idx;
            }

            $smarty->assign('pipe', $pipe);
            $smarty->assign('pipe_sl_name', $ms->getServiceLevelName($pipe->pipe_sl_idx));
            $smarty->assign('apc_idx', $pipe->apc_idx);
            $smarty->assign('pipe_sl_idx', $pipe->pipe_sl_idx);
            $smarty->assign('counter', $index+1);

            $index++;
            $smarty->assign('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx, $index);

            $repeat = true;
        } else {
            $repeat = false;
        }

        return $content;

    } // smart_ov_pipe()

    public function smartyOverviewFilter($params, $content, &$smarty, &$repeat)
    {
        return false;
        global $db;

        if (!array_key_exists('np_idx', $params)) {
            static::raiseError("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }
        if (!array_key_exists('chain_idx', $params)) {
            static::raiseError("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }
        if (!array_key_exists('pipe_idx', $params)) {
            static::raiseError("ov_netpath: missing 'pipe_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $np_idx = $params['np_idx'];
        $chain_idx = $params['chain_idx'];
        $pipe_idx = $params['pipe_idx'];

        $index = $smarty->getTemplateVars('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx);
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_filters[$np_idx][$chain_idx][$pipe_idx])) {
            $filter_idx = $this->avail_filters[$np_idx][$chain_idx][$pipe_idx][$index];
            $filter = $this->filters[$np_idx][$chain_idx][$pipe_idx][$filter_idx];

            $smarty->assign('filter', $filter);

            $index++;
            $smarty->assign('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx, $index);

            $repeat = true;
        } else {
            $repeat = false;
        }

        return $content;

    } // smart_ov_filter()

    /**
     * alter position
     *
     * gather objects current position
     * move all other objects away
     * set objects new position
     *
     * @return string
     */
    public function alterPosition()
    {
        global $ms, $db, $session;

        if (!isset($_POST['move_obj'])) {
            print "Missing object-type to alter position off";
            return false;
        }

        switch ($_POST['move_obj']) {
            case 'chain':
                $obj_table = "chains";
                $obj_col = "chain";
                $obj_parent = 'chain_netpath_idx';
                break;

            case 'netpath':
                $obj_table = "network_paths";
                $obj_col = "netpath";
                break;

            case 'pipe':
                $obj_table = "pipes";
                $obj_col = "pipe";
                $obj_parent = 'pipe_chain_idx';
                break;

            default:
                return "Unknown object-type";
                break;
        }

        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            static::raiseError(_("Id to alter position is missing or not numeric!"));
            return false;
        }

        if (!isset($_POST['to']) || !in_array($_POST['to'], array('up','down'))) {
            static::raiseError(_("Don't know in which direction we shall alter position!"));
            return false;
        }

        $idx = $_POST['id'];

        // get objects current position
        switch ($_POST['move_obj']) {
            case 'chain':
                $query = "
                    SELECT
                    chain_position as position,
                                   chain_netpath_idx as parent_idx,
                                   (
                                    /* get colums max position */
                                    SELECT
                                    MAX(chain_position)
                                    FROM
                                    TABLEPREFIXchains
                                    WHERE
                                    /* but only for our parents objects */
                                    chain_netpath_idx = (
                                        SELECT
                                        chain_netpath_idx
                                        FROM
                                        TABLEPREFIXchains
                                        WHERE
                                        chain_idx LIKE '". $idx ."'
                                        AND
                                        chain_host_idx LIKE '". $session->getCurrentHostProfile() ."'
                                        )
                                    AND
                                    chain_host_idx LIKE '". $session->getCurrentHostProfile() ."'
                                   ) as max
                                   FROM
                                   TABLEPREFIXchains
                                   WHERE
                                   chain_idx='". $idx ."'
                                   AND
                                   chain_host_idx LIKE '". $session->getCurrentHostProfile() ."'
                                   ";
                break;
            case 'pipe':
                $query = "
                    SELECT
                    apc.apc_idx as idx,
                    apc.apc_pipe_pos as position,
                    apc.apc_chain_idx parent_idx,
                    (
                     /* get colums max position */
                     SELECT
                     MAX(apc_pipe_pos)
                     FROM
                     TABLEPREFIXassign_pipes_to_chains
                     WHERE
                     apc_chain_idx LIKE (
                         SELECT
                         apc_chain_idx
                         FROM
                         TABLEPREFIXassign_pipes_to_chains
                         WHERE
                         apc_idx LIKE '". $idx ."'
                         )
                    ) as max
                        FROM
                        TABLEPREFIXpipes p
                        INNER JOIN
                        TABLEPREFIXassign_pipes_to_chains apc
                        ON
                        p.pipe_idx=apc.apc_pipe_idx
                        WHERE
                        apc.apc_idx='". $idx ."'
                        ";
                break;
            case 'netpath':
                $query = " SELECT
                        netpath_position as position,
                        (
                            /* get colums max position */
                            SELECT
                                MAX(netpath_position)
                            FROM
                                TABLEPREFIXnetwork_paths
                            WHERE
                                netpath_host_idx LIKE '". $session->getCurrentHostProfile() ."'
                        ) as max
                    FROM
                        TABLEPREFIXnetwork_paths
                    WHERE
                        netpath_idx='". $idx ."'
                    AND
                        netpath_host_idx LIKE '". $session->getCurrentHostProfile() ."'";
                break;
        }

        if (!isset($query)) {
            return;
        }

        $my_pos = $db->db_fetchSingleRow($query);

        if ($_POST['to'] == 'up') {
            /* if we are not at the top most position */
            if ($my_pos->position > 1) {
                $new_pos = $my_pos->position - 1;
            } else {
                $new_pos = -1;
            }
        } elseif ($_POST['to'] == 'down') {
            /* if we are not at the bottom most position */
            if ($my_pos->position < $my_pos->max) {
                $new_pos = $my_pos->position + 1;
            } else {
                $new_pos = -2;
            }
        } else {
            /* we make no change */
            $new_pos = $my_pos->position;
        }

        //return $new_pos ." ". $my_pos->position ." ". $my_pos->max;

        /* if no position will be changed, return */
        if ($new_pos == $my_pos->position) {
            return "ok";
        }

        /* new position can not be below null */
        if ($new_pos == 0) {
            $new_pos = 1;
        /* moving if new position is greater than 0 */
        } elseif ($new_pos > 0) {
            /* swap position with current position holder */
            switch ($_POST['move_obj']) {
                case 'chain':
                    $sth = $db->prepare("
                            UPDATE
                            TABLEPREFIXchains
                            SET
                            chain_position=?
                            WHERE
                            chain_position LIKE ?
                            AND
                            chain_netpath_idx LIKE ?
                            AND
                            chain_host_idx LIKE ?
                            ");

                    $db->execute($sth, array(
                                $my_pos->position,
                                $new_pos,
                                $my_pos->parent_idx,
                                $session->getCurrentHostProfile(),
                                ));
                    $db->freeStatement($sth);
                    break;

                case 'pipe':
                    $sth = $db->prepare("
                            UPDATE
                            TABLEPREFIXassign_pipes_to_chains
                            SET
                            apc_pipe_pos=?
                            WHERE
                            apc_pipe_pos LIKE ?
                            AND
                            apc_chain_idx LIKE ?
                            ");

                    $db->execute($sth, array(
                                $my_pos->position,
                                $new_pos,
                                $my_pos->parent_idx
                                ));
                    $db->freeStatement($sth);
                    break;

                case 'netpath':
                    $sth = $db->prepare("
                            UPDATE
                            TABLEPREFIXnetwork_paths
                            SET
                            netpath_position=?
                            WHERE
                            netpath_position LIKE ?
                            AND
                            netpath_host_idx LIKE ?
                            ");

                    $db->execute($sth, array(
                                $my_pos->position,
                                $new_pos,
                                $session->getCurrentHostProfile(),
                                ));
                    $db->freeStatement($sth);
                    break;
            }
        } else {
            /* move all object one position up/down */
            if ($_POST['to'] == 'up') {
                $dir = "-1";
            } elseif ($_POST['to'] == 'down') {
                $dir = "+1";
            }

            switch ($_POST['move_obj']) {
                case 'chain':
                    $db->query("
                            UPDATE
                            TABLEPREFIXchains
                            SET
                            chain_position = chain_position". $dir ."
                            WHERE
                            chain_netpath_idx LIKE '". $my_pos->parent_idx ."'
                            AND
                            chain_host_idx LIKE '". $session->getCurrentHostProfile() ."'
                            ");
                    break;

                case 'pipe':
                    $db->query("
                            UPDATE
                            TABLEPREFIXassign_pipes_to_chains
                            SET
                            apc_pipe_pos = apc_pipe_pos". $dir ."
                            WHERE
                            apc_chain_idx LIKE '". $my_pos->parent_idx ."'
                            ");
                    break;

                case 'netpath':
                    $sth = $db->prepare("
                            UPDATE
                            TABLEPREFIXnetwork_paths
                            SET
                            netpath_position = netpath_position". $dir ."
                            WHERE
                            netpath_host_idx LIKE '". $session->getCurrentHostProfile() ."'
                            ");
                    break;
            }
        }

        if ($new_pos == -1) {
            $new_pos = $my_pos->max;
        } elseif ($new_pos == -2) {
            $new_pos = 1;
        }

        /* finally set objects new position */
        switch ($_POST['move_obj']) {
            case 'chain':
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXchains
                        SET
                        chain_position = ?
                        WHERE
                        chain_idx LIKE ?
                        AND
                        chain_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $new_pos,
                            $idx,
                            $session->getCurrentHostProfile(),
                            ));
                $db->freeStatement($sth);
                break;

            case 'pipe':
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXassign_pipes_to_chains
                        SET
                        apc_pipe_pos = ?
                        WHERE
                        apc_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $new_pos,
                            $my_pos->idx
                            ));
                $db->freeStatement($sth);
                break;

            case 'netpath':
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXnetwork_paths
                        SET
                        netpath_position = ?
                        WHERE
                        netpath_idx LIKE ?
                        AND
                        netpath_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $new_pos,
                            $idx,
                            $session->getCurrentHostProfile(),
                            ));
                $db->freeStatement($sth);
                break;
        }

        return "ok";

    } // alterPosition()

    /**
     * handle updates
     */
    public function store()
    {
        global $ms, $db;

        if (isset($_POST['chain_sl_idx']) && is_array($_POST['chain_sl_idx'])) {
            /* save all chain service levels */
            foreach ($_POST['chain_sl_idx'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXchains
                        SET
                        chain_sl_idx=?
                        WHERE
                        chain_idx LIKE ?
                        AND
                        chain_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k,
                            $session->getCurrentHostProfile(),
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['chain_fallback_idx']) && is_array($_POST['chain_fallback_idx'])) {
            /* save all chain fallback service levels */
            foreach ($_POST['chain_fallback_idx'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXchains
                        SET
                        chain_fallback_idx = ?
                        WHERE
                        chain_idx LIKE ?
                        AND
                        chain_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k,
                            $session->getCurrentHostProfile(),
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['chain_src_target']) && is_array($_POST['chain_src_target'])) {
            /* save all chain fallback service levels */
            foreach ($_POST['chain_src_target'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXchains
                        SET
                        chain_src_target = ?
                        WHERE
                        chain_idx LIKE ?
                        AND
                        chain_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k,
                            $session->getCurrentHostProfile(),
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['chain_dst_target']) && is_array($_POST['chain_dst_target'])) {
            /* save all chain fallback service levels */
            foreach ($_POST['chain_dst_target'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXchains
                        SET
                        chain_dst_target = ?
                        WHERE
                        chain_idx LIKE ?
                        AND
                        chain_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k,
                            $session->getCurrentHostProfile(),
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['chain_direction']) && is_array($_POST['chain_direction'])) {
            /* save all chain fallback service levels */
            foreach ($_POST['chain_direction'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXchains
                        SET
                        chain_direction = ?
                        WHERE
                        chain_idx LIKE ?
                        AND
                        chain_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k,
                            $session->getCurrentHostProfile(),
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['chain_action']) && is_array($_POST['chain_action'])) {
            /* save all chain fallback service levels */
            foreach ($_POST['chain_action'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXchains
                        SET
                        chain_action = ?
                        WHERE
                        chain_idx LIKE ?
                        AND
                        chain_host_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k,
                            $session->getCurrentHostProfile(),
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['pipe_sl_idx']) && is_array($_POST['pipe_sl_idx'])) {
            /* save all pipe service levels */
            foreach ($_POST['pipe_sl_idx'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXassign_pipes_to_chains
                        SET
                        apc_sl_idx = ?
                        WHERE
                        apc_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['pipe_src_target']) && is_array($_POST['pipe_src_target'])) {
            /* save all pipe fallback service levels */
            foreach ($_POST['pipe_src_target'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXpipes
                        SET
                        pipe_src_target = ?
                        WHERE
                        pipe_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['pipe_dst_target']) && is_array($_POST['pipe_dst_target'])) {
            /* save all pipe fallback service levels */
            foreach ($_POST['pipe_dst_target'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXpipes
                        SET
                        pipe_dst_target = ?
                        WHERE
                        pipe_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['pipe_direction']) && is_array($_POST['pipe_direction'])) {
            /* save all pipe fallback service levels */
            foreach ($_POST['pipe_direction'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXpipes
                        SET
                        pipe_direction = ?
                        WHERE
                        pipe_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k
                            ));

                $db->freeStatement($sth);
            }
        }

        if (isset($_POST['pipe_action']) && is_array($_POST['pipe_action'])) {
            /* save all pipe fallback service levels */
            foreach ($_POST['pipe_action'] as $k => $v) {
                $sth = $db->prepare("
                        UPDATE
                        TABLEPREFIXpipes
                        SET
                        pipe_action = ?
                        WHERE
                        pipe_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $v,
                            $k
                            ));

                $db->freeStatement($sth);
            }
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
