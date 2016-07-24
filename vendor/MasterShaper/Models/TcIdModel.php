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

class TcIdModel extends DefaultModel
{
    protected static $model_table_name = 'tc_ids';
    protected static $model_column_prefix = 'id';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'pipe_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'chain_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'host_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'if' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'tc_id' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'color' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );
    protected static $model_links = array(
        'PipeModel/idx' => 'pipe_idx',
        'ChainModel/idx' => 'chain_idx',
        'HostProfileModel/idx' => 'host_idx',
    );
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
