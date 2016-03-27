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

namespace MasterShaper\Models ;

class ChainsModel extends DefaultModel
{
    protected static $model_table_name = 'chains';
    protected static $model_column_prefix = 'chain';
    protected static $model_has_items = true;
    protected static $model_items_model = 'ChainModel';

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        return true;
    }

    public function updatePositions($ms_objects = null)
    {
        global $session, $db;

        if (($host_idx = $session->getCurrentHostProfile()) === false) {
            $this->raiseError(get_class($session) .'::getCurrentHostProfile() returned false!');
            return false;
        }

        // get all chains assign to this network-path
        $sth = $db->prepare(
            "SELECT
                chain_idx
            FROM
                TABLEPREFIXchains
            WHERE
                chain_netpath_idx LIKE ?
            AND
                chain_host_idx LIKE ?
            ORDER BY
                chain_position ASC"
        );

        $db->execute($sth, array(
            $ms_objects,
            $host_idx,
        ));

        $pos = 1;

        $sth_update = $db->prepare(
            "UPDATE
                TABLEPREFIXchains
            SET
                chain_position=?
            WHERE
                chain_idx LIKE ?
            AND
                chain_netpath_idx LIKE ?
            AND
                chain_host_idx LIKE ?"
        );

        while ($chain = $sth->fetch()) {

            $db->execute($sth_update, array(
                $pos,
                $chain->chain_idx,
                $ms_objects,
                $host_idx,
            ));

            $pos++;
        }

        $db->freeStatement($sth_update);
        $db->freeStatement($sth);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
