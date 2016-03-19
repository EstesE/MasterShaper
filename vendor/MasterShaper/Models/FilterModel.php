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

class FilterModel extends DefaultModel
{
    protected static $model_table_name = 'filters';
    protected static $model_column_prefix = 'filter';
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
        'protocol_id' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'tos' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'dscp' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'tcpflag_syn' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'tcpflag_ack' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'tcpflag_fin' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'tcpflag_rst' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'tcpflag_urg' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'tcpflag_psh' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'packet_length' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'time_use_range' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'time_start' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'time_stop' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'time_day_mon' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'time_day_tue' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'time_day_wed' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'time_day_thu' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'time_day_fri' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'time_day_sat' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'time_day_sun' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'match_ftp_data' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'match_sip' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
            FIELD_DEFAULT => 'Y',
        ),
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        $this->addRpcEnabledField('name');
        return true;
    }

    protected function postSave()
    {
        global $db;

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_ports_to_filters
            WHERE
                afp_filter_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        if (isset($_POST['used']) && !empty($_POST['used'])) {
            $sth = $db->prepare(
                "INSERT INTO TABLEPREFIXassign_ports_to_filters (
                    afp_filter_idx,
                    afp_port_idx
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

            $db->freeStatment($sth);
        }

        /* is our work done? */
        if (isset($_POST['filter_l7_used']) && !empty($_POST['filter_l7_used'])) {

            $sth = $db->prepare(
                "DELETE FROM
                    TABLEPREFIXassign_l7_protocols_to_filters
                WHERE
                    afl7_filter_idx LIKE ?"
            );

            $db->execute($sth, array(
                $this->id
            ));

            $db->freeStatement($sth);

            foreach ($_POST['filter_l7_used'] as $use) {
                $sth = $db->prepare(
                    "INSERT INTO TABLEPREFIXassign_l7_protocols_to_filters (
                        afl7_filter_idx,
                        afl7_l7proto_idx
                    ) VALUES (
                        ?,
                        ?
                    )"
                );

                if (empty($use)) {
                    continue;
                }

                $db->execute($sth, array(
                    $this->id,
                    $use
                ));

            }
            $db->freeStatement($sth);
        }

        return true;
    }

    public function postDelete()
    {
        global $db;

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_ports_to_filters
            WHERE
                afp_filter_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_l7_protocols_to_filters
            WHERE
                afl7_filter_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_filters_to_pipes
            WHERE
                apf_filter_idx LIKE ?"
        );

        $db->execute($sth, array(
            $this->id
        ));

        $db->freeStatement($sth);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
