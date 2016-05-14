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
            FIELD_LENGTH => 4096,
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

        if (!$this->setFieldValue('command', $command)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

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

        if (!$this->setFieldValue('session_id', $sessionid)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getSessionId()
    {
        if (!$this->hasFieldValue('session_id')) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        if (($session_id = $this->getFieldValue('session_id')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $session_id;
    }

    public function hasCommand()
    {
        if (!$this->hasFieldValue('command')) {
            return false;
        }

        return true;
    }

    public function getCommand()
    {
        if (!$this->hasCommand()) {
            static::raiseError(__CLASS__ .'::hasCommand() returned false!');
            return false;
        }

        if (($command = $this->getFieldValue('command')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $command;
    }

    public function setBody($body)
    {
        if (!isset($body) || empty($body)) {
            static::raiseError(__METHOD__ .'(), $body parameter needs to be set!');
            return false;
        }

        if (is_string($body)) {
            if (!$this->setFieldValue('body', base64_encode(serialize($body)))) {
                static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
                return false;
            }
            return true;
        } elseif (is_array($body)) {
            $filtered_body = array_filter($body, function ($var) {
                if (is_numeric($var) || is_string($var)) {
                    return true;
                }
                return false;
            });
            if (!$this->setFieldValue('body', base64_encode(serialize($filtered_body)))) {
                static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
                return false;
            }
            return true;
        } elseif (!is_object($body)) {
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

        if (!$this->setFieldValue('body', base64_encode(serialize($filtered_body)))) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasBody()
    {
        if (!$this->hasFieldValue('body')) {
            return false;
        }

        return true;
    }

    public function getBody()
    {
        if (!$this->hasFieldValue('body')) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        if (($body_raw = $this->getBodyRaw()) === false) {
            static::raiseError(__CLASS__ .'::getBodyRaw() returned false!');
            return false;
        }

        if (($body = base64_decode($body_raw)) === false) {
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
        if (!$this->hasFieldValue('body')) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        if (($body_raw = $this->getFieldValue('body')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $body_raw;
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

        if (!$this->setFieldValue('scope', $scope)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getScope()
    {
        if (!$this->hasFieldValue('scope')) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        if (($scope = $this->getFieldValue('scope')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $scope;
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

        if (($in_processing = $this->getFieldValue('in_processing')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $in_processing;
    }

    public function isProcessing()
    {
        if (!isset($this->getProcessingFlag)) {
            return false;
        }

        if (($in_processing = $this->getFieldValue('in_processing')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($in_processing != 'Y') {
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

        if (!$this->setFieldValue('value', $value)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getValue()
    {
        if (!$this->hasValue()) {
            return false;
        }

        if (($value = $this->getFieldValue('value')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function hasValue()
    {
        if (!$this->hasFieldValue('value')) {
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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
