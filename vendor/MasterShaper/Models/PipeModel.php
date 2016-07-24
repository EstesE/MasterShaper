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
    protected static $model_icon = "icon_pipes";
    protected static $model_friendly_name = "Pipe";
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
    protected static $model_links = array(
        'ServiceLevelModel/idx' => 'sl_idx',
        'TargetModel/idx/1' => 'src_target',
        'TargetModel/idx/2' => 'dst_target',
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
        if (!$this->hasSourceTarget() && !$this->hasDestinationTarget()) {
            return true;
        }

        if ($this->hasSourceTarget() && ($src = $this->getSourceTarget()) === false) {
            static::raiseError(__CLASS__ .'::getSourceTarget() returned false!');
            return false;
        } elseif (!$this->hasSourceTarget()) {
            $src = 0;
        }

        if ($this->hasDestinationTarget() && ($dst = $this->getDestinationTarget()) === false) {
            static::raiseError(__CLASS__ .'::getDestinationTarget() returne false!');
            return false;
        } elseif (!$this->hasDestinationTarget()) {
            $dst = 0;
        }

        if (!$this->setSourceTarget($dst)) {
            static::raiseError(__CLASS__ .'::setSourceTarget() returned false!');
            return false;
        }

        if (!$this->setDestinationTarget($src)) {
            static::raiseError(__CLASS__ .'::setDestinationTarget() returned false!');
            return false;
        }

        return true;
    }

    public function hasSourceTarget()
    {
        if (!$this->hasFieldValue('src_target')) {
            return false;
        }

        return true;
    }

    public function getSourceTarget($load = false)
    {
        if (!$this->hasSourceTarget()) {
            static::raiseError(__CLASS__ .'::hasSourceTarget() returned false!');
            return false;
        }

        if (($target_idx = $this->getFieldValue('src_target')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!isset($load) || $load === false) {
            return $target_idx;
        }

        if ($target_idx === "0") {
            return "any";
        }

        try {
            $target = new \MasterShaper\Models\TargetModel(array(
                'idx' => $target_idx,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load TargetModel!', false, $e);
            return false;
        }

        return $target;
    }

    public function setSourceTarget($src_idx)
    {
        if (!isset($src_idx) || !is_numeric($src_idx)) {
            static::raiseError(__METHOD__ .'(), $src_idx parameter is invalid!');
            return false;
        }

        if (!empty($src_idx) && !\MasterShaper\Models\TargetModel::exists(array(
            FIELD_IDX => $src_idx,
        ))) {
            static::raiseError(__METHOD__ .'(), provided target does not exist!');
            return false;
        }

        if (!$this->setFieldValue('src_target', $src_idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getSourceTargetName()
    {
        if (!$this->hasSourceTarget()) {
            return "any";
        }

        if (($target = $this->getSourceTarget(true)) === false) {
            static::raiseError(__CLASS__ .'::getSourceTarget() returned false!');
            return false;
        }

        if (is_string($target) && $target === "any") {
            return $target;
        }

        if (($name = $target->getName()) === false) {
            static::raiseError(get_class($target) .'::getName() returned false!');
            return false;
        }

        return $name;
    }

    public function hasDestinationTarget()
    {
        if (!$this->hasFieldValue('dst_target')) {
            return false;
        }

        return true;
    }

    public function getDestinationTarget($load = false)
    {
        if (!$this->hasDestinationTarget()) {
            static::raiseError(__CLASS__ .'::hasDestinationTarget() returned false!');
            return false;
        }

        if (($target_idx = $this->getFieldValue('dst_target')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!isset($load) || $load === false) {
            return $target_idx;
        }

        if ($target_idx === "0") {
            return "any";
        }

        try {
            $target = new \MasterShaper\Models\TargetModel(array(
                'idx' => $target_idx,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load TargetModel!', false, $e);
            return false;
        }

        return $target;
    }

    public function setDestinationTarget($dst_idx)
    {
        if (!isset($dst_idx) || !is_numeric($dst_idx)) {
            static::raiseError(__METHOD__ .'(), $dst_idx parameter is invalid!');
            return false;
        }

        if (!empty($dst_idx) && !\MasterShaper\Models\TargetModel::exists(array(
            FIELD_IDX => $dst_idx,
        ))) {
            static::raiseError(__METHOD__ .'(), provided target does not exist!');
            return false;
        }

        if (!$this->setFieldValue('dst_target', $dst_idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getDestinationTargetName()
    {
        if (!$this->hasDestinationTarget()) {
            return "any";
        }

        if (($target = $this->getDestinationTarget(true)) === false) {
            static::raiseError(__CLASS__ .'::DestinationTarget() returned false!');
            return false;
        }

        if (is_string($target) && $target === "any") {
            return $target;
        }

        if (($name = $target->getName()) === false) {
            static::raiseError(get_class($target) .'::getName() returned false!');
            return false;
        }

        return $name;
    }

    public function hasDirection()
    {
        if (!$this->hasFieldValue('direction')) {
            return false;
        }

        return true;
    }

    public function getDirection($text = false)
    {
        if (!$this->hasDirection()) {
            static::raiseError(__CLASS__ .'::hasDirection() returned false!');
            return false;
        }

        if (($direction = $this->getFieldValue('direction')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!isset($text) || $text === false) {
            return $direction;
        }

        switch ($direction) {
            case 1:
                return "--&gt;";
                break;
            case 2:
                return "&lt;-&gt;";
                break;
        }

        return false;
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
        global $cache;

        if (!$this->hasServiceLevel()) {
            static::raiseError(__CLASS__ .'::hasServiceLevel() returned false!');
            return false;
        }

        if (($sl_idx = $this->getFieldValue('sl_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!isset($load) || $load === false) {
            return $sl_idx;
        }

        if (!$cache->has("sl_${sl_idx}")) {
            try {
                $sl = new \MasterShaper\Models\ServiceLevelModel(array(
                    FIELD_IDX => $sl_idx,
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load ServiceLevelModel!', false, $e);
                return false;
            }
            if (!$cache->add($sl, "sl_${sl_idx}")) {
                static::raiseError(get_class($cache) .'::add() returned false!');
                return false;
            }
        } else {
            if (($sl = $cache->get("sl_${sl_idx}")) === false) {
                static::raiseError(get_class($cache) .'::get() returned false!');
                return false;
            }
            if (!$sl->resetFields()) {
                static::raiseError(get_class($sl) .'::resetFields() returned false!');
                return false;
            }
        }

        return $sl;
    }

    public function getServiceLevelName()
    {
        if (!$this->hasServiceLevel()) {
            static::raiseError(__CLASS__ .'::hasServiceLevel() returned false!');
            return false;
        }

        if (($sl = $this->getServiceLevel(true)) == false) {
            static::raiseError(__CLASS__ .'::getServiceLevel() returned false!');
            return false;
        }

        if (($name = $sl->getName()) === false) {
            static::raiseError(get_class($sl) .'::getName() returned false!');
            return false;
        }

        return $name;
    }

    public function getActiveFilters()
    {
        if (($pipe_idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        try {
            $afps = new \MasterShaper\Models\AssignFilterToPipesModel(array(
                'pipe_idx' => $pipe_idx,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load AssignFilterToPipesModel!', false, $e);
            return false;
        }

        if (!$afps->hasItems()) {
            return array();
        }

        if (($items = $afps->getItems()) === false) {
            static::raiseError(get_class($afps) .'::getItems() returned false!');
            return false;
        }

        $filters = array();

        foreach ($items as $afp) {
            if (($filter = $afp->getFilter(true)) === false) {
                static::raiseError(get_class($afp) .'::getFilter() returned false!');
                return false;
            }

            array_push($filters, $filter);
        }

        return $filters;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
