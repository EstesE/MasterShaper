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

    /*
        if ($session->isLoggedIn()) {
          if (!($user = $session->getUserDetails())) {
          $ms->raiseError(get_class($session) .'::getUserDetails() returned false!', true);
          return false;
      }
          $this->assign('user_name', $user->user_name);
          return true;
          }
    */
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
