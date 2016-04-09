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

class PipeModel extends DefaultModel
{
    protected static $model_table_name = 'pipes';
    protected static $model_column_prefix = 'pipe';
    protected static $model_fields = array(
        'idx'  => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'name' => array(
            FIELD_TYPE => FIELD_STRING,
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
        'direction' => array(
            FIELD_TYPE => FIELD_INT,
            FIELD_DEFAULT => 2,
        ),
        'action' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
            FIELD_DEFAULT => 'Y',
        ),
        'tc_id' => array(
            FIELD_TYPE => FIELD_STRING,
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

    public function preSave()
    {
        global $db;

        /* no prework if chain already exists */
        if (isset($this->id)) {
            return true;
        }

        return true;
    }

    public function postSave()
    {
        global $db;

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_filters_to_pipes
            WHERE
                apf_pipe_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        if (!isset($_POST['used']) || empty($_POST['used'])) {
            return true;
        }

        $sth = $db->prepare(
            "INSERT INTO TABLEPREFIXassign_filters_to_pipes (
                apf_pipe_idx,
                apf_filter_idx
            ) VALUES (
                ?,
                ?
            )"
        );

        foreach ($_POST['used'] as $use) {
            if (empty($use)) {
                continue;
            }

            $db->execute($sth, array(
                $this->id,
                $use
            ));

        }

        $db->freeStatement($sth);
        return true;
    }

    public function postDelete()
    {
        global $db;

        // remove all filter associations
        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_filters_to_pipes
            WHERE
                apf_pipe_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        // get all chains this pipe was associated with
        $result = $db->prepare(
            "SELECT
                apc_chain_idx as chain_idx
            FROM
                TABLEPREFIXassign_pipes_to_chains
            WHERE
                apc_pipe_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $chains = array();
        while ($chain = $result->fetch()) {
            array_push($chains, $chain->chain_idx);
        }

        // remove all chains associations
        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_pipes_to_chains
            WHERE
                apc_pipe_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        if (empty($chains)) {
            return true;
        }

        try {
            $pipes = new \MasterShaper\Models\PipesModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load PipesModel!', false, $e);
            return false;
        }

        if (!$pipes->updatePositions($chains)) {
            $this->raiseError(get_class($pipes) .'::updatePositions() returned false!');
            return false;
        }

        return true;
    }

    public function swapTargets()
    {
        $tmp = $this->pipe_src_target;
        $this->pipe_src_target = $this->pipe_dst_target;
        $this->pipe_dst_target = $tmp;
        return true;

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

        if (($target_idx = $this->getFieldValue('src_target')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $target_idx;
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

        if (($target_idx = $this->getFieldValue('dst_target')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $target_idx;
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

        if (($direction = $this->getFieldValue('direction')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $direction;
    }

    public function hasServiceLevel()
    {
        if (!$this->hasFieldValue('sl_idx')) {
            return false;
        }

        return true;
    }

    public function getServiceLevel()
    {
        if (!$this->hasServiceLevel()) {
            static::raiseError(__CLASS__ .'::hasServiceLevel() returned false!');
            return false;
        }

        if (($sl_idx = $this->getFieldValue('sl_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $sl_idx;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
