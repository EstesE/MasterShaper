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

namespace MasterShaper\Views;

abstract class DefaultView extends \Thallium\Views\DefaultView
{
    protected static $view_class_name = "main";

    public function __construct()
    {
        parent::__construct();

        global $tmpl;

        $tmpl->assign('view', $this);
        $tmpl->registerPlugin(
            "function",
            "form_buttons",
            array(&$this, "smartyFormButtons"),
            false
        );
        return;
    }

    public function hasOption($option)
    {
        global $ms;

        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$ms->hasOption($option)) {
            return false;
        }

        return true;
    }

    public function getOption($option)
    {
        global $ms;

        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            static::raiseError(__CLASS__ .'::hasOption() returned false!');
            return false;
        }

        if (($value = $ms->getOption($option)) === false) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        return $value;
    }

    public function smartyFormButtons($params, &$smarty)
    {
        global $db;

        if (array_key_exists('submit', $params) && $params['submit']) {
            $smarty->assign('submit', true);
        }
        if (array_key_exists('discard', $params) && $params['discard']) {
            $smarty->assign('discard', true);
        }
        if (array_key_exists('reset', $params) && $params['reset']) {
            $smarty->assign('reset', true);
        }

        return $smarty->fetch('form_buttons.tpl');
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
