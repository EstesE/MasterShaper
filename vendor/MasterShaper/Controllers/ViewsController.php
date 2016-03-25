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

class ViewsController extends \Thallium\Controllers\ViewsController
{
    protected static $page_map = array(
        '/^$/' => 'OverviewView',
        '/^main$/' => 'OverviewView',
        '/^overview$/' => 'OverviewView',
        '/^login$/' => 'LoginView',
        '/^logout$/' => 'LogoutView',
        '/^chains$/' => 'ChainsView',
        '/^pipes$/' => 'PipesView',
        '/^filters$/' => 'FiltersView',
        '/^targets$/' => 'TargetsView',
        '/^ports$/' => 'PortsView',
        '/^protocols$/' => 'ProtocolsView',
        '/^servicelevels$/' => 'ServiceLevelsView',
        '/^interfaces$/' => 'InterfacesView',
        '/^networkpaths$/' => 'NetworkPathsView',
        '/^users$/' => 'UsersView',
        '/^hostprofiles$/' => 'hostprofiles',
        '/^settings$/' => 'SettingsView',
        '/^options$/' => 'OptionsView',
        '/^rules$/' => 'RulesView',
        '/^others$/' => 'OthersView',
        '/^tasklist$/' => 'TaskListView',
        '/^monitoring$/' => 'MonitoringView',
        '/^update$/' => 'UpdateView',
        '/^about$/' => 'AboutView',
    );
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
