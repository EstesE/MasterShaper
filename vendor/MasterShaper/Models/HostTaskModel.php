<?php

/**
 *
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

class HostTaskModel extends DefaultModel
{
    protected static $model_table_name = 'tasks';
    protected static $model_column_prefix = 'task';
    protected static $model_friendly_name = "Task";
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'job' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'submit_time' => array(
            FIELD_TYPE => FIELD_TIMESTAMP,
        ),
        'run_time' => array(
            FIELD_TYPE => FIELD_TIMESTAMP,
        ),
        'host_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'state' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
