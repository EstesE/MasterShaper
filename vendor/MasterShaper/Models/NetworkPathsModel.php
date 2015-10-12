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

namespace MasterShaper\Models ;

class NetworkPathsModel extends DefaultModel
{
    protected static $model_table_name = 'network_paths';
    protected static $model_column_prefix = 'netpath';
    protected static $model_has_items = true;
    protected static $model_items_model = 'NetworkPathModel';
    private $hostProfileId;

    public function getNetworkPaths()
    {
        global $ms;

        if (!isset($this->items)) {
            $ms->raiseError(__METHOD__ .'(), no items set!');
            return false;
        }

        if (empty($this->items)) {
            return array();
        }

        $filtered = array_filter($this->items, function ($item) {
            print_r($item);
            return false;
        });

        return $filtered;
    }

    public function setHostProfile($host_id)
    {
        global $ms;

        if (!isset($host_id) || empty($host_id) || !is_string($host_id)) {
            $ms->raiseError(__METHOD__ .'(), \$host_id is invalid!');
            return false;
        }

        $this->hostProfileId = $host_id;
    }

    public function getHostProfile()
    {
        global $ms;

        if (!isset($this->hostProfileId)) {
            $ms->raiseError(__METHOD__ .'(), hostProfileId has not been set yet!');
            return false;
        }

        return $this->hostProfileId;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
