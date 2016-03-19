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

function autoload($class)
{
    $prefixes = array(
        'Thallium',
        'MasterShaper',
    );

    $class = str_replace("\\", "/", $class);
    $parts = explode('/', $class);

    if (!is_array($parts) || empty($parts)) {
        return;
    }

    # only take care outloading of our namespace
    if (!in_array($parts[0], $prefixes)) {
        return;
    }

    $filename = APP_BASE;
    $filename.= "/vendor/";
    if (isset($subdir) || !empty($subdir)) {
        $filename.= $subdir;
    }
    $filename.= implode('/', $parts);
    $filename.= '.php';

    if (!file_exists($filename)) {
        return;
    }
    if (!is_readable($filename)) {
        return;
    }

    require_once $filename;
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
