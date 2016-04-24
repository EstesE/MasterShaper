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

class MainController extends DefaultController
{
    const FRAMEWORK_VERSION = "1.1";

    protected $verbosity_level = LOG_WARNING;
    protected $override_namespace_prefix;
    protected $registeredModels = array(
        'auditentry' => 'AuditEntryModel',
        'auditlog' => 'AuditLogModel',
        'jobmodel' => 'JobModel',
        'jobsmodel' => 'JobsModel',
        'messagebusmodel' => 'MessageBusModel',
        'messagemodel' => 'MessageModel',
    );
    protected $registeredHandlers = array();
    protected $backgroundJobsRunning;

    public function __construct($mode = null)
    {
        $GLOBALS['thallium'] =& $this;

        $this->loadController("Config", "config");
        global $config;

        if ($config->inMaintenanceMode()) {
            print "This application is currently in maintenance mode. Please try again later!";
            exit(0);
        }

        $this->loadController("Requirements", "requirements");

        global $requirements;

        if (!$requirements->check()) {
            static::raiseError("Error - not all requirements are met. Please check!", true);
        }

        // no longer needed
        unset($requirements);

        $this->loadController("Audit", "audit");
        $this->loadController("Database", "db");

        if (!$this->isCmdline()) {
            $this->loadController("HttpRouter", "router");
            global $router;
            if (($GLOBALS['query'] = $router->select()) === false) {
                static::raiseError(__METHOD__ .'(), HttpRouterController::select() returned false!');
                return false;
            }
            global $query;
        }

        if (isset($query) && isset($query->view) && $query->view == "install") {
            $mode = "install";
        }

        if ($mode != "install" && $this->checkUpgrade()) {
            return false;
        }

        if (isset($mode) and $mode == "queue_only") {
            $this->loadController("Import", "import");
            global $import;

            if (!$import->handleQueue()) {
                static::raiseError("ImportController::handleQueue returned false!");
                return false;
            }

            unset($import);
        } elseif (isset($mode) and $mode == "install") {
            $this->loadController("Installer", "installer");
            global $installer;

            if (!$installer->setup()) {
                exit(1);
            }

            unset($installer);
            exit(0);
        }

        $this->loadController("Session", "session");
        $this->loadController("Jobs", "jobs");
        $this->loadController("MessageBus", "mbus");

        if (!$this->processRequestMessages()) {
            static::raiseError(__CLASS__ .'::processRequestMessages() returned false!', true);
            return false;
        }

        try {
            $this->registerHandler('rpc', array($this, 'rpcHandler'));
            $this->registerHandler('view', array($this, 'viewHandler'));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to register handlers!', true);
            return false;
        }

        return true;
    }

    public function startup()
    {
        if (!ob_start()) {
            static::raiseError(__METHOD__ .'(), internal error, ob_start() returned false!', true);
            return false;
        }

        if (!$this->callHandlers()) {
            static::raiseError(__CLASS__ .'::callHandlers() returned false!');
            return false;
        }

        $size = ob_get_length();

        if ($size !== false) {
            header("Content-Length: {$size}");
            header('Connection: close');

            if (($reval = ob_end_flush()) === false) {
                error_log(__METHOD__ .'(), ob_end_flush() returned false!');
            }
            ob_flush();
            flush();
            session_write_close();
        }

        register_shutdown_function(array($this, 'flushOutputBufferToLog'));

        if (!ob_start()) {
            static::raiseError(__METHOD__ .'(), internal error, ob_start() returned false!', true);
            return false;
        }

        if (!$this->runBackgroundJobs()) {
            static::raiseError(__CLASS__ .'::runBackgroundJobs() returned false!');
            return false;
        }

        return true;
    }

    public function runBackgroundJobs()
    {
        global $jobs;

        ignore_user_abort(true);
        set_time_limit(30);

        $this->backgroundJobsRunning = true;

        if (!$jobs->runJobs()) {
            static::raiseError(get_class($jobs) .'::runJobs() returned false!');
            return false;
        }

        return true;
    }

    public function setVerbosity($level)
    {
        /*if (!in_array($level, array(0 => LOG_INFO, 1 => LOG_WARNING, 2 => LOG_DEBUG))) {
            static::raiseError("Unknown verbosity level ". $level);
        }

        $this->verbosity_level = $level;*/

    } // setVerbosity()

    protected function rpcHandler()
    {
        $this->loadController("Rpc", "rpc");
        global $rpc;

        if (!$rpc->perform()) {
            static::raiseError(get_class($rpc) .'::perform() returned false!');
            return false;
        }

        return true;
    }

    protected function uploadHandler()
    {
        $this->loadController("Upload", "upload");
        global $upload;

        if (!$upload->perform()) {
            static::raiseError("UploadController::perform() returned false!");
            return false;
        }

        unset($upload);
        return true;
    }

    public function isValidId($id)
    {
        if (!isset($id) || is_null($id)) {
            return false;
        }

        if (gettype($id) === 'integer' && !is_int($id)) {
            return false;
        }

        if (gettype($id) !== 'string') {
            return false;
        }

        if (!intval($id)) {
            return false;
        }

        return true;
    }

    public function isValidModel($model_name)
    {
        if (!isset($model_name) ||
            empty($model_name) ||
            !is_string($model_name)
        ) {
            static::raiseError(__METHOD__ .'(), $model_name parameter is invalid!');
            return false;
        }

        if (!preg_match('/model$/i', $model_name)) {
            $nick = $model_name;
            $model = null;
        } else {
            $nick = null;
            $model = $model_name;
        }

        if ($this->isRegisteredModel($nick, $model)) {
            return true;
        }

        return false;
    }

    public function isValidGuidSyntax($guid)
    {
        if (strlen($guid) == 64) {
            return true;
        }

        return false;
    }

    public function parseId($id)
    {
        if (!isset($id) || empty($id)) {
            return false;
        }

        $parts = array();

        if (preg_match('/(\w+)-([0-9]+)-([a-z0-9]+)/', $id, $parts) === false) {
            return false;
        }

        if (!isset($parts) || empty($parts) || count($parts) != 4) {
            return false;
        }

        $id_obj = new \stdClass();
        $id_obj->original_id = $parts[0];
        $id_obj->model = $parts[1];
        $id_obj->id = $parts[2];
        $id_obj->guid = $parts[3];

        return $id_obj;
    }

    public function createGuid()
    {
        if (!function_exists("openssl_random_pseudo_bytes")) {
            $guid = uniqid(rand(0, 32766), true);
            return $guid;
        }

        if (($guid = openssl_random_pseudo_bytes("32")) === false) {
            static::raiseError("openssl_random_pseudo_bytes() returned false!");
            return false;
        }

        $guid = bin2hex($guid);
        return $guid;
    }

    public function loadModel($model_name, $id = null, $guid = null)
    {
        if (!($prefix = $this->getNamespacePrefix())) {
            static::raiseError(__METHOD__ .'(), failed to fetch namespace prefix!');
            return false;
        }

        if (!($known_models =  $this->getRegisteredModels())) {
            static::raiseError(__METHOD__ .'(), getRegisteredModels returned false!');
            return false;
        }

        $nick = null;
        $name = null;

        if (in_array(strtolower($model_name), array_keys($known_models))) {
            $model = $known_models[$model_name];
        } elseif (in_array($model_name, $known_models)) {
            $model = $model_name;
        }

        $model = $prefix .'\\Models\\'. $model;

        $load_by = array();
        if (isset($id) && !empty($id)) {
            $load_by['idx'] = $id;
        }
        if (isset($guid) && !empty($guid)) {
            $load_by['guid'] = $guid;
        }

        try {
            $obj = new $model($load_by);
        } catch (\Exception $e) {
            static::raiseError("Failed to load model {$object_name}! ". $e->getMessage());
            return false;
        }

        if (isset($obj)) {
            return $obj;
        }

        return false;
    }

    public function checkUpgrade()
    {
        global $db, $config;

        if (!($base_path = $config->getWebPath())) {
            static::raiseError("ConfigController::getWebPath() returned false!");
            return false;
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        if (!$db->checkTableExists("TABLEPREFIXmeta")) {
            static::raiseError(
                "You are missing meta table in database! "
                ."You may run <a href=\"{$base_path}/install\">"
                ."Installer</a> to fix this.",
                true
            );
            return true;
        }

        try {
            $framework_db_schema_version = $db->getFrameworkDatabaseSchemaVersion();
            $framework_sw_schema_version = $db->getFrameworkSoftwareSchemaVersion();
            $application_db_schema_version = $db->getApplicationDatabaseSchemaVersion();
            $application_sw_schema_version = $db->getApplicationSoftwareSchemaVersion();
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to read current schema state!');
            return false;
        }

        if ($application_db_schema_version < $application_sw_schema_version ||
            $framework_db_schema_version < $framework_sw_schema_version
        ) {
            static::raiseError(
                "A database schema upgrade is pending.&nbsp;"
                ."You have to run <a href=\"{$base_path}/install\">Installer</a> "
                ."again to upgrade.",
                true
            );
            return true;
        }

        return false;
    }

    public function loadController($controller, $global_name)
    {
        if (empty($controller)) {
            static::raiseError(__METHOD__ .'(), $controller parameter is invalid!', true);
            return false;
        }

        if (isset($GLOBALS[$global_name]) && !empty($GLOBALS[$global_name])) {
            return true;
        }

        if (!($prefix = $this->getNamespacePrefix())) {
            static::raiseError(__METHOD__ .'(), failed to fetch namespace prefix!');
            return false;
        }

        $controller = '\\'. $prefix .'\\Controllers\\'.$controller.'Controller';

        if (!class_exists($controller, true)) {
            static::raiseError("{$controller} class is not available!", true);
            return false;
        }

        try {
            $GLOBALS[$global_name] =& new $controller;
        } catch (Exception $e) {
            static::raiseError("Failed to load {$controller_name}! ". $e->getMessage(), true);
            return false;
        }

        return true;
    }

    public function getProcessUserId()
    {
        if ($uid = posix_getuid()) {
            return $uid;
        }

        return false;
    }

    public function getProcessGroupId()
    {
        if ($gid = posix_getgid()) {
            return $gid;
        }

        return false;
    }

    public function getProcessUserName()
    {
        if (!$uid = $this->getProcessUserId()) {
            return false;
        }

        if ($user = posix_getpwuid($uid)) {
            return $user['name'];
        }

        return false;

    }

    public function getProcessGroupName()
    {
        if (!$uid = $this->getProcessGroupId()) {
            return false;
        }

        if ($group = posix_getgrgid($uid)) {
            return $group['name'];
        }

        return false;
    }

    public function processRequestMessages()
    {
        global $mbus;

        if (!($messages = $mbus->getRequestMessages()) || empty($messages)) {
            return true;
        }

        if (!is_array($messages)) {
            static::raiseError(get_class($mbus) .'::getRequestMessages() has not returned an array!');
            return false;
        }

        foreach ($messages as $message) {
            $message->setProcessingFlag();

            if (!$message->save()) {
                static::raiseError(get_class($message) .'::save() returned false!');
                return false;
            }

            if (!$this->handleMessage($message)) {
                static::raiseError('handleMessage() returned false!');
                return false;
            }

            if (!$message->delete()) {
                static::raiseError(get_class($message) .'::delete() returned false!');
                return false;
            }
        }

        return true;
    }

    protected function handleMessage(&$message)
    {
        global $jobs;

        if (get_class($message) != 'Thallium\\Models\\MessageModel') {
            static::raiseError(__METHOD__ .' requires a MessageModel reference as parameter!');
            return false;
        }

        if (!$message->isClientMessage()) {
            static::raiseError(__METHOD__ .' can only handle client requests!');
            return false;
        }

        if (($command = $message->getCommand()) === false) {
            static::raiseError(get_class($message) .'::getCommand() returned false!');
            return false;
        }

        if (!is_string($command)) {
            static::raiseError(get_class($message) .'::getCommand() has not returned a string!');
            return false;
        }

        if ($message->hasBody()) {
            if (($parameters = $message->getBody()) === false) {
                static::raiseError(get_class($message) .'::getBody() returned false!');
                return false;
            }
        } else {
            $parameters = null;
        }

        if (($sessionid = $message->getSessionId()) === false) {
            static::raiseError(get_class($message) .'::getSessionId() returned false!');
            return false;
        }

        if (($msg_guid = $message->getGuid()) === false || !$this->isValidGuidSyntax($msg_guid)) {
            static::raiseError(get_class($message) .'::getGuid() has not returned a valid GUID!');
            return false;
        }

        if ($jobs->createJob($command, $parameters, $sessionid, $msg_guid) === false) {
            static::raiseError(get_class($jobs) .'::createJob() returned false!');
            return false;
        }

        return true;
    }

    final public function getNamespacePrefix()
    {
        if (isset($this->override_namespace_prefix) &&
            !empty($this->override_namespace_prefix)
        ) {
            return $this->override_namespace_prefix;
        }

        $namespace = __NAMESPACE__;

        if (!strstr($namespace, '\\')) {
            return $namespace;
        }

        $namespace_parts = explode('\\', $namespace);

        if (!isset($namespace_parts) ||
            empty($namespace_parts) ||
            !is_array($namespace_parts) ||
            !isset($namespace_parts[0]) ||
            empty($namespace_parts[0]) ||
            !is_string($namespace_parts[0])
        ) {
            static::raiseError('Failed to extract prefix from NAMESPACE constant!');
            return false;
        }

        return $namespace_parts[0];
    }

    final public function setNamespacePrefix($prefix)
    {
        if (!isset($prefix) || empty($prefix) || !is_string($prefix)) {
            static::raiseError(__METHOD__ .'(), $prefix parameter is invalid!');
            return false;
        }

        $this->override_namespace_prefix = $prefix;
        return true;
    }

    final public function getRegisteredModels()
    {
        if (!isset($this->registeredModels) ||
            empty($this->registeredModels) ||
            !is_array($this->registeredModels)
        ) {
            static::raiseError(__METHOD__ .'(), registeredModels property is invalid!');
            return false;
        }

        return $this->registeredModels;
    }

    final public function registerModel($nick, $model)
    {
        if (!isset($this->registeredModels) ||
            empty($this->registeredModels) ||
            !is_array($this->registeredModels)
        ) {
            static::raiseError(__METHOD__ .'(), registeredModels property is invalid!', true);
            return false;
        }

        if (!isset($nick) || empty($nick) || !is_string($nick)) {
            static::raiseError(__METHOD__ .'(), $nick parameter is invalid!', true);
            return false;
        }

        if (!isset($model) || empty($model) || !is_string($model)) {
            static::raiseError(__METHOD__ .'(), $model parameter is invalid!', true);
            return false;
        }

        if ($this->isRegisteredModel($nick, $model)) {
            return true;
        }

        if (!($prefix = $this->getNamespacePrefix())) {
            static::raiseError(__METHOD__ .'(), failed to fetch namespace prefix!', true);
            return false;
        }

        $full_model_name = "\\{$prefix}\\Models\\{$model}";

        if (!class_exists($full_model_name, true)) {
            static::raiseError(__METHOD__ ."(), model {$model} class does not exist!", true);
            return false;
        }

        $this->registeredModels[$nick] = $model;
        return true;
    }

    final public function isRegisteredModel($nick = null, $model = null)
    {
        if ((!isset($nick) || empty($nick) || !is_string($nick)) &&
            (!isset($model) || empty($model) || !is_string($model))
        ) {
            static::raiseError(__METHOD__ .'(), can not look for nothing!');
            return false;
        }

        if (($known_models = $this->getRegisteredModels()) === false) {
            static::raiseError(__METHOD__ .'(), getRegisteredModels() returned false!');
            return false;
        }

        $result = false;

        if (isset($nick) && !empty($nick)) {
            if (in_array($nick, array_keys($known_models))) {
                $result = true;
            }
        }

        // not looking for $model? then we are done.
        if (!isset($model) || empty($model)) {
            return $result;
        }

        // looking for nick was ok, but does it also match $model?
        if ($result) {
            if ($known_models[$nick] == $model) {
                return true;
            } else {
                return false;
            }
        }

        if (!in_array($model, $known_models)) {
            return false;
        }

        return true;
    }

    public function getModelByNick($nick)
    {
        if (!isset($nick) || empty($nick) || !is_string($nick)) {
            static::raiseError(__METHOD__ .'(), $nick parameter is invalid!');
            return false;
        }

        if (($known_models = $this->getRegisteredModels()) === false) {
            static::raiseError(__METHOD__ .'(), getRegisteredModels() returned false!');
            return false;
        }

        if (!isset($known_models[$nick])) {
            return false;
        }

        return $known_models[$nick];
    }

    public function isBelowDirectory($dir, $topmost = null)
    {
        if (empty($dir)) {
            static::raiseError(__METHOD__ .'(), $dir parameter is invalid!');
            return false;
        }

        if (empty($topmost)) {
            $topmost = APP_BASE;
        }

        $dir = strtolower(realpath($dir));
        $dir_top = strtolower(realpath($topmost));

        $dir_top_reg = preg_quote($dir_top, '/');

        // check if $dir is within $dir_top
        if (!preg_match('/^'. preg_quote($dir_top, '/') .'/', $dir)) {
            return false;
        }

        if ($dir == $dir_top) {
            return false;
        }

        $cnt_dir = count(explode('/', $dir));
        $cnt_dir_top = count(explode('/', $dir_top));

        if ($cnt_dir > $cnt_dir_top) {
            return true;
        }

        return false;
    }

    protected function registerHandler($handler_name, $handler)
    {
        if (!isset($handler_name) || empty($handler_name) || !is_string($handler_name)) {
            static::raiseError(__METHOD__ .'(), $handler_name parameter is invalid!');
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

        if ($this->isRegisteredHandler($handler_name)) {
            static::raiseError(__METHOD__ ."(), a handler for {$handler_name} is already registered!");
            return false;
        }

        $this->registeredHandlers[$handler_name] = $handler;
    }

    protected function unregisterHandler($handler_name)
    {
        if (!isset($handler_name) || empty($handler_name) || !is_string($handler_name)) {
            static::raiseError(__METHOD__ .'(), $handler_name parameter is invalid!');
            return false;
        }

        if (!$this->isRegisteredHandler($handler_name)) {
            return true;
        }

        unset($this->registeredHandlers[$handler_name]);
        return true;
    }

    protected function isRegisteredHandler($handler_name)
    {
        if (!isset($handler_name) || empty($handler_name) || !is_string($handler_name)) {
            static::raiseError(__METHOD__ .'(), $handler_name parameter is invalid!');
            return false;
        }

        if (!in_array($handler_name, array_keys($this->registeredHandlers))) {
            return false;
        }

        return true;
    }

    protected function getHandler($handler_name)
    {
        if (!isset($handler_name) || empty($handler_name) || !is_string($handler_name)) {
            static::raiseError(__METHOD__ .'(), $handler_name parameter is invalid!');
            return false;
        }

        if (!$this->isRegisteredHandler($handler_name)) {
            static::raiseError(__METHOD__ .'(), no such handler!');
            return false;
        }

        return $this->registeredHandlers[$handler_name];
    }

    protected function viewHandler()
    {
        $this->loadController("Views", "views");
        global $views, $query;

        if (!isset($query->view) || empty($query->view)) {
            static::raiseError(__METHOD__ .'(), no view has been requested!');
            return false;
        }

        if (($page = $views->load($query->view)) === false) {
            static::raiseError("ViewController:load() returned false!");
            return false;
        }

        if ($page === true) {
            return true;
        }

        // display output and close the connection to the client.
        if (!empty($page)) {
            print $page;
        }

        return true;
    }

    protected function callHandlers()
    {
        global $router;

        if ($router->isRpcCall()) {
            if (!$this->callHandler('rpc')) {
                static::raiseError(__CLASS__ .'::callHandler() returned false!');
                return false;
            }
            return true;
        } elseif ($router->isUploadCall()) {
            if (!$this->callHandler('upload')) {
                static::raiseError(__CLASS__ .'::callHandler() returned false!');
                return false;
            }
            return true;
        }

        if (!$this->callHandler('view')) {
            static::raiseError(__CLASS__ .'::callHandler() returned false!');
            return false;
        }

        return true;
    }

    protected function callHandler($handler_name)
    {
        if (!isset($handler_name) || empty($handler_name) || !is_string($handler_name)) {
            static::raiseError(__METHOD__ .'(), $handler_name parameter is invalid!');
            return false;
        }

        if (($handler = $this->getHandler($handler_name)) === false) {
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

        if (!call_user_func($handler)) {
            static::raiseError(get_class($handler[0]) ."::{$handler[1]}() returned false!");
            return false;
        }

        return true;
    }

    public function flushOutputBufferToLog()
    {
        if (($size = ob_get_length()) === false || empty($size)) {
            return true;
        }

        if (($buffer = ob_get_contents()) === false || empty($buffer)) {
            return true;
        }

        if (($reval = ob_end_clean()) === false) {
            error_log(__METHOD__ .'(), ob_end_clean() returned false!');
        }

        ob_flush();
        flush();

        error_log(__METHOD__ .'(), background jobs have issued output! output follows:');
        error_log(__METHOD__ .'(), '. $buffer);
        return true;
    }

    public function getFullModelName($model)
    {
        if (!$this->isRegisteredModel($model, $model)) {
            static::raiseError(__CLASS__ .'::isRegisteredModel() returned false!');
            return false;
        }

        if (!($prefix = $this->getNamespacePrefix())) {
            static::raiseError(__METHOD__ .'(), failed to fetch namespace prefix!');
            return false;
        }

        $full_model_name = "\\{$prefix}\\Models\\{$model}";
        return $full_model_name;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
