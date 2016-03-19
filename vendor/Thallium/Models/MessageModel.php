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

class MessageModel extends DefaultModel
{
    protected static $model_table_name = 'message_bus';
    protected static $model_column_prefix = 'msg';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'scope' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'submit_time' => array(
            FIELD_TYPE => FIELD_TIMESTAMP,
        ),
        'session_id' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'command' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'body' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'value' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'in_processing' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );

    public function setCommand($command)
    {
        if (empty($command)) {
            static::raiseError(__METHOD__ .'(), an empty command is not allowed!');
            return false;
        }

        if (!is_string($command)) {
            static::raiseError(__METHOD__ .'(), parameter has to be a string!');
            return false;
        }

        $this->msg_command = $command;
        return true;
    }

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

        $this->msg_session_id = $sessionid;
        return true;
    }

    public function getSessionId()
    {
        if (!isset($this->msg_session_id)) {
            static::raiseError(__METHOD__ .'(), \$msg_session_id has not been set yet!');
            return false;
        }

        return $this->msg_session_id;
    }

    public function getCommand()
    {
        if (!isset($this->msg_command)) {
            static::raiseError(__METHOD__ .'(), \$msg_command has not been set yet!');
            return false;
        }

        return $this->msg_command;
    }

    public function setBody($body)
    {
        if (!isset($body) || empty($body)) {
            static::raiseError(__METHOD__ .'(), $body parameter needs to be set!');
            return false;
        }

        if (is_string($body)) {
            $this->msg_body = base64_encode(serialize($body));
            return true;
        }

        if (is_array($body)) {
            $filtered_body = array_filter($body, function ($var) {
                if (is_numeric($var) || is_string($var)) {
                    return true;
                }
                return false;
            });
            $this->msg_body = base64_encode(serialize($filtered_body));
            return true;
        }

        if (!is_object($body)) {
            static::raiseError(__METHOD__ .'(), unknown $body type!');
            return false;
        }

        if (!is_a($body, 'stdClass')) {
            static::raiseError(__METHOD__ .'(), only stdClass objects are supported!');
            return false;
        }

        if (($vars = get_object_vars($body)) === null) {
            static::raiseError(__METHOD__ .'(), $body object has no properties assigned!');
            return false;
        }

        if (!isset($vars) || empty($vars) || !is_array($vars)) {
            static::raiseError(__METHOD__ .'(), get_object_vars() has not reveal any class properties!');
            return false;
        }

        $filtered_body = new \stdClass;
        foreach ($vars as $key => $value) {
            if ((!is_string($key) && !is_numeric($key)) ||
                (!is_string($value) && !is_numeric($value))
            ) {
                continue;
            }
            $filtered_body->$key = $value;
        }

        $this->msg_body = base64_encode(serialize($filtered_body));
        return true;
    }

    public function hasBody()
    {
        if (!isset($this->msg_body) || empty($this->msg_body)) {
            return false;
        }

        return true;
    }

    public function getBody()
    {
        if (!isset($this->msg_body)) {
            static::raiseError(__METHOD__ .'(), \$msg_body has not been set yet!');
            return false;
        }

        if (($body = base64_decode($this->msg_body)) === false) {
            static::raiseError(__METHOD__ .'(), base64_decode() failed on msg_body!');
            return false;
        }

        if (($body = unserialize($body)) === false) {
            static::raiseError(__METHOD__ .'(), unserialize() msg_body failed!');
            return false;
        }

        return $body;
    }

    public function getBodyRaw()
    {
        if (!isset($this->msg_body)) {
            static::raiseError(__METHOD__ .'(), \$msg_body has not been set yet!');
            return false;
        }

        return $this->msg_body;
    }

    public function setScope($scope)
    {
        if (!is_string($scope)) {
            static::raiseError(__METHOD__ .'(), parameter has to be a string!');
            return false;
        }

        if (!in_array($scope, array('inbound', 'outbound'))) {
            static::raiseError(__METHOD__ .'(), allowed values for scope are "inbound" and "outbound" only!');
            return false;
        }

        $this->msg_scope = $scope;
        return true;
    }

    public function getScope()
    {
        if (!isset($this->msg_scope)) {
            static::raiseError(__METHOD__ .'(), \$msg_scope has not been set yet!');
            return false;
        }

        return $this->msg_scope;
    }

    public function isClientMessage()
    {
        if (!($scope = $this->getScope())) {
            static::raiseError(__CLASS__ .'::getScope() returned false!');
            return false;
        }

        if ($scope != 'inbound') {
            return false;
        }

        return true;
    }

    public function isServerMessage()
    {
        if (!($scope = $this->getScope())) {
            static::raiseError(__CLASS__ .'::getScope() returned false!');
            return false;
        }

        if ($scope != 'outbound') {
            return false;
        }

        return true;
    }

    public function setProcessingFlag($value = true)
    {
        if (!$value) {
            $this->msg_in_processing = 'N';
            return true;
        }

        $this->msg_in_processing = 'Y';
        return true;
    }

    public function getProcessingFlag()
    {
        if (!isset($this->msg_in_processing)) {
            return 'N';
        }

        return $this->msg_in_processing;
    }

    public function isProcessing()
    {
        if (!isset($this->getProcessingFlag)) {
            return false;
        }

        if ($this->msg_in_processing != 'Y') {
            return false;
        }

        return true;
    }

    public function setValue($value)
    {
        if (!isset($value) || empty($value) || !is_string($value)) {
            static::raiseError(__METHOD__ .'(), first parameter \$value has to be a string!');
            return false;
        }

        $this->msg_value = $value;
        return true;
    }

    public function getValue()
    {
        if (!$this->hasValue()) {
            return false;
        }

        return $this->msg_value;
    }

    public function hasValue()
    {
        if (!isset($this->msg_value) || empty($this->msg_value)) {
            return false;
        }

        return true;
    }

    protected function preSave()
    {
        if (!isset($this->msg_in_processing) || empty($this->msg_in_processing)) {
            $this->msg_in_processing = 'N';
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
