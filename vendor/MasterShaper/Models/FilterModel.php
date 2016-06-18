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
    private $ports;

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

    public function getProtocol($load = false)
    {
        global $cache;

        if (!$this->hasProtocol()) {
            static::raiseError(__CLASS__ .'::hasProtocol() returned false!');
            return false;
        }

        if (($proto_idx = $this->getFieldValue('protocol_id')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!$load || intval($proto_idx) == -1) {
            return $proto_idx;
        }

        if (!$cache->has("proto_${proto_idx}")) {
            try {
                $proto = new \MasterShaper\Models\ProtocolModel(array(
                    FIELD_IDX => $proto_idx,
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load ProtocolModel!', false, $e);
                return false;
            }
            if (!$cache->add($proto, "proto_${proto_idx}")) {
                static::raiseError(get_class($cache) .'::add() returned false!');
                return false;
            }
        } else {
            if (($proto = $cache->get("proto_${proto_idx}")) === false) {
                static::raiseError(get_class($cache) .'::get() returned false!');
                return false;
            }
            if (!$proto->resetFields()) {
                static::raiseError(get_class($proto) .'::resetFields() returned false!');
                return false;
            }
        }

        return $proto;
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

    public function hasPorts()
    {
        if (isset($this->ports) && !empty($ports)) {
            return true;
        }

        try {
            $ports = new \MasterShaper\Models\AssignPortToFiltersModel(array(
                'filter_idx' => $this->getIdx(),
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load AssignPortToFiltersModel!', false, $e);
            return false;
        }

        $this->ports = $ports;

        if (!$ports->hasItems()) {
            return false;
        }

        return true;
    }

    public function getPorts($load = false)
    {
        if (!$this->hasPorts()) {
            static::raiseError(__CLASS__ .'::hasPorts() returned false!');
            return false;
        }

        if (($ports = $this->ports->getItems()) === false) {
            static::raiseError(get_class($this->ports) .'::getItems() returned false!');
            return false;
        }

        if (!$load) {
            return $ports;
        }

        $result = array();

        foreach ($ports as $apf) {
            if (($port = $apf->getPort(true)) === false) {
                static::raiseError(get_class($apf) .'::getPort() returned false!');
                return false;
            }
            array_push($result, $port);
        }

        return $result;
    }

    public function isTcpFlagEnabled($flag)
    {
        $known_flags = array(
            'SYN',
            'ACK',
            'FIN',
            'RST',
            'URG',
            'PSH',
        );

        if (!in_array(strtoupper($flag), $known_flags)) {
            static::raiseError(__METHOD__ .'(), unknown flag requested!');
            return false;
        }

        $flag_field = sprintf("tcpflag_%s", strtolower($flag));

        if (!$this->hasField($flag_field)) {
            static::raiseError(__METHOD__ .'(), unknown field requested!');
            return false;
        }

        if (!$this->hasFieldValue($flag_field)) {
            return false;
        }

        if (($value = $this->getFieldValue($flag_field)) === false) {
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
