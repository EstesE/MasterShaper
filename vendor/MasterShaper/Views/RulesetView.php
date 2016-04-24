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

class RulesetView extends DefaultView
{
    protected static $view_default_mode = 'show';
    protected static $view_class_name = 'ruleset';

    public function __construct()
    {
        $this->addMode('load');

    } // __construct()

    public function show()
    {
        global $ms, $tmpl;

        /* If authentication is enabled, check permissions */
        if ($ms->getOption("authentication") == "Y" &&
            !$ms->checkPermissions("user_show_rules")
        ) {
            $ms->printError(
                "<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - ". _("Show rules"),
                _("You do not have enough permissions to access this module!")
            );
            return 0;
        }

        $tmpl->registerPlugin("function", "ruleset_output", array(&$this, "smartyRulesetOutput"), false);
        return $tmpl->fetch("ruleset_show.tpl");

    } // show

    public function smartyRulesetOutput($params, &$smarty)
    {
        $ruleset = new \MasterShaper\Controllers\RulesetController;

        if ($ruleset->initRules()) {
            return $ruleset->showIt();
        }

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
