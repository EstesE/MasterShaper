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

class ServiceLevelsModel extends DefaultModel
{
    protected static $model_table_name = 'service_levels';
    protected static $model_column_prefix = 'sl';
    protected static $model_has_fields = true;
    protected static $model_item_model = 'servicelevel';

    public function getServiceLevels()
    {
        global $ms;

        if (!isset($this->items)) {
            $ms->raiseError(__METHOD__ .'(), no items set!');
            return false;
        }

        if (empty($this->items)) {
            return array();
        }

        /*$filtered = array_filter($this->items, function ($item) {
            print_r($item);
            return false;
        });*/

        return $this->items;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
