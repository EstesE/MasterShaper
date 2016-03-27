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
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );
    protected static $valid_matches = array(
        'IP',
        'MAC',
        'GROUP',
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

    public function setMatch($match)
    {
        if (!isset($match) || empty($match) || !is_string($match)) {
            $this->raiseError(__METHOD__ .'(), $match parameter is invalid!');
            return false;
        }

        if (!in_array(strtoupper($match), static::$valid_matches)) {
            $this->raiseError(__METHOD__ .'(), $match parameter contains an invalid match!');
            return false;
        }

        $this->target_match = strtoupper($match);
        return true;
    }

    public function hasMatch()
    {
        if (!isset($this->target_match) || empty($this->target_match) || !is_string($this->target_match)) {
            return false;
        }

        return true;
    }

    public function getMatch()
    {
        if (!$this->hasMatch()) {
            $this->raiseError(__CLASS__ .'::hasMatch() returned false!');
            return false;
        }

        if (!in_array($this->target_match, static::$valid_matches)) {
            $this->raiseError(__METHOD__ .'(), target_match contains an invalid match!');
            return false;
        }

        return $this->target_match;
    }

    public function setIP($ip)
    {
        if (!isset($ip) || empty($ip) || !is_string($ip)) {
            $this->raiseError(__METHOD__ .'(), $ip parameter is invalid!');
            return false;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->raiseError(__METHOD__ .'(), $ip is not an valid IP address!');
            return false;
        }

        $this->target_ip = $ip;
        return true;
    }

    public function hasIP()
    {
        if (!isset($this->target_ip) ||
            empty($this->target_ip) ||
            !is_string($this->target_ip)
        ) {
            return false;
        }

        return true;
    }

    public function getIP()
    {
        if (!$this->hasIP()) {
            $this->raiseError(__CLASS__ .'::hasIP() returned false!');
            return false;
        }

        if (!filter_var($this->target_ip, FILTER_VALIDATE_IP)) {
            $this->raiseError(__METHOD__ .'(), target_ip contains an valid IP address!');
            return false;
        }

        return $this->target_ip;
    }

    public function setMAC($mac)
    {
        if (!isset($mac) || empty($mac) || !is_string($mac)) {
            $this->raiseError(__METHOD__ .'(), $mac parameter is invalid!');
            return false;
        }

        if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac)) {
            $this->raiseError(__METHOD__ .'(), $mac is not an valid MAC address!');
            return false;
        }

        $this->target_mac = $mac;
        return true;
    }

    public function hasMAC()
    {
        if (!isset($this->target_mac) ||
            empty($this->target_mac) ||
            !is_string($this->target_mac)
        ) {
            return false;
        }

        return true;
    }

    public function getMAC()
    {
        if (!$this->hasMAC()) {
            $this->raiseError(__CLASS__ .'::hasMAC() returned false!');
            return false;
        }

        if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $this->target_mac)) {
            $this->raiseError(__METHOD__ .'(), target_mac contains an valid MAC address!');
            return false;
        }

        return $this->target_mac;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
