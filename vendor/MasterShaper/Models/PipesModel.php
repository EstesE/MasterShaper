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

class PipesModel extends DefaultModel
{
    protected static $model_table_name = 'pipes';
    protected static $model_column_prefix = 'pipe';
    protected static $model_has_items = true;
    protected static $model_items_model = 'PipeModel';

    public function updatePositions($ms_objects = null)
    {
        global $db;

        if (!isset($ms_objects) or empty($ms_objects)) {
            return true;
        }

        // get all pipes used by chain
        $sth = $db->prepare(
            "SELECT
                apc_pipe_idx as pipe_idx
            FROM
                TABLEPREFIXassign_pipes_to_chains
            WHERE
                apc_chain_idx LIKE ?
            ORDER BY
                apc_pipe_pos ASC"
        );

        $sth_update = $db->db_prepare(
            "UPDATE
                TABLEPREFIXassign_pipes_to_chains
            SET
                apc_pipe_pos=?
            WHERE
                apc_pipe_idx=?
            AND
                apc_chain_idx=?"
        );

        // loop through all provided chain ids
        foreach ($ms_objects as $chain) {

            $db->execute($sth, array(
                $chain
            ));

            // update all pipes position assign to this chain
            $pos = 1;

            while ($pipe = $sth->fetch()) {

                $db->execute($sth_update, array(
                    $pos,
                    $pipe->pipe_idx,
                    $chain
                ));

                $pos++;
            }

        }

        $db->freeStatement($sth);
        $db->freeStatement($sth_update);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
