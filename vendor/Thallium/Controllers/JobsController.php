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

namespace Thallium\Controllers;

class JobsController extends DefaultController
{
    const EXPIRE_TIMEOUT = 300;
    protected $currentJobGuid;
    protected $registeredHandlers = array();
    protected $json_errors;

    public function __construct()
    {
        if (!$this->removeExpiredJobs()) {
            static::raiseError('removeExpiredJobs() returned false!', true);
            return false;
        }

        try {
            $this->registerHandler('delete-request', array($this, 'handleDeleteRequest'));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to register handlers!', true);
            return false;
        }

        // Define the JSON errors.
        $constants = get_defined_constants(true);
        $this->json_errors = array();
        foreach ($constants["json"] as $name => $value) {
            if (!strncmp($name, "JSON_ERROR_", 11)) {
                $this->json_errors[$value] = $name;
            }
        }

        parent::__construct();
        return true;
    }

    protected function removeExpiredJobs()
    {
        try {
            $jobs = new \Thallium\Models\JobsModel;
        } catch (\Exception $e) {
            static::raiseError('Failed to load JobsModel!');
            return false;
        }

        if (!$jobs->deleteExpiredJobs(self::EXPIRE_TIMEOUT)) {
            static::raiseError(get_class($jobs) .'::deleteExpiredJobs() returned false!');
            return false;
        }

        return true;
    }

    public function createJob($command, $parameters = null, $sessionid = null, $request_guid = null)
    {
        global $thallium;

        if (!isset($command) || empty($command) || !is_string($command)) {
            static::raiseError(__METHOD__ .'(), parameter $commmand is required!');
            return false;
        }

        if (isset($sessionid) && (empty($sessionid) || !is_string($sessionid))) {
            static::raiseError(__METHOD__ .'(), parameter $sessionid has to be a string!');
            return false;
        }

        if (isset($request_guid) &&
           (empty($request_guid) || !$thallium->isValidGuidSyntax($request_guid))
        ) {
            static::raiseError(__METHOD__ .'(), parameter $request_guid is invalid!');
            return false;
        }

        try {
            $job = new \Thallium\Models\JobModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), unable to load JobModel!');
            return false;
        }

        if (isset($sessionid) && !$job->setSessionId($sessionid)) {
            static::raiseError(get_class($job) .'::setSessionId() returned false!');
            return false;
        }

        if (isset($request_guid) && !$job->setRequestGuid($request_guid)) {
            static::raiseError(get_class($job) .'::setRequestGuid() returned false!');
            return false;
        }

        if (!$job->setCommand($command)) {
            static::raiseError(get_class($job) .'::setCommand() returned false!');
            return false;
        }

        if (isset($parameters) && !empty($parameters)) {
            if (!$job->setParameters($parameters)) {
                static::raiseError(get_class($job) .'::setParameters() returned false!');
                return false;
            }
        }

        if (!$job->save()) {
            static::raiseError(get_class($job) .'::save() returned false!');
            return false;
        }

        if (!isset($job->job_guid) ||
            empty($job->job_guid) ||
            !$thallium->isValidGuidSyntax($job->job_guid)
        ) {
            static::raiseError(get_class($job) .'::save() has not lead to a valid GUID!');
            return false;
        }

        return $job;
    }

    public function deleteJob($job_guid)
    {
        global $thallium;

        if (!isset($job_guid) || empty($job_guid) || !$thallium->isValidGuidSyntax($job_guid)) {
            static::raiseError(__METHOD__ .', first parameter has to be a valid GUID!');
            return false;
        }

        try {
            $job = new \Thallium\Models\JobModel(array(
                'guid' => $job_guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .", failed to load JobModel(null, {$job_guid})");
            return false;
        }

        if (!$job->delete()) {
            static::raiseError(get_class($job) .'::delete() returned false!');
            return false;
        }

        if ($this->hasCurrentJob() && ($cur_guid = $this->getCurrentJob())) {
            if ($cur_guid == $job_guid) {
                $this->clearCurrentJob();
            }
        }

        return true;
    }

    public function setCurrentJob($job_guid)
    {
        global $thallium;

        if (!isset($job_guid) || empty($job_guid) || !$thallium->isValidGuidSyntax($job_guid)) {
            static::raiseError(__METHOD__ .', first parameter has to be a valid GUID!');
            return false;
        }

        $this->currentJobGuid = $job_guid;
        return true;
    }

    public function getCurrentJob()
    {
        if (!$this->hasCurrentJob()) {
            return false;
        }

        return $this->currentJobGuid;
    }

    public function hasCurrentJob()
    {
        if (!isset($this->currentJobGuid) || empty($this->currentJobGuid)) {
            return false;
        }

        return true;
    }

    public function clearCurrentJob()
    {
        unset($this->currentJobGuid);
        return true;
    }

    public function runJob($job)
    {
        global $thallium, $mbus;

        if (is_string($job) && $thallium->isValidGuidSyntax($job)) {
            try {
                $job = new \Thallium\Models\JobModel(array(
                    'guid' => $job
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load JobModel!');
                return false;
            }
        }

        if (!is_object($job)) {
            static::raiseError(__METHOD__ .'(), no valid JobModel provided!');
            return false;
        }

        if (($command = $job->getCommand()) === false) {
            static::raiseError(get_class($job) .'::getCommand() returned false!');
            return false;
        }

        if (!isset($command) || empty($command) || !is_string($command)) {
            static::raiseError(get_class($job) .'::getCommand() returned invalid data!');
            return false;
        }

        if (!$this->isRegisteredHandler($command)) {
            static::raiseError(__METHOD__ ."(), there is no handler for {$command}!");
            return false;
        }

        if (($handler = $this->getHandler($command)) === false) {
            static::raiseError(__CLASS__ .'::getHandler() returned false!');
            return false;
        }

        if (!isset($handler) || empty($handler) || !is_array($handler) ||
            !isset($handler[0]) || empty($handler[0]) || !is_object($handler[0]) ||
            !isset($handler[1]) || empty($handler[1]) || !is_string($handler[1])
        ) {
            static::raiseError(__CLASS__ .'::getHandler() returned invalid data!');
            return false;
        }

        if (!is_callable($handler, true)) {
            static::raiseError(__METHOD__ .'(), handler is not callable!');
            return false;
        }

        if (!$job->hasSessionId()) {
            $state = $mbus->suppressOutboundMessaging(true);
        }

        if (!call_user_func($handler, $job)) {
            static::raiseError(get_class($handler[0]) ."::{$handler[1]}() returned false!");
            return false;
        }

        if (!$job->hasSessionId()) {
            $mbus->suppressOutboundMessaging($state);
        }

        return true;
    }

    public function runJobs()
    {
        try {
            $jobs = new \Thallium\Models\JobsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load JobsModel!');
            return false;
        }

        if (($pending = $jobs->getPendingJobs()) === false) {
            static::raiseError(get_class($jobs) .'::getPendingJobs() returned false!');
            return false;
        }

        if (!isset($pending) || !is_array($pending)) {
            static::raiseError(get_class($jobs) .'::getPendingJobs() returned invalid data!');
            return false;
        }

        if (empty($pending)) {
            return true;
        }

        foreach ($pending as $job) {
            if ($job->isProcessing()) {
                return true;
            }

            if (!$job->setProcessingFlag()) {
                static::raiseError(get_class($job) .'::setProcessingFlag() returned false!');
                return false;
            }

            if (!$job->save()) {
                static::raiseError(get_class($job) .'::save() returned false!');
                return false;
            }

            if (!$this->setCurrentJob($job->getGuid())) {
                static::raiseError(__CLASS__ .'::setCurrentJob() returned false!');
                return false;
            }

            if (!$this->runJob($job)) {
                static::raiseError(__CLASS__ .'::runJob() returned false!');
                return false;
            }

            if (!$job->delete()) {
                static::raiseError(get_class($job) .'::delete() returned false!');
                return false;
            }

            if (!$this->clearCurrentJob()) {
                static::raiseError(__CLASS__ .'::clearCurrentJob() returned false!');
                return false;
            }
        }

        return true;
    }

    public function registerHandler($job_name, $handler)
    {
        if (!isset($job_name) || empty($job_name) || !is_string($job_name)) {
            static::raiseError(__METHOD__ .'(), $job_name parameter is invalid!');
            return false;
        }

        if (!isset($handler) || empty($handler) || (!is_string($handler) && !is_array($handler))) {
            static::raiseError(__METHOD__ .'(), $handler parameter is invalid!');
            return false;
        }

        if (is_string($handler)) {
            $handler = array($this, $handler);
        } else {
            if (count($handler) != 2 ||
                !isset($handler[0]) || empty($handler[0]) || !is_object($handler[0]) ||
                !isset($handler[1]) || empty($handler[1]) || !is_string($handler[1])
            ) {
                static::raiseError(__METHOD__ .'(), $handler parameter contains invalid data!');
                return false;
            }
        }

        if ($this->isRegisteredHandler($job_name)) {
            static::raiseError(__METHOD__ ."(), a handler for {$job_name} is already registered!");
            return false;
        }

        $this->registeredHandlers[$job_name] = $handler;
    }

    public function unregisterHandler($job_name)
    {
        if (!isset($job_name) || empty($job_name) || !is_string($job_name)) {
            static::raiseError(__METHOD__ .'(), $job_name parameter is invalid!');
            return false;
        }

        if (!$this->isRegisteredHandler($job_name)) {
            return true;
        }

        unset($this->registeredHandlers[$job_name]);
        return true;
    }

    public function isRegisteredHandler($job_name)
    {
        if (!isset($job_name) || empty($job_name) || !is_string($job_name)) {
            static::raiseError(__METHOD__ .'(), $job_name parameter is invalid!');
            return false;
        }

        if (!in_array($job_name, array_keys($this->registeredHandlers))) {
            return false;
        }

        return true;
    }

    public function getHandler($job_name)
    {
        if (!isset($job_name) || empty($job_name) || !is_string($job_name)) {
            static::raiseError(__METHOD__ .'(), $job_name parameter is invalid!');
            return false;
        }

        if (!$this->isRegisteredHandler($job_name)) {
            static::raiseError(__METHOD__ .'(), no such handler!');
            return false;
        }

        return $this->registeredHandlers[$job_name];
    }

    protected function handleDeleteRequest($job)
    {
        global $thallium, $mbus;

        if (!$mbus->sendMessageToClient('delete-reply', 'Preparing', '10%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'() requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || ($delete_request = $job->getParameters()) === false) {
            static::raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_object($delete_request)) {
            static::raiseError(get_class($job) .'::getParameters() returned invalid data!');
            return false;
        }

        if (!isset($delete_request->id) || empty($delete_request->id) ||
            !isset($delete_request->guid) || empty($delete_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() delete-request is incomplete!');
            return false;
        }

        if ($delete_request->id != 'all' &&
            !$thallium->isValidId($delete_request->id)
        ) {
            static::raiseError(__METHOD__ .'() \$id is invalid!');
            return false;

        }

        if ($delete_request->guid != 'all' &&
            !$thallium->isValidGuidSyntax($delete_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('delete-reply', 'Deleting...', '20%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!isset($delete_request->model) || empty($delete_request->model)) {
            static::raiseError(__METHOD__ .'(), delete-request does not contain model information!');
            return false;
        }

        if (!$thallium->isRegisteredModel($delete_request->model)) {
            static::raiseError(__METHOD__ .'(), delete-request contains an unsupported model!');
            return false;
        }

        $model = $delete_request->model;
        $id = $delete_request->id;
        $guid = $delete_request->guid;

        if (($obj = $thallium->loadModel($model, $id, $guid)) === false) {
            static::raiseError(get_class($thallium) .'::loadModel() returned false!');
            return false;
        }

        if (!$obj->permitsRpcActions('delete')) {
            static::raiseError(__METHOD__ ."(), {$obj_name} does not permit 'delete' action!");
            return false;
        }

        if ($id == 'all' && $guid == 'all') {
            if (method_exists($obj, 'flush')) {
                $rm_method = 'flush';
            } else {
                $rm_method = 'delete';
            }
            if (!$obj->$rm_method()) {
                static::raiseError(get_class($obj) ."::${rm_method}() returned false!");
                return false;
            }
            if (!$mbus->sendMessageToClient('delete-reply', 'Done', '100%')) {
                static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }
            return true;
        }

        if (!$obj->delete()) {
            static::raiseError(get_class($obj) .'::delete() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('delete-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
