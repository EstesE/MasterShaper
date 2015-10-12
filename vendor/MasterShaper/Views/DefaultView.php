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
    public $class_name = "main";
    public $supported_modes = array (
        'list',
        'show',
        'edit',
        'delete',
        'add',
        'upload',
        'truncate',
    );
    public $default_mode = "list";

    public function __construct()
    {
        global $ms, $config, $session, $tmpl;

        if (!isset($this->class_name) || empty($this->class_name)) {
            $ms->raiseError("Class has not defined property 'class_name'. Something is wrong with it", true);
            return false;
        }

        /*if ($session->isLoggedIn()) {

          if (!($user = $session->getUserDetails())) {
          $ms->raiseError(get_class($session) .'::getUserDetails() returned false!', true);
          return false;
          }

          $this->assign('user_name', $user->user_name);
          return true;
          }*/

        return true;
    }

    /**
     * returns true if storing is requested
     *
     * @return bool
     */
    public function isStoring()
    {
        if (!isset($_POST['action']) || empty($_POST['action'])) {
            return false;
        }

        if ($_POST['action'] == 'store') {
            return true;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
