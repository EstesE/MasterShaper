<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015> <Andreas Unterkircher>
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

class JobModel extends DefaultModel
{
    protected static $model_table_name = 'jobs';
    protected static $model_column_prefix = 'job';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'command' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'command' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'parameters' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'session_id' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'request_guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'time' => array(
            FIELD_TYPE => FIELD_TIMESTAMP,
        ),
        'in_processing' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );

    public function setSessionId($sessionid)
    {
        if (empty($sessionid)) {
            static::raiseError(__METHOD__ .', an empty session id is not allowed!');
            return false;
        }

        if (!is_string($sessionid)) {
            static::raiseError(__METHOD__ .', parameter has to be a string!');
            return false;
        }

        $this->job_session_id = $sessionid;
        return true;
    }

    public function getSessionId()
    {
        if (!isset($this->job_session_id)) {
            static::raiseError(__METHOD__ .', \$job_session_id has not been set yet!');
            return false;
        }

        return $this->job_session_id;
    }

    public function hasSessionId()
    {
        if (!isset($this->job_session_id) || empty($this->job_session_id)) {
            return false;
        }

        return true;
    }

    public function setProcessingFlag($value = true)
    {
        if (!$value) {
            $this->job_in_processing = 'N';
            return true;
        }

        $this->job_in_processing = 'Y';
        return true;
    }

    public function getProcessingFlag()
    {
        if (!isset($this->job_in_processing)) {
            return 'N';
        }

        return $this->job_in_processing;
    }

    public function isProcessing()
    {
        if (!isset($this->getProcessingFlag)) {
            return false;
        }

        if ($this->job_in_processing != 'Y') {
            return false;
        }

        return true;
    }

    protected function preSave()
    {
        if (!isset($this->job_in_processing) || empty($this->job_in_processing)) {
            $this->job_in_processing = 'N';
        }

        return true;
    }

    public function setRequestGuid($guid)
    {
        global $thallium;

        if (empty($guid) || !$thallium->isValidGuidSyntax($guid)) {
            static::raiseError(__METHOD__ .', first parameter needs to be a valid GUID!');
            return false;
        }

        $this->job_request_guid = $guid;
        return true;
    }

    public function getRequestGuid()
    {
        if (!isset($this->job_request_guid)) {
            static::raiseError(__METHOD__ .', \$job_request_guid has not been set yet!');
            return false;
        }

        return $this->job_request_guid;
    }

    public function getCommand()
    {
        if (!isset($this->job_command)) {
            return false;
        }

        return $this->job_command;
    }

    public function setCommand($command)
    {
        if (!isset($command) || empty($command) || !is_string($command)) {
            static::raiseError(__METHOD__ .'(), $command parameter needs to be set!');
            return false;
        }

        $this->job_command = $command;
        return true;
    }

    public function getParameters()
    {
        if (!$this->hasParameters()) {
            static::raiseError(__CLASS__ .'::hasParameters() returned false!');
            return false;
        }

        if (($params = base64_decode($this->job_parameters)) === false) {
            static::raiseError(__METHOD__ .'(), base64_decode() failed on job_parameters!');
            return false;
        }

        if (($params = unserialize($params)) === false) {
            static::raiseError(__METHOD__ .'(), unserialize() job_parameters failed!');
            return false;
        }

        return $params;
    }

    public function setParameters($parameters)
    {
        if (!isset($parameters) || empty($parameters)) {
            static::raiseError(__METHOD__ .'(), $parameters parameter needs to be set!');
            return false;
        }

        $this->job_parameters = base64_encode(serialize($parameters));
        return true;
    }

    public function hasParameters()
    {
        if (!isset($this->job_parameters) || empty($this->job_parameters)) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
