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
            FIELD_SET => 'setIP',
            FIELD_GET => 'getIP',
        ),
        'mac' => array(
            FIELD_TYPE => FIELD_STRING,
            FIELD_SET => 'setMAC',
            FIELD_GET => 'getMAC',
        ),
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );
    protected static $model_links = array(
        'AssignTargetToGroupsModel/target_idx',
        'AssignTargetToGroupsModel/group_idx',
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
        $this->addRpcAction('update');
        $this->addRpcEnabledField('name');
        $this->addRpcEnabledField('active');
        $this->addVirtualField('members');
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
            static::raiseError(__METHOD__ .'(), $match parameter is invalid!');
            return false;
        }

        if (!in_array(strtoupper($match), static::$valid_matches)) {
            static::raiseError(__METHOD__ .'(), $match parameter contains an invalid match!');
            return false;
        }

        $this->model_values['match'] = strtoupper($match);
        return true;
    }

    public function hasMatch()
    {
        if (!isset($this->model_values['match']) ||
            empty($this->model_values['match']) ||
            !is_string($this->model_values['match'])
        ) {
            return false;
        }

        return true;
    }

    public function getMatch()
    {
        if (!$this->hasMatch()) {
            static::raiseError(__CLASS__ .'::hasMatch() returned false!');
            return false;
        }

        if (!in_array($this->model_values['match'], static::$valid_matches)) {
            static::raiseError(__METHOD__ .'(), target_match contains an invalid match!');
            return false;
        }

        return $this->model_values['match'];
    }

    public function setIP($ip)
    {
        if (!isset($ip) || empty($ip) || !is_string($ip)) {
            $this->model_values['ip'] = null;
            return true;
        }

        if (!static::isValidIp($ip)) {
            static::raiseError(__METHOD__ .'(), $ip is not valid!');
            return false;
        }

        $this->model_values['ip'] = $ip;
        return true;
    }

    public static function isValidIp($ip)
    {
        if (!isset($ip) || empty($ip) || !is_string($ip)) {
            static::raiseError(__METHOD__ .'(), $ip parameter is invalid!');
            return false;
        }

        //
        // ranges
        //
        if (strstr($ip, '-') !== false) {
            if (($range = explode('-', $ip)) === false ||
                !isset($range) ||
                empty($range) ||
                !is_array($range) ||
                count($range) < 2 ||
                count($range) > 2 ||
                !isset($range[0]) ||
                empty($range[0]) ||
                !is_string($range[0]) ||
                !isset($range[1]) ||
                empty($range[1]) ||
                !is_string($range[1])
            ) {
                static::raiseError(__METHOD__ .'(), explode() $ip parameter failed!');
                return false;
            }

            if (!static::isIpv4Address($range[0]) && !static::isIpv6Address($range[0])) {
                static::raiseError(
                    __METHOD__ .'(), starting address in range is neither a valid v4 nor v6 IP address!'
                );
                return false;
            }

            if (!static::isIpv4Address($range[1]) && !static::isIpv6Address($range[1])) {
                static::raiseError(
                    __METHOD__ .'(), ending address in range is neither a valid v4 nor v6 IP address!'
                );
                return false;
            }
        //
        // CIDR x.x.x.x/x.x.x.x or x.x.x.x/y
        //
        } elseif (strstr($ip, '/') !== false) {
            if (($cidr = explode('/', $ip)) === false ||
                !isset($cidr) ||
                empty($cidr) ||
                !is_array($cidr) ||
                count($cidr) < 2 ||
                count($cidr) > 2 ||
                !isset($cidr[0]) ||
                empty($cidr[0]) ||
                !is_string($cidr[0]) ||
                !isset($cidr[1]) ||
                is_null($cidr[1]) || /* because a netmask of 0 is valid! */
                (!is_string($cidr[1]) && !is_numeric($cidr[1]))
            ) {
                static::raiseError(__METHOD__ .'(), explode() $ip parameter failed!');
                return false;
            }

            if (!static::isIpv4Address($cidr[0]) && !static::isIpv6Address($cidr[0])) {
                static::raiseError(__METHOD__ .'(), IP address is neither a valid v4 nor v6 IP address!');
                return false;
            }

            if (is_numeric($cidr[1]) && (
                $cidr[1] < 0 ||
                $cidr[1] > 32 ||
                !is_int((int) $cidr[1])
            )) {
                static::raiseError(__METHOD__ .'(), $ip parameter contains an invalid netmask!');
                return false;
            } elseif (!is_numeric($cidr[1]) &&
                is_string($cidr[1]) &&
                !static::isIpv4Address($cidr[1]) &&
                !static::isIpv6Address($cidr[1])
            ) {
                static::raiseError(__METHOD__ .'(), netmask is neither a valid v4 nor v6 IP address!');
                return false;
            }
        //
        // pure v4 or v6 IP addresses
        //
        } elseif (!static::isIpv4Address($ip) && !static::isIpv6Address($ip)) {
            static::raiseError(__METHOD__ .'(), IP address is neither a valid v4 nor v6 IP address!');
            return false;
        }

        return true;
    }

    public function hasIP()
    {
        if (!isset($this->model_values['ip']) ||
            empty($this->model_values['ip']) ||
            !is_string($this->model_values['ip'])
        ) {
            return false;
        }

        return true;
    }

    public function getIP()
    {
        if (!$this->hasIP()) {
            static::raiseError(__CLASS__ .'::hasIP() returned false!');
            return false;
        }

        if (!static::isValidIp($this->model_values['ip'])) {
            static::raiseError(__METHOD__ .'(), isValidIp() returned false!');
            return false;
        }

        return $this->model_values['ip'];
    }

    public function setMAC($mac)
    {
        if (!isset($mac) || empty($mac) || !is_string($mac)) {
            $this->model_values['mac'] = null;
            return true;
        }

        if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac)) {
            static::raiseError(__METHOD__ .'(), $mac is not an valid MAC address!');
            return false;
        }

        $this->model_values['mac'] = $mac;
        return true;
    }

    public function hasMAC()
    {
        if (!isset($this->model_values['mac']) ||
            empty($this->model_values['mac']) ||
            !is_string($this->model_values['mac'])
        ) {
            return false;
        }

        return true;
    }

    public function getMAC()
    {
        if (!$this->hasMAC()) {
            static::raiseError(__CLASS__ .'::hasMAC() returned false!');
            return false;
        }

        if (!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $this->model_values['mac'])) {
            static::raiseError(__METHOD__ .'(), target_mac contains an valid MAC address!');
            return false;
        }

        return $this->model_values['mac'];
    }

    public static function isIpv4Address($address)
    {
        if (!isset($address) || empty($address) || !is_string($address)) {
            static::raiseError(__METHOD__ .'(), $address parameter is invalid!', true);
            return false;
        }

        if (!preg_match('/^\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}$/', $address)) {
            return false;
        }

        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        return true;
    }

    public static function isIpv6Address($address)
    {
        if (!isset($address) || empty($address) || !is_string($address)) {
            static::raiseError(__METHOD__ .'(), $address parameter is invalid!', true);
            return false;
        }

        if (!preg_match(
            '/^(((?=.*(::))(?!.*\3.+\3))\3?|([\dA-F]{1,4}(\3|:\b|$)|\2))'
            .'(?4){5}((?4){2}|(((2[0-4]|1\d|[1-9])?\d|25[0-5])\.?\b){4})\z/i',
            $address
        )) {
            return false;
        }

        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        return true;
    }

    public function getMembers()
    {

    }

    public function setMembers($members)
    {
        global $ms, $cache;

        if (!isset($members) || (!empty($members) && !is_string($members))) {
            static::raiseError(__METHOD__ .'(), $members parameter is invalid!');
            return false;
        }

        if (!empty($members) && ($pairs = explode(',', $members)) === false) {
            static::raiseError(__METHOD__ .'(), explode() returned false!');
            return false;
        }

        if (!empty($members) && (!isset($pairs) || !is_array($pairs))) {
            static::raiseError(__METHOD__ .'(), explode() returned invalid data!');
            return false;
        }

        if (!$cache->has("atgs_". $this->getId())) {
            try {
                $atgs = new \MasterShaper\Models\AssignTargetToGroupsModel(array(
                    'group_idx' => $this->getId()
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load AssignTargetToGroupsModel!', false, $e);
                return false;
            }
            if (!$cache->add($atgs, true)) {
                static::raiseError(get_class($cache) .'::add() returned false!');
                return false;
            }
        } else {
            if (($sl = $cache->get("atgs_". $this->getId())) === false) {
                static::raiseError(get_class($cache) .'::get() returned false!');
                return false;
            }
        }

        if (!$atgs->delete()) {
            static::raiseError(get_class($atgs) .'::delete() returned false!');
            return false;
        }

        if (empty($members)) {
            return true;
        }

        foreach ($pairs as $pair) {
            if (!isset($pair) || empty($pair) || !is_string($pair)) {
                static::raiseError(__METHOD__ .'(), received an invalid pair!');
                return false;
            }
            if ((list($idx, $guid) = explode(':', $pair)) === false) {
                static::raiseError(__METHOD__ .'(), explode() returned false!');
                return false;
            }
            if (!isset($idx) || empty($idx) || !is_numeric($idx) || !$ms->isValidId($idx) ||
                !isset($guid) || empty($guid) || !is_string($guid) || !$ms->isValidGuidSyntax($guid)
            ) {
                static::raiseError(__METHOD__ .'(), explode() returned invalid data!');
                return false;
            }
            if ($idx === $this->getId()) {
                static::raiseError(__METHOD__ .'(), target can not be its own group member!');
                return false;
            }
            if (!\MasterShaper\Models\TargetModel::exists(array(
                'idx' => $idx,
                'guid' => $guid
            ))) {
                static::raiseError(__METHOD__ .'(), no such target model exists!');
                return false;
            }

            try {
                $atg = new \MasterShaper\Models\AssignTargetToGroupModel;
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load AssignTargetToGroupModel!');
                return false;
            }

            if (!$atg->setGroup($this->getId())) {
                static::raiseError(get_class($atg) .'::setGroup() returned false!');
                return false;
            }

            if (!$atg->setTarget($idx)) {
                static::raiseError(get_class($atg) .'::setTarget() returned false!');
                return false;
            }

            if (!$atg->save()) {
                static::raiseError(get_class($atg) .'::save() returned false!');
                return false;
            }
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
