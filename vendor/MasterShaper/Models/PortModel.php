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

class PortModel extends DefaultModel
{
    protected static $model_table_name = 'ports';
    protected static $model_column_prefix = 'port';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'name' => array(
            FIELD_TYPE => FIELD_STR,
        ),
        'desc' => array(
            FIELD_TYPE => FIELD_STR,
        ),
        'number' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'user_defined' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );

    public function postDelete()
    {
        global $db;

        $sth = $db->db_prepare(
            "DELETE FROM
                TABLEPREFIXassign_ports_to_filters
            WHERE
                afp_port_idx LIKE ?"
        );

        $db->db_execute($sth, array(
            $this->id
        ));

        $db->db_sth_free($sth);
        return true;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
