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
            static::raiseError(__METHOD__ .'(), an empty session id is not allowed!');
            return false;
        }

        if (!is_string($sessionid)) {
            static::raiseError(__METHOD__ .'(), parameter has to be a string!');
            return false;
        }

        if (!$this->setFieldValue('session_id', $sessionid)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getSessionId()
    {
        if (!$this->hasFieldValue('session_id')) {
            static::raiseError(__METHOD__ .'(), \$job_session_id has not been set yet!');
            return false;
        }

        if (($session_id = $this->getFieldValue('session_id')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $session_id;
    }

    public function hasSessionId()
    {
        if (!$this->hasFieldValue('session_id')) {
            return false;
        }

        return true;
    }

    public function setProcessingFlag($value = true)
    {
        if (!isset($value) || empty($value) || !$value) {
            if (!$this->setFieldValue('in_processing', 'N')) {
                static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
                return false;
            }
            return true;
        }

        if (!$this->setFieldValue('in_processing', 'Y')) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getProcessingFlag()
    {
        if (!$this->hasFieldValue('in_processing')) {
            return 'N';
        }

        if (($flag = $this->getFieldValue('in_processing')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $flag;
    }

    public function isProcessing()
    {
        if (($flag = $this->getProcessingFlag()) === false) {
            static::raiseError(__CLASS__ .'::getProcessingFlag() returned false!');
            return false;
        }

        if ($flag != 'Y') {
            return false;
        }

        return true;
    }

    protected function preSave()
    {
        if (!$this->hasFieldValue('in_processing')) {
            if (!$this->setFieldValue('in_processing', 'N')) {
                static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
                return false;
            }
        }

        return true;
    }

    public function setRequestGuid($guid)
    {
        global $thallium;

        if (empty($guid) || !$thallium->isValidGuidSyntax($guid)) {
            static::raiseError(__METHOD__ .'(), first parameter needs to be a valid GUID!');
            return false;
        }

        if (!$this->setFieldValue('request_guid', $guid)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getRequestGuid()
    {
        if (!$this->hasFieldValue('request_guid')) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        if (($request_guid = $this->getFieldValue('request_guid')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $request_guid;
    }

    public function getCommand()
    {
        if (!$this->hasFieldValue('command')) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        if (($command = $this->getFieldValue('command')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $command;
    }

    public function setCommand($command)
    {
        if (!isset($command) || empty($command) || !is_string($command)) {
            static::raiseError(__METHOD__ .'(), $command parameter needs to be set!');
            return false;
        }

        if (!$this->setFieldValue('command', $command)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getParameters()
    {
        if (!$this->hasParameters()) {
            static::raiseError(__CLASS__ .'::hasParameters() returned false!');
            return false;
        }

        if (($parameters = $this->getFieldValue('parameters')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (($params = base64_decode($parameters)) === false) {
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

        if (!$this->setFieldValue('parameters', base64_encode(serialize($parameters)))) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasParameters()
    {
        if (!$this->hasFieldValue('parameters')) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
