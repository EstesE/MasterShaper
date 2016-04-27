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

class AssignPipeToChainModel extends DefaultModel
{
    protected static $model_table_name = 'assign_pipes_to_chains';
    protected static $model_column_prefix = 'apc';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'chain_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'pipe_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'sl_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'pipe_pos' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'pipe_active' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );

    public function hasChain()
    {
        if (!$this->hasFieldValue('chain_idx')) {
            return false;
        }

        return true;
    }

    public function getChain()
    {
        if (!$this->hasChain()) {
            static::raiseError(__CLASS__ .'::hasChain() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('chain_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setChain($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('chain_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasPipe()
    {
        if (!$this->hasFieldValue('pipe_idx')) {
            return false;
        }

        return true;
    }

    public function getPipe($load = false)
    {
        if (!$this->hasPipe()) {
            static::raiseError(__CLASS__ .'::hasPipe() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('pipe_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!$load) {
            return $value;
        }

        try {
            $pipe = new \MasterShaper\Models\PipeModel(array(
                FIELD_IDX => $value,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PipeModel!', false, $e);
            return false;
        }

        return $pipe;
    }

    public function setPipe($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('pipe_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function isPipeActive()
    {
        if (!$this->hasFieldValue('pipe_active')) {
            return false;
        }

        if (($value = $this->getFieldValue('pipe_active')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($value !== 'Y') {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
