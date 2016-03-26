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

    /**
     * Page_Ruleset constructor
     *
     * Initialize the Page_Ruleset class
     */
    public function __construct()
    {
        $this->addMode('load');
        //$this->rights = 'user_manage_rules';

    } // __construct()

    public function handler()
    {
        global $tmpl, $page, $ms;

        if (isset($this->rights) && !$ms->is_cmdline()) {
            /* If authentication is enabled, check permissions */
            if ($ms->getOption("authentication") == "Y" && !$ms->checkPermissions($this->rights)) {
                $ms->raiseError(
                    "<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"),
                    _("You do not have enough permissions to access this module!")
                );
                return 0;
            }
        }

        switch ($page->action) {
            default:
            case 'show':
                $content = $this->show();
                break;
            case 'load':
                $content = $this->load();
                break;
            case 'load-debug':
                $content = $this->load(1);
                break;
            case 'unload':
                $content = $this->unload();
                break;
        }

        if (isset($content)) {
            $tmpl->assign('content', $content);
        }

    } // handler()

    /* This function prepares the rule setup according configuration and calls tc with a batchjob */
    public function show($state = 0)
    {
        global $ms, $tmpl;

        /* If authentication is enabled, check permissions */
        if ($ms->getOption("authentication") == "Y" &&
            !$ms->checkPermissions("user_show_rules")) {

            $ms->printError(
                "<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - ". _("Show rules"),
                _("You do not have enough permissions to access this module!")
            );
            return 0;

        }

        $tmpl->registerPlugin("function", "ruleset_output", array(&$this, "smartyRulesetOutput"), false);
        return $tmpl->fetch("ruleset_show.tpl");

    } // show

    /**
     * load MasterShaper ruleset
     */
    public function load($debug = null)
    {
        global $ms;

        /* If authentication is enabled, check permissions */
        if (!$ms->is_cmdline() && $ms->getOption("authentication") == "Y" &&
                !$ms->checkPermissions("user_load_rules")) {

            $ms->printError(
                "<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - ". _("Load rules"),
                _("You do not have enough permissions to access this module!")
            );
            return 0;
        }

        if (!isset($debug)) {
            $ms->add_task('RULES_LOAD');
        } else {
            $ms->add_task('RULES_LOAD_DEBUG');
        }

        return "Ruleset load task submitted to job queue.";

    } // load()

    /**
     * unload MasterShaper ruleset
     */
    public function unload()
    {
        global $ms;

        /* If authentication is enabled, check permissions */
        if ($ms->getOption("authentication") == "Y" &&
            !$ms->checkPermissions("user_load_rules")
        ) {

            $ms->printError(
                "<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - Unload rules",
                "You do not have enough permissions to access this module!"
            );
            return 0;

        }

        $retval = $ms->add_task('RULES_UNLOAD');

        return "Ruleset unload task submitted to job queue.";

    } // unload()

    public function smartyRulesetOutput($params, &$smarty)
    {
        $ruleset = new Ruleset;

        if ($ruleset->initRules()) {
            return $ruleset->showIt();
        }

    } // smartyRulesetOutput()
} // class Page_Ruleset

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
