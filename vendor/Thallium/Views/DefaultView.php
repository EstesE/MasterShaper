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

namespace Thallium\Views;

abstract class DefaultView
{
    protected static $view_default_mode = "list";
    protected static $view_class_name;
    protected static $view_default_modes = array(
        'list',
        'show',
        'edit',
    );
    protected $view_modes = array();

    public function __construct()
    {
        if (!static::validateView()) {
            static::raiseError(__CLASS__ .'::validateView() returned false!', true);
            return;
        }
    }

    protected static function validateView()
    {
        if (!isset(static::$view_default_mode) ||
            empty(static::$view_default_mode) ||
            !is_string(static::$view_default_mode)
        ) {
            static::raiseError(__METHOD__ .'(), $view_default_mode is invalid!');
            return false;
        }

        if (!isset(static::$view_class_name) ||
            empty(static::$view_class_name) ||
            !is_string(static::$view_class_name)
        ) {
            static::raiseError(__METHOD__ .'(), $view_class_name is invalid!');
            return false;
        }

        return true;
    }

    public function show()
    {
        global $thallium, $query, $router, $tmpl;

        if (isset($query->params)) {
            $params = $query->params;
        }

        if ((!isset($params) || empty($params)) &&
            static::$view_default_mode == "list"
        ) {
            $mode = "list";
        } elseif (isset($params) && !empty($params)) {
            if (isset($params[0]) && !empty($params[0]) &&
                static::isKnownMode($params[0])
            ) {
                $mode = $params[0];
            }
        } elseif (static::$view_default_mode == "show") {
            $mode = "show";
        }

        if (!isset($mode)) {
            $mode = static::$view_default_mode;
        }

        if ($mode == "list" && $tmpl->templateExists(static::$view_class_name ."_list.tpl")) {
            return $this->showList();
        } elseif ($mode == "edit" && $tmpl->templateExists(static::$view_class_name ."_edit.tpl")) {
            if (($item = $router->parseQueryParams()) === false) {
                static::raiseError("HttpRouterController::parseQueryParams() returned false!");
                return false;
            }
            if (empty($item) ||
                !is_array($item) ||
                !isset($item['id']) ||
                empty($item['id']) ||
                !isset($item['guid']) ||
                empty($item['guid']) ||
                !$thallium->isValidId($item['id']) ||
                !$thallium->isValidGuidSyntax($item['guid'])
            ) {
                static::raiseError("HttpRouterController::parseQueryParams() was unable to parse query parameters!");
                return false;
            }
            return $this->showEdit($item['id'], $item['guid']);

        } elseif ($mode == "show" && $tmpl->templateExists(static::$view_class_name ."_show.tpl")) {
            if (($item = $router->parseQueryParams()) === false) {
                static::raiseError("HttpRouterController::parseQueryParams() returned false!");
            }
            if (empty($item) ||
                !is_array($item) ||
                !isset($item['id']) ||
                empty($item['id']) ||
                !isset($item['guid']) ||
                empty($item['guid']) ||
                !$thallium->isValidId($item['id']) ||
                !$thallium->isValidGuidSyntax($item['guid'])
            ) {
                static::raiseError("HttpRouterController::parseQueryParams() was unable to parse query parameters!");
                return false;
            }
            return $this->showItem($item['id'], $item['guid']);

        } elseif ($tmpl->templateExists(static::$view_class_name .".tpl")) {
            return $tmpl->fetch(static::$view_class_name .".tpl");
        }

        static::raiseError(__METHOD__ .'(), all methods utilized but still do not know what to show!');
        return false;
    }

    public function showList()
    {
        global $tmpl;

        $template_name = static::$view_class_name ."_list.tpl";

        if (!$tmpl->templateExists($template_name)) {
            static::raiseError(__METHOD__ ."(), template '{$template_name}' does not exist!");
            return false;
        }

        $tmpl->registerPlugin(
            'block',
            static::$view_class_name ."_list",
            array(&$this, static::$view_class_name ."List")
        );
        return $tmpl->fetch($template_name);
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        $tmpl->assign('item', $id);

        $template_name = static::$view_class_name ."_edit.tpl";

        if (!$tmpl->templateExists($template_name)) {
            static::raiseError(__METHOD__ ."(), template '{$template_name}' does not exist!");
            return false;
        }

        return $tmpl->fetch($template_name);
    }

    public function showItem($id, $guid)
    {
        global $tmpl;

        $template_name = static::$view_class_name ."_show.tpl";

        if (!$tmpl->templateExists($template_name)) {
            static::raiseError(__METHOD__ ."(), template '{$template_name}' does not exist!");
            return false;
        }

        return $tmpl->fetch($template_name);
    }

    protected static function isKnownMode($mode)
    {
        if (!in_array($mode, static::$view_modes)) {
            return false;
        }

        return true;
    }

    protected static function raiseError($string, $stop_execution = false, $exception = null)
    {
        global $thallium;

        $thallium::raiseError(
            $string,
            $stop_execution,
            $exception
        );

        return true;
    }

    public function addMode($mode)
    {
        if (!isset($mode) || empty($mode) || !is_string($mode)) {
            static::raiseError(__METHOD__ .'(), $mode parameter is invalid!');
            return false;
        }

        if (in_array($mode, static::$view_default_modes)) {
            return true;
        }

        if (isset($this->view_modes) &&
            !empty($this->view_modes) &&
            is_array($this->view_modes) &&
            in_array($mode, $this->view_modes)
        ) {
            return true;
        }

        array_push($this->view_modes, $mode);
        return true;
    }

    public function isValidMode($mode)
    {
        if (!isset($mode) || empty($mode) || !is_string($mode)) {
            static::raiseError(__METHOD__ .'(), $mode parameter is invalid!');
            return false;
        }

        if (($modes = $this->getModes()) === false) {
            static::raiseError(__CLASS__ .'::getModes() returned false!');
            return false;
        }

        if (!in_array($mode, $modes)) {
            return false;
        }

        return true;
    }

    public function getModes()
    {
        if (!isset($this->view_modes) || empty($this->view_modes) || !is_array($this->view_modes)) {
            return static::$view_default_modes;
        }

        return array_merge(static::$view_default_modes, $this->view_modes);
    }

    protected function getSessionVar($name)
    {
        global $session;

        if (!$session->hasVariable($name, static::$view_class_name)) {
            return false;
        }

        if (($value = $session->getVariable($name, static::$view_class_name)) === false) {
            static::raiseError(get_class($session) .'::getVariable() returned false!');
            return false;
        }

        return $value;
    }

    protected function setSessionVar($name, $value)
    {
        global $session;

        if (!$session->setVariable(
            $name,
            $value,
            static::$view_class_name
        )) {
            static::raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
