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

class ViewsController extends DefaultController
{
    protected static $page_map = array(
        '/^$/' => 'MainView',
        '/^main$/' => 'MainView',
        '/^about$/' => 'AboutView',
    );
    protected $page_skeleton;
    protected $loaded_views = array();

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

    public static function getViewName($view)
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

            if (($prefix = $thallium->getNamespacePrefix()) === false) {
                static::raiseError(get_class($thallium) .'::getNamespacePrefix() returned false!');
                return false;
            }

            if (!isset($prefix) || empty($prefix) || !is_string($prefix)) {
                static::raiseError(get_class($thallium) .'::getNamespacePrefix() returned no valid data!');
                return false;
            }

            if (!class_exists('\\'. $prefix .'\\Views\\'.static::$page_map[$entry])) {
                static::raiseError(__METHOD__ ."(), view class ". static::$page_map[$entry] ." does not exist!");
                return false;
            }

            $view = '\\'. $prefix .'\\Views\\'.static::$page_map[$entry];
            return $view;
        }
    }

    public function getView($view)
    {
        if (!isset($view) || empty($view) || !is_string($view)) {
            static::raiseError(__METHOD__ .'(), $view parameter is invalid!');
            return false;
        }

        if (($view_class = static::getViewName($view)) === false) {
            static::raiseError(__CLASS__ .'::getViewName() returned false!');
            return false;
        }

        if (!isset($view_class) || empty($view_class) || !is_string($view_class)) {
            static::raiseError(__CLASS__ .'::getViewName() returned invalid data!');
            return false;
        }

        if ($this->isLoadedView($view_class)) {
            return $this->getLoadedView($view_class);
        }

        try {
            $view_obj = new $view_class;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ ."(), failed to load '{$view}'!", true, $e);
            return false;
        }

        $this->loaded_views[$view_class] =& $view_obj;
        return $view_obj;
    }

    public function load($view, $skeleton = true)
    {
        global $thallium, $tmpl;

        if (($page = $this->getView($view)) === false) {
            static::raiseError(__CLASS__ .'::getView() returned false!');
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

    protected function isLoadedView($name)
    {
        if (!isset($this->loaded_views[$name]) || empty($this->loaded_views[$name])) {
            return false;
        }

        return true;
    }

    protected function getLoadedView($name)
    {
        if (!$this->isLoadedView($name)) {
            static::raiseError(__CLASS__ .'::isViewLoaded() returned false!');
            return false;
        }

        return $this->loaded_views[$name];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
