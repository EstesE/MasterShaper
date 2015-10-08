<?php

/**
 *
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2015 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

define('MASTERSHAPER_BASE', __DIR__);

define('SIGN_TOP_LEFT', 1);
define('SIGN_TOP_CENTER', 2);
define('SIGN_TOP_RIGHT', 3);
define('SIGN_MIDDLE_LEFT', 4);
define('SIGN_MIDDLE_CENTER', 5);
define('SIGN_MIDDLE_RIGHT', 6);
define('SIGN_BOTTOM_LEFT', 7);
define('SIGN_BOTTOM_CENTER', 8);
define('SIGN_BOTTOM_RIGHT', 9);

if (!constant('LOG_ERR')) {
    define('LOG_ERR', 1);
}
if (!constant('LOG_WARNING')) {
    define('LOG_WARNING', 2);
}
if (!constant('LOG_INFO')) {
    define('LOG_INFO', 3);
}
if (!constant('LOG_DEBUG')) {
    define('LOG_DEBUG', 4);
}

function autoload($class)
{
    require_once "controllers/exception.php";

    $class = str_replace("\\", "/", $class);
    $parts = explode('/', $class);

    if (!is_array($parts) || empty($parts)) {
        error("failed to extract class names!");
        exit(1);
    }

    # only take care outloading of our namespace
    if ($parts[0] != "MasterShaper") {
        return;
    }

    // remove leading 'MasterShaper'
    array_shift($parts);

    // remove *Controller from ControllerName
    if (preg_match('/^(.*)Controller$/', $parts[1])) {
        $parts[1] = preg_replace('/^(.*)Controller$/', '$1', $parts[1]);
    }
    // remove *View from ViewName
    if (preg_match('/^(.*)View$/', $parts[1])) {
        $parts[1] = preg_replace('/^(.*)View$/', '$1', $parts[1]);
    }
    // remove *Model from ModelName
    if (preg_match('/^(.*)Model$/', $parts[1])) {
        $parts[1] = preg_replace('/^(.*)Model$/', '$1', $parts[1]);
    }

    $filename = MASTERSHAPER_BASE;
    $filename.= "/";
    $filename.= strtolower(implode('/', $parts));
    $filename.= '.php';

    if (!file_exists($filename)) {
        error("File ". $filename ." does not exist!");
        exit(1);
    }
    if (!is_readable($filename)) {
        error("File ". $filename ." is not readable!");
        exit(1);
    }

    require_once $filename;
}

function error($string)
{
    print "<br /><br />". $string ."<br /><br />\n";

    try {
        throw new MasterShaper\Controllers\ExceptionController;
    } catch (MasterShaper\Controllers\ExceptionController $e) {
        print "<br /><br />\n";
        print $e->getMessage();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
