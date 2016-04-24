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

namespace Thallium\Controllers;

class MessageBusController extends DefaultController
{
    const EXPIRE_TIMEOUT = 300;
    protected $suppressOutboundMessaging = false;
    protected $json_errors = array();

    public function __construct()
    {
        global $session;

        if (!$session) {
            static::raiseError(__METHOD__ ." requires SessionController to be initialized!", true);
            return false;
        }

        if (!$this->removeExpiredMessages()) {
            static::raiseError('removeExpiredMessages() returned false!', true);
            return false;
        }

        // Define the JSON errors.
        $constants = get_defined_constants(true);
        foreach ($constants["json"] as $name => $value) {
            if (!strncmp($name, "JSON_ERROR_", 11)) {
                $this->json_errors[$value] = $name;
            }
        }

        return true;
    }

    public function submit($messages_raw)
    {
        global $session;

        if (!($sessionid = $session->getSessionId())) {
            static::raiseError(get_class($session) .'::getSessionId() returned false!');
            return false;
        }

        if (empty($messages_raw)) {
            static::raiseError(__METHOD__ .', first parameter can not be empty!');
            return false;
        }

        if (!is_string($messages_raw)) {
            static::raiseError(__METHOD__ .', first parameter has to be a string!');
            return false;
        }

        if (($json = json_decode($messages_raw, false, 2)) === null) {
            static::raiseError(__METHOD__ .'(), json_decode() returned false! '. $this->json_errors[json_last_error()]);
            return false;
        }

        if (empty($json)) {
            return true;
        }

        if (!isset($json->count) || empty($json->count) ||
            !isset($json->size) || empty($json->size) ||
            !isset($json->hash) || empty($json->hash) ||
            !isset($json->json) || empty($json->json)
        ) {
            static::raiseError(__METHOD__ .', submitted message object is incomplete!');
            return false;
        }

        if (strlen($json->json) != $json->size) {
            static::raiseError(__METHOD__ .', verification failed - size differs!');
            return false;
        }

        if (sha1($json->json) != $json->hash) {
            static::raiseError(__METHOD__ .', verification failed - hash differs!');
            return false;
        }

        if (($messages = json_decode($json->json, false, 10)) === null) {
            static::raiseError(__METHOD__ .'(), json_decode() returned false! '. $this->json_errors[json_last_error()]);
            return false;
        }

        foreach ($messages as $message) {
            if (!is_object($message)) {
                static::raiseError(__METHOD__ .', $message is not an object!');
                return false;
            }

            if (!isset($message->command) || empty($message->command)) {
                static::raiseError(__METHOD__ .', $message does not contain a command!');
                return false;
            }

            try {
                $mbmsg = new \Thallium\Models\MessageModel;
            } catch (\Exception $e) {
                static::raiseError('Failed to load MessageModel!');
                return false;
            }

            if (!$mbmsg->setCommand($message->command)) {
                static::raiseError(get_class($mbmsg) .'::setCommand() returned false!');
                return false;
            }

            if (!$mbmsg->setSessionId($sessionid)) {
                static::raiseError(get_class($mbmsg) .'::setSessionId() returned false!');
                return false;
            }

            $mbmsg->setProcessingFlag(false);

            if (isset($message->message) && !empty($message->message)) {
                if (!$mbmsg->setBody($message->message)) {
                    static::raiseError(get_class($mbmsg) .'::setBody() returned false!');
                    return false;
                }
            }

            if (!$mbmsg->setScope('inbound')) {
                static::raiseError(get_class($mbmsg) .'::setScope() returned false!');
                return false;
            }

            if (!$mbmsg->save()) {
                static::raiseError(get_class($mbmsg) .'::save() returned false!');
                return false;
            }
        }

        return true;
    }

    public function poll()
    {
        global $session;

        $messages = array();

        try {
            $msgs = new \Thallium\Models\MessageBusModel;
        } catch (\Exception $e) {
            static::raiseError('Failed to load MessageBusModel!');
            return false;
        }

        if (!($sessionid = $session->getSessionId())) {
            static::raiseError(get_class($session) .'::getSessionId() returned false!');
            return false;
        }

        if (($messages = $msgs->getMessagesForSession($sessionid)) === false) {
            static::raiseError(get_class($msgs) .'::getMessagesForSession() returned false!');
            return false;
        }

        $raw_messages = array();
        foreach ($messages as $message) {
            $raw_messages[] = array(
                'id' => $message->getId(),
                'guid' => $message->getGuid(),
                'command' => $message->getCommand(),
                'body' => $message->getBody(),
                'value' => $message->getValue()
            );

            if (!$message->delete()) {
                static::raiseError(get_class($message) .'::delete() returned false!');
                return false;
            }
        }

        if (!($json = json_encode($raw_messages))) {
            static::raiseError('json_encode() returned false!');
            return false;
        }

        $len = count($raw_messages);
        $size = strlen($json);
        $hash = sha1($json);

        $reply_raw = array(
            'count' => $len,
            'size' => $size,
            'hash' => $hash,
            'json' => $json
        );

        if (!($reply = json_encode($reply_raw))) {
            static::raiseError('json_encode() returned false!');
            return false;
        }

        return $reply;
    }

    public function getRequestMessages()
    {
        try {
            $msgs = new \Thallium\Models\MessageBusModel;
        } catch (\Exception $e) {
            static::raiseError('Failed to load MessageBusModel!');
            return false;
        }

        if (($messages = $msgs->getServerRequests()) === false) {
            static::raiseError(get_class($msgs) .'::getServerRequests() returned false!');
            return false;
        }

        if (!is_array($messages)) {
            static::raiseError(get_class($msgs) .'::getServerRequests() has not returned an arary!');
            return false;
        }

        return $messages;
    }

    protected function removeExpiredMessages()
    {
        try {
            $msgs = new \Thallium\Models\MessageBusModel;
        } catch (\Exception $e) {
            static::raiseError('Failed to load MessageBusModel!');
            return false;
        }

        if (!$msgs->deleteExpiredMessages(self::EXPIRE_TIMEOUT)) {
            static::raiseError(get_class($msgs) .'::deleteExpiredMessages() returned false!');
            return false;
        }

        return true;
    }

    public function sendMessageToClient($command, $body, $value, $sessionid = null)
    {
        global $jobs;

        if ($this->isSuppressOutboundMessaging()) {
            return true;
        }

        if (!isset($command) || empty($command) || !is_string($command)) {
            static::raiseError(__METHOD__ .', parameter $command is mandatory and has to be a string!');
            return false;
        }
        if (!isset($body) || empty($body) || !is_string($body)) {
            static::raiseError(__METHOD__ .', parameter $body is mandatory and has to be a string!');
            return false;
        }

        if (isset($value) && !empty($value) && !is_string($value)) {
            static::raiseError(__METHOD__ .', parameter $value has to be a string!');
            return false;
        }

        if (empty($sessionid) && !($sessionid = $this->getSessionIdFromJob())) {
            static::raiseError(__METHOD__ .', no session id returnd by getSessionIdFromJob()!');
            return false;
        }

        if (!isset($sessionid) || empty($sessionid) || !is_string($sessionid)) {
            static::raiseError(__METHOD__ .', the specified $sessionid is invalid!');
            return false;
        }

        try {
            $msg = new \Thallium\Models\MessageModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .', failed to load MessageModel!');
            return false;
        }

        if (!$msg->setCommand($command)) {
            static::raiseError(get_class($msg) .'::setCommand() returned false!');
            return false;
        }

        if (!$msg->setBody($body)) {
            static::raiseError(get_class($msg) .'::setBody() returned false!');
            return false;
        }

        if (!$msg->setValue($value)) {
            static::raiseError(get_class($msg) .'::setValue() returned false!');
            return false;
        }

        if (!$msg->setSessionId($sessionid)) {
            static::raiseError(get_class($msg) .'::setSessionId() returned false!');
            return false;
        }

        if (!$msg->setScope('outbound')) {
            static::raiseError(get_class($msg) .'::setScope() returned false!');
            return false;
        }

        if (!$msg->save()) {
            static::raiseError(get_class($msg) .'::save() returned false!');
            return false;
        }

        return true;
    }

    protected function getSessionIdFromJob($job_guid = null)
    {
        global $thallium, $jobs;

        if (!isset($job_guid) || empty($job_guid)) {
            if (($job_guid = $jobs->getCurrentJob()) === false) {
                static::raiseError(get_class($jobs) .'::getCurrentJob() returned false!');
                return false;
            }
            if (!isset($job_guid) || empty($job_guid)) {
                static::raiseError(__METHOD__ .'(), no job found to work on!');
                return false;
            }
        }

        if (!$thallium->isValidGuidSyntax($job_guid)) {
            static::raiseError(__METHOD__ .', $job_guid is not a valid GUID!');
            return false;
        }

        try {
            $job = new \Thallium\Models\JobModel(array(
                'guid' => $job_guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .', failed to load JobModel(null, {$job})!');
            return false;
        }

        if (!($sessionid = $job->getSessionId())) {
            static::raiseError(get_class($job) .'::getSessionId() returned false!');
            return false;
        }

        return $sessionid;
    }

    public function isSuppressOutboundMessaging()
    {
        if (empty($this->suppressOutboundMessaging)) {
            return false;
        }

        return true;
    }

    public function suppressOutboundMessaging($state)
    {
        if (!is_bool($state)) {
            static::raiseError(__METHOD__ .', parameter need to be boolean!');
            return false;
        }

        $state_before = $this->suppressOutboundMessaging;
        $this->suppressOutboundMessaging = $state;
        return $state_before;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
