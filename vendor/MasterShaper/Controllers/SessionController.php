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

class SessionController extends \Thallium\Controllers\SessionController
{
    public function getUserDetails($user_name = null)
    {
        global $ms, $db, $session;

        if (!isset($user_name) || empty($user_name)) {
            if (!($user_name = $this->getUserName())) {
                static::raiseError(__METHOD__ .', have no user_name to check!');
                return false;
            }
        }

        $sql =
            "SELECT
                user_idx,
                user_pass
            FROM
                TABLEPREFIXusers
            WHERE
                user_name LIKE ?
            AND
                user_active='Y'";

        if (!($sth = $db->prepare($sql))) {
            static::raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!$db->execute($sth, array($user_name))) {
            $db->freeStatement($sth);
            static::raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        if (!($user = $sth->fetch())) {
            $db->freeStatement($sth);
            static::raiseError(__METHOD__ .', found no matching user!');
            return false;
        }

        $db->freeStatement($sth);
        return $user;
    }

    /**
     * check login status
     *
     * return true if user is logged in
     * return false if user is not yet logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        if (!isset($_SESSION['user_name']) ||
            empty($_SESSION['user_name'])
        ) {
            return false;
        }

        return true;

    } // isLoggedIn()

    public function getUserName()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return $_SESSION['user_name'];
    }

    public function loginRequest($login_name, $login_pass)
    {
        if (!($user = $this->getUserDetails($login_name))) {
            static::raiseError(__METHOD__ .', '. _("Invalid or inactive User."));
        }

        if ($user->user_pass != md5($_POST['user_pass'])) {
            static::raiseError(_("Invalid Password."));
        }

        $_SESSION['user_name'] = $_POST['user_name'];
        $_SESSION['user_idx'] = $user->user_idx;
        return true;

    }

    public function getCurrentHostProfile()
    {
        if (isset($_SESSION['host_profile']) &&
            !empty($_SESSION['host_profile']) &&
            is_numeric($_SESSION['host_profile']) &&
            $_SESSION['host_profile'] > 0
        ) {
            return $_SESSION['host_profile'];
        }

        return 1;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
