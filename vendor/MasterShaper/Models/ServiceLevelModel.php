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
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
            FIELD_DEFAULT => 'Y',
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
            FIELD_TYPE => FIELD_STRING,
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
            FIELD_TYPE => FIELD_STRING,
        )
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        $this->addRpcAction('update');
        $this->addRpcEnabledField('name');
        $this->addRpcEnabledField('active');
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

    public function hasHtbBandwidthInRate()
    {
        if (!$this->hasFieldValue('htb_bw_in_rate')) {
            return false;
        }

        return true;
    }

    public function getHtbBandwidthInRate()
    {
        if (!$this->hasHtbBandwidthInRate()) {
            static::raiseError(__CLASS__ .'::hasHtbBandwidthInRate() returned false!');
            return false;
        }

        return $this->getFieldValue('htb_bw_in_rate');
    }

    public function hasHtbBandwidthInCeil()
    {
        if (!$this->hasFieldValue('htb_bw_in_ceil')) {
            return false;
        }

        return true;
    }

    public function getHtbBandwidthInCeil()
    {
        if (!$this->hasHtbBandwidthInCeil()) {
            static::raiseError(__CLASS__ .'::hasHtbBandwidthInCeil() returned false!');
            return false;
        }

        return $this->getFieldValue('htb_bw_in_ceil');
    }

    public function hasHtbBandwidthInBurst()
    {
        if (!$this->hasFieldValue('htb_bw_in_burst')) {
            return false;
        }

        return true;
    }

    public function getHtbBandwidthInBurst()
    {
        if (!$this->hasHtbBandwidthInBurst()) {
            static::raiseError(__CLASS__ .'::hasHtbBandwidthInBurst() returned false!');
            return false;
        }

        return $this->getFieldValue('htb_bw_in_burst');
    }

    public function hasHtbBandwidthOutRate()
    {
        if (!$this->hasFieldValue('htb_bw_out_rate')) {
            return false;
        }

        return true;
    }

    public function getHtbBandwidthOutRate()
    {
        if (!$this->hasHtbBandwidthOutRate()) {
            static::raiseError(__CLASS__ .'::hasHtbBandwidthOutRate() returned false!');
            return false;
        }

        return $this->getFieldValue('htb_bw_out_rate');
    }

    public function hasHtbBandwidthOutCeil()
    {
        if (!$this->hasFieldValue('htb_bw_out_ceil')) {
            return false;
        }

        return true;
    }

    public function getHtbBandwidthOutCeil()
    {
        if (!$this->hasHtbBandwidthOutCeil()) {
            static::raiseError(__CLASS__ .'::hasHtbBandwidthOutCeil() returned false!');
            return false;
        }

        return $this->getFieldValue('htb_bw_out_ceil');
    }

    public function hasHtbBandwidthOutBurst()
    {
        if (!$this->hasFieldValue('htb_bw_out_burst')) {
            return false;
        }

        return true;
    }

    public function getHtbBandwidthOutBurst()
    {
        if (!$this->hasHtbBandwidthOutBurst()) {
            static::raiseError(__CLASS__ .'::hasHtbBandwidthOutBurst() returned false!');
            return false;
        }

        return $this->getFieldValue('htb_bw_out_burst');
    }

    public function hasHtbPriority()
    {
        if (!$this->hasFieldValue('htb_priority')) {
            return false;
        }

        return true;
    }

    public function getHtbPriority()
    {
        if (!$this->hasHtbPriority()) {
            static::raiseError(__CLASS__ .'::hasHtbPriority() returned false!');
            return false;
        }

        return $this->getFieldValue('htb_priority');
    }

    public function hasHfscInUmax()
    {
        if (!$this->hasFieldValue('hfsc_in_umax')) {
            return false;
        }

        return true;
    }

    public function getHfscInUmax()
    {
        if (!$this->hasHfscInUmax()) {
            static::raiseError(__CLASS__ .'::hasHfscInUmax() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_in_umax');
    }

    public function hasHfscInDmax()
    {
        if (!$this->hasFieldValue('hfsc_in_dmax')) {
            return false;
        }

        return true;
    }

    public function getHfscInDmax()
    {
        if (!$this->hasHfscInDmax()) {
            static::raiseError(__CLASS__ .'::hasHfscInDmax() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_in_dmax');
    }

    public function hasHfscInRate()
    {
        if (!$this->hasFieldValue('hfsc_in_rate')) {
            return false;
        }

        return true;
    }

    public function getHfscInRate()
    {
        if (!$this->hasHfscInRate()) {
            static::raiseError(__CLASS__ .'::hasHfscInRate() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_in_rate');
    }

    public function hasHfscInUlrate()
    {
        if (!$this->hasFieldValue('hfsc_in_ulrate')) {
            return false;
        }

        return true;
    }

    public function getHfscInUlrate()
    {
        if (!$this->hasHfscInUlrate()) {
            static::raiseError(__CLASS__ .'::hasHfscInUlrate() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_in_ulrate');
    }

    public function hasHfscOutUmax()
    {
        if (!$this->hasFieldValue('hfsc_out_umax')) {
            return false;
        }

        return true;
    }

    public function getHfscOutUmax()
    {
        if (!$this->hasHfscOutUmax()) {
            static::raiseError(__CLASS__ .'::hasHfscOutUmax() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_out_umax');
    }

    public function hasHfscOutDmax()
    {
        if (!$this->hasFieldValue('hfsc_out_dmax')) {
            return false;
        }

        return true;
    }

    public function getHfscOutDmax()
    {
        if (!$this->hasHfscOutDmax()) {
            static::raiseError(__CLASS__ .'::hasHfscOutDmax() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_out_dmax');
    }

    public function hasHfscOutRate()
    {
        if (!$this->hasFieldValue('hfsc_out_rate')) {
            return false;
        }

        return true;
    }

    public function getHfscOutRate()
    {
        if (!$this->hasHfscOutRate()) {
            static::raiseError(__CLASS__ .'::hasHfscOutRate() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_out_rate');
    }

    public function hasHfscOutUlrate()
    {
        if (!$this->hasFieldValue('hfsc_out_ulrate')) {
            return false;
        }

        return true;
    }

    public function getHfscOutUlrate()
    {
        if (!$this->hasHfscOutUlrate()) {
            static::raiseError(__CLASS__ .'::hasHfscOutUlrate() returned false!');
            return false;
        }

        return $this->getFieldValue('hfsc_out_ulrate');
    }

    public function hasQdisc()
    {
        if (!$this->hasFieldValue('qdisc')) {
            return false;
        }

        return true;
    }

    public function getQdisc()
    {
        if (!$this->hasQdisc()) {
            static::raiseError(__CLASS__ .'::hasQdisc() returned false!');
            return false;
        }

        return $this->getFieldValue('qdisc');
    }

    public function hasSfqPerturb()
    {
        if (!$this->hasFieldValue('sfq_perturb')) {
            return false;
        }

        return true;
    }

    public function getSfqPerturb()
    {
        if (!$this->hasSfqPerturb()) {
            static::raiseError(__CLASS__ .'::hasSfqPerturb() returned false!');
            return false;
        }

        return $this->getFieldValue('sfq_perturb');
    }

    public function hasSfqQuantum()
    {
        if (!$this->hasFieldValue('sfq_quantum')) {
            return false;
        }

        return true;
    }

    public function getSfqQuantum()
    {
        if (!$this->hasSfqQuantum()) {
            static::raiseError(__CLASS__ .'::hasSfqQuantum() returned false!');
            return false;
        }

        return $this->getFieldValue('sfq_quantum');
    }

    public function hasEsfqPerturb()
    {
        if (!$this->hasFieldValue('esfq_perturb')) {
            return false;
        }

        return true;
    }

    public function getEsfqPerturb()
    {
        if (!$this->hasEsfqPerturb()) {
            static::raiseError(__CLASS__ .'::hasEsfqPerturb() returned false!');
            return false;
        }

        return $this->getFieldValue('esfq_perturb');
    }

    public function hasEsfqLimit()
    {
        if (!$this->hasFieldValue('esfq_limit')) {
            return false;
        }

        return true;
    }

    public function getEsfqLimit()
    {
        if (!$this->hasEsfqLimit()) {
            static::raiseError(__CLASS__ .'::hasEsfqLimit() returned false!');
            return false;
        }

        return $this->getFieldValue('esfq_limit');
    }

    public function hasEsfqDepth()
    {
        if (!$this->hasFieldValue('esfq_depth')) {
            return false;
        }

        return true;
    }

    public function getEsfqDepth()
    {
        if (!$this->hasEsfqDepth()) {
            static::raiseError(__CLASS__ .'::hasEsfqDepth() returned false!');
            return false;
        }

        return $this->getFieldValue('esfq_depth');
    }

    public function hasEsfqDivisor()
    {
        if (!$this->hasFieldValue('esfq_divisor')) {
            return false;
        }

        return true;
    }

    public function getEsfqDivisor()
    {
        if (!$this->hasEsfqDivisor()) {
            static::raiseError(__CLASS__ .'::hasEsfqDivisor() returned false!');
            return false;
        }

        return $this->getFieldValue('esfq_divisor');
    }

    public function hasEsfqHash()
    {
        if (!$this->hasFieldValue('esfq_hash')) {
            return false;
        }

        return true;
    }

    public function getEsfqHash()
    {
        if (!$this->hasEsfqHash()) {
            static::raiseError(__CLASS__ .'::hasEsfqHash() returned false!');
            return false;
        }

        return $this->getFieldValue('esfq_hash');
    }


    public function hasNetemDelay()
    {
        if (!$this->hasFieldValue('netem_delay')) {
            return false;
        }

        return true;
    }

    public function getNetemDelay()
    {
        if (!$this->hasNetemDelay()) {
            static::raiseError(__CLASS__ .'::hasNetemDelay() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_delay');
    }

    public function hasNetemJitter()
    {
        if (!$this->hasFieldValue('netem_jitter')) {
            return false;
        }

        return true;
    }

    public function getNetemJitter()
    {
        if (!$this->hasNetemJitter()) {
            static::raiseError(__CLASS__ .'::hasNetemJitter() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_jitter');
    }

    public function hasNetemRandom()
    {
        if (!$this->hasFieldValue('netem_random')) {
            return false;
        }

        return true;
    }

    public function getNetemRandom()
    {
        if (!$this->hasNetemRandom()) {
            static::raiseError(__CLASS__ .'::hasNetemRandom() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_random');
    }

    public function hasNetemLoss()
    {
        if (!$this->hasFieldValue('netem_loss')) {
            return false;
        }

        return true;
    }

    public function getNetemLoss()
    {
        if (!$this->hasNetemLoss()) {
            static::raiseError(__CLASS__ .'::hasNetemLoss() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_loss');
    }

    public function hasNetemDuplication()
    {
        if (!$this->hasFieldValue('netem_duplication')) {
            return false;
        }

        return true;
    }

    public function getNetemDuplication()
    {
        if (!$this->hasNetemDuplication()) {
            static::raiseError(__CLASS__ .'::hasNetemDuplication() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_duplication');
    }

    public function hasNetemGap()
    {
        if (!$this->hasFieldValue('netem_gap')) {
            return false;
        }

        return true;
    }

    public function getNetemGap()
    {
        if (!$this->hasNetemGap()) {
            static::raiseError(__CLASS__ .'::hasNetemGap() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_gap');
    }

    public function hasNetemReorderPercentage()
    {
        if (!$this->hasFieldValue('netem_reorder_percentage')) {
            return false;
        }

        return true;
    }

    public function getNetemReorderPercentage()
    {
        if (!$this->hasNetemReorderPercentage()) {
            static::raiseError(__CLASS__ .'::hasNetemReorderPercentage() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_reorder_percentage');
    }

    public function hasNetemReorderCorrelation()
    {
        if (!$this->hasFieldValue('netem_reorder_correlation')) {
            return false;
        }

        return true;
    }

    public function getNetemReorderCorrelation()
    {
        if (!$this->hasNetemReorderCorrelation()) {
            static::raiseError(__CLASS__ .'::hasNetemReorderCorrelation() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_reorder_correlation');
    }

    public function hasNetemDistribution()
    {
        if (!$this->hasFieldValue('netem_distribution')) {
            return false;
        }

        return true;
    }

    public function getNetemDistribution()
    {
        if (!$this->hasNetemDistribution()) {
            static::raiseError(__CLASS__ .'::hasNetemDistribution() returned false!');
            return false;
        }

        return $this->getFieldValue('netem_distribution');
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
