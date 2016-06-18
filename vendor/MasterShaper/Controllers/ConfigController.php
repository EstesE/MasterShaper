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

class ConfigController extends \Thallium\Controllers\ConfigController
{
    public function getTcPath()
    {
        if (!isset($this->config['app']['tc_bin']) ||
            empty($this->config['app']['tc_bin']) ||
            !is_string($this->config['app']['tc_bin'])
        ) {
            return false;
        }

        return $this->config['app']['tc_bin'];
    }

    public function getIptablesPath()
    {
        if (!isset($this->config['app']['ipt_bin']) ||
            empty($this->config['app']['ipt_bin']) ||
            !is_string($this->config['app']['ipt_bin'])
        ) {
            return false;
        }

        return $this->config['app']['ipt_bin'];
    }

    public function getSudoPath()
    {
        if (!isset($this->config['app']['sudo_bin']) ||
            empty($this->config['app']['sudo_bin']) ||
            !is_string($this->config['app']['sudo_bin'])
        ) {
            return false;
        }

        return $this->config['app']['sudo_bin'];
    }

    public function getScriptTimeout()
    {
        if (!isset($this->config['app']['script_timeout']) ||
            empty($this->config['app']['script_timeout']) ||
            !is_string($this->config['app']['script_timeout'])
        ) {
            return false;
        }

        return $this->config['app']['script_timeout'];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
