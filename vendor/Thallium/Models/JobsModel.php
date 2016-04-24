<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 */

namespace Thallium\Models ;

class JobsModel extends DefaultModel
{
    protected static $model_table_name = 'jobs';
    protected static $model_column_prefix = 'job';
    protected static $model_has_items = true;
    protected static $model_items_model = 'JobModel';

    public function deleteExpiredJobs($timeout)
    {
        global $db;

        if (!isset($timeout) || empty($timeout) || !is_numeric($timeout)) {
            static::raiseError(__METHOD__ .', parameter needs to be an integer!');
            return false;
        }

        $now = microtime(true);
        $oldest = $now-$timeout;

        $sql =
            "DELETE FROM
                TABLEPREFIXjobs
            WHERE
                UNIX_TIMESTAMP(job_time) < ?";

        if (!($sth = $db->prepare($sql))) {
            static::raiseError(__METHOD__ .', failed to prepare query!');
            return false;
        }

        if (!($db->execute($sth, array($oldest)))) {
            static::raiseError(__METHOD__ .', failed to execute query!');
            return false;
        }

        return true;
    }

    public function getPendingJobs()
    {
        global $db;

        $sql = sprintf(
            "SELECT
                job_idx
            FROM
                TABLEPREFIX%s
            WHERE
                job_in_processing <> 'Y'",
            static::$model_table_name
        );

        if (($sth = $db->prepare($sql)) === false) {
            static::raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!$db->execute($sth)) {
            static::raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        $jobs = array();
        if ($sth->rowCount() < 1) {
            return $jobs;
        }

        while ($row = $sth->fetch()) {
            try {
                $job = new \Thallium\Models\JobModel(array(
                    'idx' => $row->job_idx
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load JobModel!');
                return false;
            }
            array_push($jobs, $job);
        }

        return $jobs;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
