<?php

/**
 * This file is part of MasterShaper.
 *
 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>
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

namespace MasterShaper\Controllers;

class JobsController extends \Thallium\Controllers\JobsController
{
    public function __construct()
    {
        try {
            $this->registerHandler(
                'save-request',
                array($this, 'handleSaveRequest')
            );
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to register handlers!', true);
            return;
        }

        parent::__construct();
    }

    protected function handleSaveRequest($job)
    {
        global $ms, $mbus;

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'() requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || ($save_request = $job->getParameters()) === false) {
            static::raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_object($save_request)) {
            static::raiseError(get_class($job) .'::getParameters() returned invalid data!');
            return false;
        }

        if (!isset($save_request->id) || empty($save_request->id) ||
            !isset($save_request->guid) || empty($save_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() save-request is incomplete!');
            return false;
        }

        if (!$ms->isValidId($save_request->id)) {
            static::raiseError(__METHOD__ .'() \$id is invalid!');
            return false;

        }

        if (!$ms->isValidGuidSyntax($save_request->guid)) {
            static::raiseError(__METHOD__ .'() \$guid is invalid!');
            return false;
        }

        if (!isset($save_request->model) ||
            empty($save_request->model) ||
            !is_string($save_request->model)) {
            static::raiseError(__METHOD__ .'(), save-request does not contain model information!');
            return false;
        }

        if (!$ms->isRegisteredModel($save_request->model)) {
            static::raiseError(__METHOD__ .'(), save-request contains an unsupported model!');
            return false;
        }

        $model = $save_request->model;
        $id = $save_request->id;
        $guid = $save_request->guid;
        unset($save_request->model);
        unset($save_request->id);
        unset($save_request->guid);

        if (($obj = $ms->loadModel($model, $id, $guid)) === false) {
            static::raiseError(get_class($ms) .'::loadModel() returned false!');
            return false;
        }

        if (!$obj->permitsRpcActions('update')) {
            static::raiseError(__METHOD__ ."(), requested model does not permit RPC 'update' action!");
            return false;
        }

        if (!$obj->update($save_request)) {
            static::raiseError(get_class($obj) .'::update() returned false!');
            return false;
        }

        if (!$obj->save()) {
            static::raiseError(get_class($obj) .'::save() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('save-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
