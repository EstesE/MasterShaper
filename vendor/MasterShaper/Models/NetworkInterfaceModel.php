<?php

/**

 * This file is part of MasterShaper.

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

class NetworkInterfaceModel extends DefaultModel
{
    protected static $model_table_name = 'interfaces';
    protected static $model_column_prefix = 'if';
    protected static $model_icon = "icon_interfaces";
    protected static $model_friendly_name = "Network Interface";
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
        'speed' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'fallback_idx' => array(
            FIELD_TYPE => FIELD_INT,
            FIELD_DEFAULT => 0,
        ),
        'ifb' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
            FIELD_DEFAULT => 'Y',
        ),
        'host_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
    );
    protected static $model_links = array(
        'ServiceLevelModel/idx' => 'fallback_idx',
        'HostProfileModel/idx' => 'host_idx',
        'NetworkPathsModel/if1' => 'idx',
        'NetworkPathsModel/if2' => 'idx',
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
        global $session;

        if (isset($this->if_host_idx) && !empty($this->if_host_idx)) {
            return true;
        }

        if (($host_idx = $session->getCurrentHostProfile()) === false) {
            $this->raiseError(get_class($session) .'::getCurrentHostProfile() returned false!');
            return false;
        }
    
        $this->if_host_idx = $host_idx;
        return true;
    }

    public function hasSpeed()
    {
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField('speed')) {
            static::raiseError(__METHOD__ .'(), this model has no "speed" field!');
            return false;
        }

        if (!isset($this->model_values['speed']) ||
            empty($this->model_values['speed'])
        ) {
            return false;
        }

        return true;
    }

    public function getSpeed()
    {
        if (!$this->hasSpeed()) {
            static::raiseError(__CLASS__ .'::hasSpeed() returned false!');
            return false;
        }

        return $this->model_values['speed'];
    }

    public function isIfb()
    {
        if (!$this->hasFieldValue('ifb')) {
            return false;
        }

        if ($this->getFieldValue('ifb') != 'Y') {
            return false;
        }

        return true;
    }

    public function hasFallback()
    {
        if (!$this->hasFieldValue('fallback_idx')) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
