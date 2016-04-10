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
        $this->addRpcAction('update');
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

    public function hasProtocol()
    {
        if (!$this->hasFieldValue('protocol_id')) {
            return false;
        }

        return true;
    }

    public function getProtocol()
    {
        if (!$this->hasProtocol()) {
            static::raiseError(__CLASS__ .'::hasProtocol() returned false!');
            return false;
        }

        if (($proto_idx = $this->getFieldValue('protocol_id')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $proto_idx;
    }

    public function hasTos()
    {
        if (!$this->hasFieldValue('tos')) {
            return false;
        }

        return true;
    }

    public function getTos()
    {
        if (!$this->hasTos()) {
            static::raiseError(__CLASS__ .'::hasTos() returned false!');
            return false;
        }

        if (($tos = $this->getFieldValue('tos')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $tos;
    }

    public function hasDscp()
    {
        if (!$this->hasFieldValue('dscp')) {
            return false;
        }

        return true;
    }

    public function getDscp()
    {
        if (!$this->hasDscp()) {
            static::raiseError(__CLASS__ .'::hasDscp() returned false!');
            return false;
        }

        if (($dscp = $this->getFieldValue('dscp')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $dscp;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
