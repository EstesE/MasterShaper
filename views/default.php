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

namespace MasterShaper\Views ;

abstract class DefaultView extends Templates
{
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
        global $ms, $config, $session;

        parent::__construct();

        if (!isset($this->class_name)) {
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

    public function show()
    {
        global $ms, $query, $router;

        if (isset($query->params)) {
            $params = $query->params;
        }

        if (
            (!isset($params) || empty($params)) &&
            $this->default_mode == "list"
        ) {
            $mode = "list";
        } elseif (isset($params) && !empty($params)) {
            if (isset($params[0]) && $this->isKnownMode($params[0])) {
                $mode = $params[0];
            }
        } elseif ($this->default_mode == "show") {
            $mode = "show";
        }

        if (!isset($mode)) {
            $ms->raiseError("\$mode not set - do not know how to proceed!");
            return false;
        }

        if ($mode == "list" && $this->templateExists($this->class_name ."_list.tpl")) {

            return $this->showList();

        } elseif ($mode == "edit" && $this->templateExists($this->class_name ."_edit.tpl")) {

            if (!$item = $router->parseQueryParams()) {
                $ms->raiseError("HttpRouterController::parseQueryParams() returned false!");
                return false;
            }
            if (
                empty($item) ||
                !is_array($item) ||
                !isset($item['id']) ||
                empty($item['id']) ||
                !isset($item['hash']) ||
                empty($item['hash']) ||
                !$ms->isValidId($item['id']) ||
                !$ms->isValidGuidSyntax($item['hash'])
            ) {
                $ms->raiseError("HttpRouterController::parseQueryParams() was unable to parse query parameters!");
                return false;
            }
            return $this->showEdit($item['id'], $item['hash']);

        } elseif ($mode == "show" && $this->templateExists($this->class_name ."_show.tpl")) {

            if (!$item = $router->parseQueryParams()) {
                $ms->raiseError("HttpRouterController::parseQueryParams() returned false!");
            }
            if (
                empty($item) ||
                !is_array($item) ||
                !isset($item['id']) ||
                empty($item['id']) ||
                !isset($item['hash']) ||
                empty($item['hash']) ||
                !$ms->isValidId($item['id']) ||
                !$ms->isValidGuidSyntax($item['hash'])
            ) {
                $ms->raiseError("HttpRouterController::parseQueryParams() was unable to parse query parameters!");
                return false;
            }
            return $this->showItem($item['id'], $item['hash']);

        } elseif ($this->templateExists($this->class_name .".tpl")) {
            return $this->fetch($this->class_name .".tpl");
        }

        $ms->raiseError("All methods utilized but still don't know what to show!");
        return false;
    }

    public function showList()
    {
        $this->registerPlugin("block", $this->class_name ."_list", array(&$this, $this->class_name ."List"));
        return $this->fetch($this->class_name ."_list.tpl");
    }

    public function showEdit($id)
    {
        $this->assign('item', $id);
        return $this->fetch($this->class_name ."_edit.tpl");
    }

    public function showItem($id, $hash)
    {
        return $this->fetch($this->class_name ."_show.tpl");
    }

    private function isKnownMode($mode)
    {
        $valid_modes = array(
            'list',
            'edit',
            'show',
        );

        if (!in_array($mode, $valid_modes)) {
            return false;
        }

        return true;
    }

   /**
    * returns true if storing is requested
    *
    * @return bool
    */
   public function is_storing()
   {
      if(!isset($_POST['action']) || empty($_POST['action']))
         return false;

      if($_POST['action'] == 'store')
         return true;

      return false;

   } // is_storing()
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
