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

class TargetModel extends DefaultModel
{
    protected static $model_table_name = 'targets';
    protected static $model_column_prefix = 'target';
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
        'match' => array(
            FIELD_TYPE => FIELD_STRING,
            FIELD_DEFAULT => 'IP',
        ),
        'ip' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'mac' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        $this->addRpcEnabledField('name');
        return true;
    }

    public function postSave()
    {
        global $db;

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_targets_to_targets
            WHERE
                atg_group_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        if (!isset($_POST['used']) || empty($_POST['used'])) {
            return true;
        }

        $sth = $db->prepare(
            "INSERT INTO TABLEPREFIXassign_targets_to_targets (
                atg_group_idx,
                atg_target_idx
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

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_targets_to_targets
            WHERE
                atg_group_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_targets_to_targets
            WHERE
                atg_target_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
