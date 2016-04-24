<?php

/**
 * This file is part of MasterShaper.
 *
 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>

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

namespace MasterShaper\Models;

class ChainModel extends DefaultModel
{
    protected static $model_table_name = "chains";
    protected static $model_column_prefix = "chain";
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'name' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
            FIELD_DEFAULT => 'Y',
        ),
        'sl_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'src_target' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'dst_target' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'position' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'direction' => array(
            FIELD_TYPE => FIELD_INT,
            FIELD_DEFAULT => 2,
        ),
        'fallback_idx' => array(
            FIELD_TYPE => FIELD_INT,
            FIELD_DEFAULT => -1,
        ),
        'action' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'tc_id' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'netpath_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'host_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        $this->addRpcAction('update');
        $this->addRpcEnabledField('name');
        return true;
    }

    protected function preSave()
    {
        global $session, $db;

        /* no prework if chain already exists */
        if (isset($this->id)) {
            return true;
        }

        if (!isset($_POST['chain_netpath_idx']) || empty($_POST['chain_netpath_idx'])) {
            return true;
        }

        if (($host_idx = $session->getCurrentHostProfile()) === false) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned false!');
            return false;
        }

        // get the last chain position in the current network path
        $max_pos = $db->fetchSingleRow(
            "SELECT
                MAX(chain_position) as pos
            FROM
                TABLEPREFIXchains
            WHERE
                chain_netpath_idx LIKE '". $_POST['chain_netpath_idx'] ."'
            AND
                chain_host_idx LIKE '". $host_idx ."'"
        );

        $this->chain_position = ($max_pos->pos+1);
        $this->chain_host_idx = $host_idx;

        return true;
    }

    public function postSave()
    {
        global $ms, $db;

        if (!isset($_POST['pipe_sl_idx']) || empty($_POST['pipe_sl_idx'])) {
            return true;
        }

        if (!isset($_POST['pipe_active']) || empty($_POST['pipe_active'])) {
            return true;
        }

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_pipes_to_chains
            WHERE
                apc_chain_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        // nothing more to do for us?
        if (!isset($_POST['used']) || empty($_POST['used'])) {
            return true;
        }

        $used = $_POST['used'];
        $pipe_sl_idx = $_POST['pipe_sl_idx'];
        $pipe_active = $_POST['pipe_active'];

        $pipe_position = 1;

        $sth = $db->prepare(
            "INSERT INTO TABLEPREFIXassign_pipes_to_chains (
                apc_pipe_idx,
                apc_chain_idx,
                apc_sl_idx,
                apc_pipe_pos,
                apc_pipe_active,
                apc_guid
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
                )"
        );

        foreach ($used as $use) {
            if (empty($use)) {
                continue;
            }

            // skip if not a valid value
            if (!is_numeric($use)) {
                continue;
            }

            // override of service level?
            if (isset($pipe_sl_idx[$use]) && is_numeric($pipe_sl_idx[$use])) {
                $override_sl = $pipe_sl_idx[$use];
            } else {
                $override_sl = 0;
            }

            // override of pipe state within this chain
            if (isset($pipe_active[$use]) && in_array($pipe_active[$use], array('Y','N'))) {
                $override_active = $pipe_active[$use];
            } else {
                $override_active = 'Y';
            }


            $db->execute($sth, array(
                $use,
                $this->id,
                $override_sl,
                $pipe_position,
                $override_active,
                $ms->createGuid(),
            ));

            $pipe_position++;
        }

        $db->freeStatement($sth);
        return true;
    }

    /**
     * post delete function
     *
     * this function will be called by MsObject::delete()
     *
     * @return bool
     */
    public function postDelete()
    {
        global $db, $ms;

        $sth = $db->prepare("
                DELETE FROM
                TABLEPREFIXassign_pipes_to_chains
                WHERE
                apc_chain_idx LIKE ?
                ");

        $db->execute($sth, array(
                    $this->id
                    ));

        $db->freeStatement($sth);

        try {
            $chains = new \MasterShaper\Models\ChainsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ChainsModel!', false, $e);
            return false;
        }

        if (!$chains->updatePositions($this->chain_netpath_idx)) {
            static::raiseError(get_class($chains) .'::updatePositions() returned false!');
            return false;
        }

        return true;
    }

    public function hasServiceLevel()
    {
        if (!$this->hasFieldValue('sl_idx')) {
            return false;
        }

        return true;
    }

    public function getServiceLevel($load = false)
    {
        if (!$this->hasServiceLevel()) {
            static::raiseError(__CLASS__ .'::hasServiceLevel() returned false!');
            return false;
        }

        if (($sl_idx = $this->getFieldValue('sl_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!$load) {
            return $sl_idx;
        }

        try {
            $sl = new \MasterShaper\Models\ServiceLevelModel(array(
                FIELD_IDX => $sl_idx,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ServiceLevelModel!', false, $e);
            return false;
        }

        return $sl;
    }

    public function hasFallbackServiceLevel()
    {
        if (!$this->hasFieldValue('fallback_idx')) {
            return false;
        }

        return true;
    }

    public function getFallbackServiceLevel($load = false)
    {
        if (!$this->hasFallbackServiceLevel()) {
            static::raiseError(__CLASS__ .'::hasFallbackServiceLevel() returned false!');
            return false;
        }

        if (($sl_idx = $this->getFieldValue('fallback_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!$load) {
            return $sl_idx;
        }

        try {
            $sl = new \MasterShaper\Models\ServiceLevelModel(array(
                FIELD_IDX => $sl_idx,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ServiceLevelModel!', false, $e);
            return false;
        }

        return $sl;
    }

    public function hasNetworkPath()
    {
        if (!$this->hasFieldValue('netpath_idx')) {
            return false;
        }

        return true;
    }

    public function getNetworkPath()
    {
        if (!$this->hasNetworkPath()) {
            static::raiseError(__CLASS__ .'::hasNetworkPath() returned false!');
            return false;
        }

        if (($sl_idx = $this->getFieldValue('netpath_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $sl_idx;
    }

    public function hasSourceTarget()
    {
        if (!$this->hasFieldValue('src_target')) {
            return false;
        }

        return true;
    }

    public function getSourceTarget()
    {
        if (!$this->hasSourceTarget()) {
            static::raiseError(__CLASS__ .'::hasSourceTarget() returned false!');
            return false;
        }

        if (($host_idx = $this->getFieldValue('src_target')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $host_idx;
    }

    public function hasDestinationTarget()
    {
        if (!$this->hasFieldValue('dst_target')) {
            return false;
        }

        return true;
    }

    public function getDestinationTarget()
    {
        if (!$this->hasDestinationTarget()) {
            static::raiseError(__CLASS__ .'::hasDestinationTarget() returned false!');
            return false;
        }

        if (($host_idx = $this->getFieldValue('dst_target')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $host_idx;
    }

    public function hasDirection()
    {
        if (!$this->hasFieldValue('direction')) {
            return false;
        }

        return true;
    }

    public function getDirection()
    {
        if (!$this->hasDirection()) {
            static::raiseError(__CLASS__ .'::hasDirection() returned false!');
            return false;
        }

        if (($host_idx = $this->getFieldValue('direction')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $host_idx;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
