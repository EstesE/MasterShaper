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

namespace MasterShaper\Views;

class NetworkInterfacesView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'network_interfaces';

    public function __construct()
    {
        try {
            $network_interfaces = new \MasterShaper\Models\NetworkInterfacesModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkInterfacesModel!', true, $e);
            return;
        }

        if (!$this->setViewData($network_interfaces)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        parent::__construct();
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        try {
            $item = new \MasterShaper\Models\NetworkInterfaceModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load NetworkInterfaceModel!', false, $e);
            return false;
        }

        $tmpl->assign('if', $item);
        return parent::showEdit($id, $guid);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
