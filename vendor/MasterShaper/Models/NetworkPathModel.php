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

class NetworkPathModel extends DefaultModel
{
    protected static $model_table_name = 'network_paths';
    protected static $model_column_prefix = 'netpath';
    /*protected static $model_has_items = true;
    protected static $model_item_models = array(
        'chain',
    );
    protected static $model_ignore_child_on_clone = true;*/
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
        'if1' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'if1_inside_gre' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'if2' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'if2_inside_gre' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'position' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'imq' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
            FIELD_DEFAULT => 'Y',
        ),
        'host_idx' => array(
            FIELD_TYPE => FIELD_INT,
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

    public function postDelete()
    {
        try {
            $netpaths = new \MasterShaper\Models\NetworkPathsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkPathsModel!', false, $e);
            return false;
        }

        if (!$netpaths->updatePositions()) {
            static::raiseError(get_class($netpaths) .'::updatePositions() returned false!');
            return false;
        }

        return true;
    }

    public function preSave()
    {
        global $session, $db;

        if (($host_idx = $session->getCurrentHostProfile()) === false) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned false!');
            return false;
        }

        if (!is_numeric($host_idx)) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned invalid data!');
            return false;
        }

        /* no prework if chain already exists */
        if (isset($this->id)) {
            return true;
        }

        $max_pos = $db->fetchSingleRow(
            "SELECT
                MAX(netpath_position) as pos
            FROM
                TABLEPREFIXnetwork_paths
            WHERE
                netpath_host_idx LIKE '{$host_idx}'"
        );

        if (isset($max_pos) &&
            !empty($max_pos) &&
            !is_object($max_pos) &&
            isset($max_pos->pos) &&
            !empty($max_pos->pos)
        ) {
            $this->netpath_position = ($max_pos->pos+1);
        }
        $this->netpath_host_idx = $host_idx;
        return true;
    }

    /**
     * get next chain position
     *
     * this function returns the next free chain position
     * available for the actual network path.
     *
     */
    public function getNextChainPosition()
    {
        global $session, $db;

        if (($host_idx = $session->getCurrentHostProfile()) === false) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned false!');
            return false;
        }

        if (!is_numeric($host_idx)) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned invalid data!');
            return false;
        }

        if (($netpath_idx = $this->getId()) === false) {
            static::raiseError(__CLASS__ .'::getId() returned false!');
            return false;
        }

        if (!is_numeric($netpath_idx)) {
            static::raiseError(__CLASS__ .'::getId() returned invalid data!');
            return false;
        }

        $max_pos = $db->fetchSingleRow(
            "SELECT
                MAX(chain_position) as pos
            FROM
                TABLEPREFIXchains
            WHERE
                chain_netpath_idx LIKE '{$netpath_idx}'
            AND
                chain_host_idx LIKE '{$host_idx}'"
        );

        if (isset($max_pos) &&
            !empty($max_pos) &&
            !is_object($max_pos) &&
            isset($max_pos->pos) &&
            !empty($max_pos->pos)
        ) {
            return ($max_pos->pos+1);
        }

        return 0;

    } // get_next_chain_position()

    protected function postSave()
    {
        global $session, $db;

        if (($host_idx = $session->getCurrentHostProfile()) === false) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned false!');
            return false;
        }

        if (!is_numeric($host_idx)) {
            static::raiseError(get_class($session) .'::getCurrentHostProfile() returned invalid data!');
            return false;
        }

        if (!isset($_POST['chain_active']) || empty($_POST['chain_active'])) {
            return true;
        }

        $used = $_POST['used'];
        $chain_active = $_POST['chain_active'];

        $chain_position = 1;

        foreach ($used as $use) {
            if (empty($use)) {
                continue;
            }
            // skip if not a valid value
            if (!is_numeric($use)) {
                continue;
            }

            $sth = $db->db_prepare(
                "UPDATE
                    TABLEPREFIXchains
                SET
                    chain_position = ?
                WHERE
                    chain_idx LIKE ?
                AND
                    chain_host_idx LIKE ?"
            );

            $db->db_execute($sth, array(
                $chain_position,
                $use,
                $host_idx
            ));

            $db->db_sth_free($sth);
            $chain_position++;
        }

        return true;
    }

    public function isImq()
    {
        if (!$this->hasFieldValue('imq')) {
            return false;
        }

        if (($value = $this->getFieldValue('imq')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($value != 'Y') {
            return false;
        }

        return true;
    }

    public function hasInterface1()
    {
        if (!$this->hasFieldValue('if1')) {
            return false;
        }

        return true;
    }

    public function getInterface1($load = false)
    {
        if (!$this->hasInterface1()) {
            static::raiseError(__METHOD__ .'::hasInterface1() returned false!');
            return false;
        }

        if (($if1 = $this->getFieldValue('if1')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!$load) {
            return $if1;
        }

        try {
            $if = new \MasterShaper\Models\NetworkInterfaceModel(array(
                FIELD_IDX =>  $if1,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkInterfaceModel!');
            return false;
        }

        return $if;
    }

    public function isInterface1InsideGre()
    {
        if (!$this->hasFieldValue('if1_inside_gre')) {
            return false;
        }

        if (($inside_gre = $this->getFieldValue('if1_inside_gre')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($inside_gre != 'Y') {
            return false;
        }

        return true;
    }

    public function hasInterface2()
    {
        if (!$this->hasFieldValue('if2')) {
            return false;
        }

        return true;
    }

    public function getInterface2($load = false)
    {
        if (!$this->hasInterface2()) {
            static::raiseError(__METHOD__ .'::hasInterface2() returned false!');
            return false;
        }

        if (($if2 = $this->getFieldValue('if2')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!$load) {
            return $if2;
        }

        try {
            $if = new \MasterShaper\Models\NetworkInterfaceModel(array(
                FIELD_IDX =>  $if2,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkInterfaceModel!');
            return false;
        }

        return $if;
    }

    public function isInterface2InsideGre()
    {
        if (!$this->hasFieldValue('if2_inside_gre')) {
            return false;
        }

        if (($inside_gre = $this->getFieldValue('if2_inside_gre')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($inside_gre != 'Y') {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
