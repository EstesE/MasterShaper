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

use \Smarty;

class TemplatesController extends DefaultController
{
    protected $smarty;

    protected $config_template_dir;
    protected $config_compile_dir;
    protected $config_config_dir;
    protected $config_cache_dir;

    public function __construct()
    {
        global $config, $thallium;

        try {
            $this->smarty = new Smarty;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load Smarty!', true, $e);
            return;
        }

        if (($prefix = $thallium->getNamespacePrefix()) === false) {
            static::raiseError(get_class($thallium) .'::getNameSpacePrefix() returned false!', true);
            return;
        }

        // disable template caching during development
        $this->smarty->setCaching(Smarty::CACHING_OFF);
        $this->smarty->force_compile = true;
        $this->smarty->caching = false;

        $this->config_template_dir = APP_BASE .'/vendor/'. $prefix .'/Views/templates';
        $this->config_compile_dir  = self::CACHE_DIRECTORY .'/templates_c';
        $this->config_config_dir   = self::CACHE_DIRECTORY .'/smarty_config';
        $this->config_cache_dir    = self::CACHE_DIRECTORY .'/smarty_cache';

        if (!file_exists($this->config_compile_dir) && !is_writeable(self::CACHE_DIRECTORY)) {
            static::raiseError(
                sprintf(
                    __METHOD__ .'(), cche directory ". CACHE_DIRECTORY ." is not writeable'
                    .'for user (%s).<br />\n'
                    .'Please check that permissions are set correctly to this directory.<br />\n',
                    $this->getuid()
                ),
                true
            );
            return;
        }

        if (!file_exists($this->config_compile_dir) && !mkdir($this->config_compile_dir, 0700)) {
            static::raiseError(__METHOD__ .'(), failed to create directory '. $this->config_compile_dir, true);
            return;
        }

        if (!is_writeable($this->config_compile_dir)) {
            static::raiseError(
                sprintf(
                    __METHOD__ .'(), error - Smarty compile directory {$this->config_compile_dir} is not '
                    .'writeable for the current user (%s).<br />'
                    ."Please check that permissions are set correctly to this directory.<br />",
                    $this->getuid()
                ),
                true
            );
            return;
        }

        $this->smarty->setTemplateDir($this->config_template_dir);
        $this->smarty->setCompileDir($this->config_compile_dir);
        $this->smarty->setConfigDir($this->config_config_dir);
        $this->smarty->setCacheDir($this->config_cache_dir);

        if (($app_web_path = $config->getWebPath()) === false) {
            static::raiseError(__METHOD__.'(), web path is missing!', true);
            return;
        }

        if ($app_web_path == '/') {
            $app_web_path = '';
        }

        if (($page_title = $config->getPageTitle()) === false) {
            $page_title = 'Thallium v'. MainController::FRAMEWORK_VERSION;
        }

        $this->smarty->assign('config', $config);
        $this->smarty->assign('app_web_path', $app_web_path);
        $this->smarty->assign('page_title', $page_title);
        $this->registerPlugin("function", "get_url", array(&$this, "getUrl"), false);
        $this->registerPlugin(
            "function",
            "get_humanreadable_filesize",
            array(&$this, "getHumanReadableFilesize"),
            false
        );
        $this->registerPlugin("function", "raise_error", array(&$this, "smartyRaiseError"), false);
        return;
    }

    public function getuid()
    {
        if ($uid = posix_getuid()) {
            if ($user = posix_getpwuid($uid)) {
                return $user['name'];
            }
        }

        return 'n/a';

    }

    public function fetch(
        $template = null,
        $cache_id = null,
        $compile_id = null,
        $parent = null,
        $display = false,
        $merge_tpl_vars = true,
        $no_output_filter = false
    ) {
        if (!file_exists($this->config_template_dir ."/". $template)) {
            static::raiseError(sprintf(
                __METHOD__ .'(), unable to locate %s in directory %s',
                $template,
                $this->config_template_dir
            ));
            return false;
        }

        // Now call parent method
        try {
            $result =  $this->smarty->fetch(
                $template,
                $cache_id,
                $compile_id,
                $parent,
                $display,
                $merge_tpl_vars,
                $no_output_filter
            );
        } catch (\SmartyException $e) {
            static::raiseError(__METHOD__ .'(), Smarty has thrown an exception!', false, $e);
            return false;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), an exception occured!', false, $e);
            return false;
        }

        return $result;
    }

    public function getMenuState($params, &$smarty)
    {
        global $query;

        if (!array_key_exists('page', $params)) {
            static::raiseError(__METHOD__ .'(), missing "page" parameter!');
            $repeat = false;
            return false;
        }

        if ($params['page'] == $query->view) {
            return "active";
        }

        return null;
    }

    public function getHumanReadableFilesize($params, &$smarty)
    {
        global $query;

        if (!array_key_exists('size', $params)) {
            static::raiseError(__METHOD__ .'(), missing "size" parameter!');
            $repeat = false;
            return false;
        }

        if ($params['size'] < 1048576) {
            return round($params['size']/1024, 2) ."KB";
        }

        return round($params['size']/1048576, 2) ."MB";
    }

    public function assign($key, $value)
    {
        if (!$this->smarty->assign($key, $value)) {
            static::raiseError(get_class($this->smarty) .'::assign() returned false!');
            return false;
        }

        return true;
    }

    public function hasPlugin($type, $name)
    {
        if (!array_key_exists($type, $this->smarty->smarty->registered_plugins)) {
            return false;
        }

        if (!array_key_exists($name, $this->smarty->smarty->registered_plugins[$type])) {
            return false;
        }

        return true;
    }

    public function registerPlugin($type, $name, $callback, $cacheable = true)
    {
        if ($this->hasPlugin($type, $name)) {
            return true;
        }

        if (!$this->smarty->registerPlugin($type, $name, $callback, $cacheable)) {
            static::raiseError(get_class($this->smarty) .'::registerPlugin() returned false!');
            return false;
        }

        return true;
    }

    public function templateExists($tmpl)
    {
        if (!$this->smarty->templateExists($tmpl)) {
            return false;
        }

        return true;
    }

    public static function getUrl($params)
    {
        global $config, $views;

        if (!isset($params) ||
            empty($params) ||
            !is_array($params)
        ) {
            static::raiseError(__METHOD__ .'(), $params parameter is invalid!');
            return false;
        }

        if (!array_key_exists('page', $params) ||
            empty($params['page']) ||
            !is_string($params['page'])
        ) {
            static::raiseError(__METHOD__ .'(), missing "page" parameter!');
            return false;
        }

        if (array_key_exists('mode', $params)) {
            if (($view = $views->getView($params['page'])) === false) {
                static::raiseError(get_class($views) .'::getView() returned false!');
                return false;
            }

            if (!isset($view) || empty($view) || !is_object($view)) {
                static::raiseError(get_class($views) .'::getView() returned invalid data!');
                return false;
            }

            if (!$view->isValidMode($params['mode'])) {
                static::raiseError(get_class($view) .'::isValidMode() returned false!');
                return false;
            }
        }

        if (($url = $config->getWebPath()) === false) {
            static::raiseError(get_class($config) .'::getWebPath() returned false!');
            return false;
        }

        if ($url == '/') {
            $url = "";
        }

        $url.= '/'. $params['page'] .'/';

        if (array_key_exists('mode', $params) && !empty($params['mode'])) {
            $url.= $params['mode'] .'/';
        }

        if (array_key_exists('id', $params) && !empty($params['id'])) {
            $url.= $params['id'] .'/';
        }

        if (array_key_exists('file', $params) && !empty($params['file'])) {
            $url.= $params['file'] .'/';
        }

        if (!array_key_exists('number', $params) &&
            !array_key_exists('items_per_page', $params)) {
            return $url;
        }

        if (array_key_exists('number', $params)) {
            $url.= "list-{$params['number']}.html";
        }

        if (array_key_exists('items_per_page', $params)) {
            $url.= "?items-per-page=". $params['items_per_page'];
        }

        return $url;
    }

    public static function smartyRaiseError($params)
    {
        if (array_key_exists('message', $params)) {
            $message = $params['message'];
        } else {
            $message = 'unknown error';
        }

        if (array_key_exists('stop', $params)) {
            $stop = $params['stop'];
        } else {
            $stop = false;
        }

        if (!isset($message) || empty($message) || !is_string($message)) {
            static::raiseError(__METHOD__ .'(), $message parameter is invalid!');
            return false;
        }

        if (!isset($stop) || !is_bool($stop)) {
            static::raiseError(__METHOD__ .'(), $stop parameter is invalid!');
            return false;
        }

        static::raiseError($message, $stop);
        return;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
