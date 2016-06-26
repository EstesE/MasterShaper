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

class RequirementsController extends \Thallium\Controllers\RequirementsController
{
    protected function checkExternalLibraries()
    {
        global $ms;

        $missing = false;

        ini_set('track_errors', 1);
        @include_once 'Net/IPv4.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            static::raiseError("PEAR Net_IPv4 package is missing!");
            $missing = true;
            unset($php_errormsg);
        }
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "PEAR Pager package is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }
        @include_once 'smarty3/Smarty.class.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "Smarty3 template engine is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }
        @include_once 'System/Daemon.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "PEAR System_Daemon package is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }
        ini_restore('track_errors');

        if ($missing) {
            return false;
        }

        return parent::checkExternalLibraries();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
