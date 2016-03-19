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

class ViewsController extends DefaultController
{
    protected static $page_map = array(
        '/^$/' => 'MainView',
        '/^main$/' => 'MainView',
        '/^about$/' => 'AboutView',
    );
    protected $page_skeleton;

    public function __construct()
    {
        global $thallium;

        if (!$thallium->loadController('Templates', 'tmpl')) {
            static::raiseError(get_class($thallium) .'::loadController() returned false!', true);
            return false;
        }

        try {
            $this->page_skeleton = new \Thallium\Views\SkeletonView;
        } catch (\Exception $e) {
            static::raiseError(__CLASS__ .', unable to load SkeletonView!', true, $e);
            return false;
        }

        return true;
    }

    public function getViewName($view)
    {
        global $thallium;

        foreach (array_keys(static::$page_map) as $entry) {
            if (($result = preg_match($entry, $view)) === false) {
                static::raiseError(__METHOD__ ."(), unable to match ${entry} in ${view}");
                return false;
            }

            if ($result == 0) {
                continue;
            }

            if (!($prefix = $thallium->getNamespacePrefix())) {
                static::raiseError(get_class($thallium) .'::getNamespacePrefix() returned false!');
                return false;
            }

            if (!class_exists('\\'. $prefix .'\\Views\\'.static::$page_map[$entry])) {
                static::raiseError(__METHOD__ ."(), view class ". static::$page_map[$entry] ." does not exist!");
                return false;
            }

            return static::$page_map[$entry];
        }
    }

    public function load($view, $skeleton = true)
    {
        global $thallium, $tmpl;

        if (!($prefix = $thallium->getNamespacePrefix())) {
            static::raiseError(get_class($thallium) .'::getNamespacePrefix() returned false!');
            return false;
        }

        if (!isset($view) || empty($view) || !is_string($view)) {
            $this->raiseError(__METHOD__ .'(), $view parameter is invalid!');
            return false;
        }

        $view = '\\'. $prefix .'\\Views\\'.$view;

        try {
            $page = new $view;
        } catch (Exception $e) {
            static::raiseError(__METHOD__ ."(), failed to load '{$view}'!");
            return false;
        }

        if (!$skeleton) {
            return $page->show();
        }

        if (($content = $page->show()) === false) {
            return false;
        }

        // if $content=true, View has handled output already, we are done
        if ($content === true) {
            return true;
        }

        if (!empty($content)) {
            $tmpl->assign('page_content', $content);
        }

        return $this->page_skeleton->show();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
