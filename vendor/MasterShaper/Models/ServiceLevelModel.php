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

class ServiceLevelModel extends DefaultModel
{
    protected static $model_table_name = 'service_levels';
    protected static $model_column_prefix = 'sl';
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
        'htb_bw_in_rate' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'htb_bw_in_ceil' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'htb_bw_in_burst' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'htb_bw_out_rate' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'htb_bw_out_ceil' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'htb_bw_out_burst' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'htb_priority' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_in_umax' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_in_dmax' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_in_rate' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_in_ulrate' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_out_umax' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_out_dmax' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_out_rate' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'hfsc_out_ulrate' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'qdisc' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'netem_delay' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'netem_jitter' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'netem_random' => array(
            FIELD_TYPE => FIELD_INT
        ),
        'netem_distribution' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'netem_loss' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'netem_duplication' => array(
            FIELD_TYPE => FIELD_INT
        ),
        'netem_gap' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'netem_reorder_percentage' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'netem_reorder_correlation' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'sfq_perturb' => array(
            FIELD_TYPE => FIELD_INT,
            FIELD_DEFAULT => 10,
        ),
        'sfq_quantum' => array(
            FIELD_TYPE => FIELD_INT,
            FIELD_DEFAULT => 1532,
        ),
        'esfq_perturb' => array(
            FIELD_TYPE => FIELD_INT,
            FIELD_DEFAULT => 10,
        ),
        'esfq_limit' => array(
            FIELD_TYPE => FIELD_INT
        ),
        'esfq_depth' => array(
            FIELD_TYPE => FIELD_INT
        ),
        'esfq_divisor' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'esfq_hash' => array(
            FIELD_TYPE => FIELD_INT,
        )
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        $this->addRpcEnabledField('name');
        return true;
    }

    public function swapInOut()
    {
        $tmp = array(
            'sl_htb_bw_in_rate' => $this->sl_htb_bw_in_rate,
            'sl_htb_bw_in_ceil' => $this->sl_htb_bw_in_ceil,
            'sl_htb_bw_in_burst' => $this->sl_htb_bw_in_burst,
            'sl_hfsc_in_umax' => $this->sl_hfsc_in_umax,
            'sl_hfsc_in_dmax' => $this->sl_hfsc_in_dmax,
            'sl_hfsc_in_rate' => $this->sl_hfsc_in_rate,
            'sl_hfsc_in_ulrate' => $this->sl_hfsc_in_ulrate,
        );

        $this->sl_htb_bw_in_rate = $this->sl_htb_bw_out_rate;
        $this->sl_htb_bw_in_ceil = $this->sl_htb_bw_out_ceil;
        $this->sl_htb_bw_in_burst = $this->sl_htb_bw_out_burst;
        $this->sl_hfsc_in_umax = $this->sl_hfsc_out_umax;
        $this->sl_hfsc_in_dmax = $this->sl_hfsc_out_dmax;
        $this->sl_hfsc_in_rate = $this->sl_hfsc_out_rate;
        $this->sl_hfsc_in_ulrate = $this->sl_hfsc_out_ulrate;

        $this->sl_htb_bw_out_rate = $tmp['sl_htb_bw_in_rate'];
        $this->sl_htb_bw_out_ceil = $tmp['sl_htb_bw_in_ceil'];
        $this->sl_htb_bw_out_burst = $tmp['sl_htb_bw_in_burst'];
        $this->sl_hfsc_out_umax = $tmp['sl_hfsc_in_umax'];
        $this->sl_hfsc_out_dmax = $tmp['sl_hfsc_in_dmax'];
        $this->sl_hfsc_out_rate = $tmp['sl_hfsc_in_rate'];
        $this->sl_hfsc_out_ulrate = $tmp['sl_hfsc_in_ulrate'];

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
