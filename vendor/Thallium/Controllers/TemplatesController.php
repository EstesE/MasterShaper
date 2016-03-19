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

use \Smarty;

class TemplatesController extends DefaultController
{
    protected $smarty;

    protected $config_template_dir;
    protected $config_compile_dir;
    protected $config_config_dir;
    protected $config_cache_dir;
    protected $supported_modes = array (
            'list',
            'show',
            'edit',
            'delete',
            'add',
            'upload',
            'truncate',
            );
    protected $default_mode = "list";

    public function __construct()
    {
        global $config, $views, $thallium;

        try {
            $this->smarty = new Smarty;
        } catch (\Exception $e) {
            static::raiseError('Failed to load Smarty!', true);
            return false;
        }

        if (!($prefix = $thallium->getNamespacePrefix())) {
            static::raiseError(get_class($thallium) .'::getNameSpacePrefix() returned false!', true);
            return false;
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
                "Cache directory ". CACHE_DIRECTORY ." is not writeable"
                ."for user (". $this->getuid() .").<br />\n"
                ."Please check that permissions are set correctly to this directory.<br />\n",
                true
            );
        }

        if (!file_exists($this->config_compile_dir) && !mkdir($this->config_compile_dir, 0700)) {
            static::raiseError("Failed to create directory ". $this->config_compile_dir, true);
            return false;
        }

        if (!is_writeable($this->config_compile_dir)) {
            static::raiseError(
                "Error - Smarty compile directory ". $this->config_compile_dir ." is not writeable
                for the current user (". $this->getuid() .").<br />\n
                Please check that permissions are set correctly to this directory.<br />\n",
                true
            );
            return false;
        }

        $this->smarty->setTemplateDir($this->config_template_dir);
        $this->smarty->setCompileDir($this->config_compile_dir);
        $this->smarty->setConfigDir($this->config_config_dir);
        $this->smarty->setCacheDir($this->config_cache_dir);

        if (!($app_web_path = $config->getWebPath())) {
            static::raiseError("Web path is missing!", true);
            return false;
        }

        if ($app_web_path == '/') {
            $app_web_path = '';
        }

        if (!($page_title = $config->getPageTitle())) {
            $page_title = 'Thallium v'. MainController::FRAMEWORK_VERSION;
        }

        $this->smarty->assign('app_web_path', $app_web_path);
        $this->smarty->assign('page_title', $page_title);
        $this->registerPlugin("function", "get_url", array(&$this, "getUrl"), false);
        $this->registerPlugin(
            "function",
            "get_humanreadable_filesize",
            array(&$this, "getHumanReadableFilesize"),
            false
        );
        return true;
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
            static::raiseError("Unable to locate ". $template ." in directory ". $this->config_template_dir);
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
            static::raiseError("Smarty throwed an exception! ". $e->getMessage());
            return false;
        } catch (\Exception $e) {
            static::raiseError('An exception occured: '. $e->getMessage());
            return false;
        }

        return $result;
    }

    public function getMenuState($params, &$smarty)
    {
        global $query;

        if (!array_key_exists('page', $params)) {
            static::raiseError("getMenuState: missing 'page' parameter", E_USER_WARNING);
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
            static::raiseError("getMenuState: missing 'size' parameter", E_USER_WARNING);
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

    public function registerPlugin($type, $name, $callback, $cacheable = true)
    {
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

    public function getUrl($params, &$smarty)
    {
        global $config;

        if (!array_key_exists('page', $params)) {
            static::raiseError("getUrl: missing 'page' parameter", E_USER_WARNING);
            $repeat = false;
            return false;
        }

        if (array_key_exists('mode', $params) && !$this->isSupportedMode($params['mode'])) {
            static::raiseError("getUrl: value of parameter 'mode' ({$params['mode']}) isn't supported", E_USER_WARNING);
            $repeat = false;
            return false;
        }

        if (!($url = $config->getWebPath())) {
            static::raiseError("Web path is missing!");
            return false;
        }

        if ($url == '/') {
            $url = "";
        }

        $url.= "/";
        $url.= $params['page'] ."/";

        if (isset($params['mode']) && !empty($params['mode'])) {
            $url.= $params['mode'] ."/";
        }

        if (array_key_exists('id', $params) && !empty($params['id'])) {
            $url.= $params['id'];
        }
        if (array_key_exists('file', $params) && !empty($params['file'])) {
            $url.= '/'. $params['file'];
        }

        return $url;

    } // getUrl()

    public function addSupportedMode($mode)
    {
        if (!isset($mode) || empty($mode) || !is_string($mode)) {
            static::raiseError(__METHOD__ .'(), $mode parameter is invalid!');
            return false;
        }

        if (in_array($mode, $this->supported_modes)) {
            return true;
        }

        array_push($this->supported_modes, $mode);
        return true;
    }

    public function isSupportedMode($mode)
    {
        if (!isset($mode) || empty($mode) || !is_string($mode)) {
            static::raiseError(__METHOD__ .'(), $mode parameter is invalid!');
            return false;
        }

        if (($modes = $this->getSupportedModes()) === false) {
            static::raiseError(__CLASS__ .'::getSupportedModes() returned false!');
            return false;
        }

        if (!in_array($mode, $modes)) {
            return false;
        }

        return true;
    }

    public function getSupportedModes()
    {
        if (!isset($this->supported_modes)) {
            return false;
        }

        return $this->supported_modes;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
