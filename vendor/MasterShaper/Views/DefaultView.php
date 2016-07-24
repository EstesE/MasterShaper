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
    protected $sl_helper = array();

    public function __construct()
    {
        parent::__construct();

        global $tmpl;

        $tmpl->assign('view', $this);
        $tmpl->registerPlugin(
            "function",
            "form_buttons",
            array(&$this, "smartyFormButtons"),
            false
        );
        $tmpl->registerPlugin(
            "block",
            "select_list",
            array(&$this, "smartySelectList"),
            false
        );

        return;
    }

    public function hasOption($option)
    {
        global $ms;

        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$ms->hasOption($option)) {
            return false;
        }

        return true;
    }

    public function getOption($option)
    {
        global $ms;

        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            static::raiseError(__CLASS__ .'::hasOption() returned false!');
            return false;
        }

        if (($value = $ms->getOption($option)) === false) {
            static::raiseError(get_class($ms) .'::getOption() returned false!');
            return false;
        }

        return $value;
    }

    final public function smartyFormButtons($params, &$smarty)
    {
        global $db;

        if (array_key_exists('submit', $params) && $params['submit']) {
            $smarty->assign('submit', true);
        }
        if (array_key_exists('discard', $params) && $params['discard']) {
            $smarty->assign('discard', true);
        }
        if (array_key_exists('reset', $params) && $params['reset']) {
            $smarty->assign('reset', true);
        }

        return $smarty->fetch('form_buttons.tpl');
    }

    final public function smartySelectList($params, $content, &$smarty, &$repeat)
    {
        if (!array_key_exists('name', $params) ||
            !isset($params['name']) ||
            !is_string($params['name']) ||
            empty($params['name'])
        ) {
            static::raiseError(__METHOD__ .'(), $name parameter is invalid!', true);
            $repeat = false;
            return false;
        } else {
            $name = $params['name'];
        }

        if (!array_key_exists('what', $params) ||
            !isset($params['what']) ||
            !is_string($params['what']) ||
            empty($params['what'])
        ) {
            static::raiseError(__METHOD__ .'(), $what parameter is invalid!');
            $repeat = false;
            return false;
        } else {
            $what = $params['what'];
        }

        if (!array_key_exists('selected', $params)) {
            unset($selected);
        } elseif (array_key_exists('selected', $params) &&
            !empty($params['selected']) &&
            !is_string($params['selected']) &&
            !is_numeric($params['selected'])
        ) {
            static::raiseError(__METHOD__ .'(), $what parameter is invalid!');
            $repeat = false;
            return false;
        } else {
            $selected = $params['selected'];
        }

        if (array_key_exists('details', $params) &&
            isset($params['details']) &&
            is_string($params['details']) &&
            !empty($params['details'])
        ) {
            $details = $params['details'];
        } else {
            unset($details);
        }

        $template_index = sprintf('smarty.IB.%s.index', $name);

        if (($index = $smarty->getTemplateVars($template_index)) === false ||
            !isset($index) || !is_numeric($index)) {
            $index = 0;
        }

        if (!$this->hasHelperData($name) && !$this->getHelperData($name, $what)) {
            static::raiseError(__CLASS__ .'::getHelperData() returned false!');
            $repeat = false;
            return false;
        }

        if ($index < count($this->sl_helper[$name])) {
            $data = $this->sl_helper[$name][$index];
            $index++;
            $smarty->assign('selected', $selected);
            $smarty->assign('data', $data);
            $smarty->assign($template_index, $index);
            $repeat = true;
        } else {
            $repeat = false;
            $smarty->assign($template_index, 0);
        }

        return $content;
    }

    public function smartyServiceLevelSelectList($params, &$smarty)
    {
        $string = "";
        while ($row = $result->fetch()) {
            $string.= "<option value=\"". $row->sl_idx ."\"";

            if (isset($params['sl_idx']) && $row->sl_idx == $params['sl_idx']) {
                $string.= " selected=\"selected\"";
            }

            $string.= ">";

            if (isset($params['sl_default']) && $row->sl_idx == $params['sl_default']) {
                $string.= "*** ";
            }
            $string.= $row->sl_name;

            if ($params['details'] == 'yes') {
                switch ($ms->getOption("classifier")) {
                    case 'HTB':
                        $string.= "(in: ".
                            $row->sl_htb_bw_in_rate ."kbit/s, out: ".
                            $row->sl_htb_bw_out_rate ."kbit/s)";
                        break;
                    case 'HFSC':
                        $string.= "(in: ". $row->sl_hfsc_in_dmax .
                            "ms,". $row->sl_hfsc_in_rate ."kbit/s, out: ".
                            $row->sl_hfsc_out_dmax ."ms,".
                            $row->sl_hfsc_bw_out_rate ."kbit/s)";
                        break;
                }
            }

            if (isset($params['sl_default']) && $row->sl_idx == $params['sl_default']) {
                $string.= " ***";
            }

            $string.= "</option>\n";
        }

        return $string;
    }

    protected function hasHelperData($name)
    {
        if (!isset($name) || empty($name) || !is_string($name)) {
            static::raiseError(__METHOD__ .'(), $name parameter is invalid!');
            return false;
        }

        if (!array_key_exists($name, $this->sl_helper)) {
            return false;
        }

        return true;
    }

    protected function getHelperData($name, $what)
    {
        global $ms;

        if (!isset($name) || empty($name) || !is_string($name)) {
            static::raiseError(__METHOD__ .'(), $name parameter is invalid!');
            return false;
        }

        if (!isset($what) || empty($what) || !is_string($what)) {
            static::raiseError(__METHOD__ .'(), $what parameter is invalid!');
            return false;
        }

        if (!$ms->isValidModel($what)) {
            static::raiseError(get_class($ms) .'::isValidModel() returned false!');
            return false;
        }

        if (($full_what = $ms->getFullModelName($what)) === false) {
            static::raiseError(get_class($ms) .'::getFullModelName() returned false!');
            return false;
        }

        try {
            $helper = new $full_what;
        } catch (\Exception $e) {
            static::raiseError(sprintf('%s(), failed to load %s!', __METHOD__, $full_what), false, $e);
            return false;
        }

        if (!$helper->hasItems()) {
            $this->sl_helper[$name] = array();
        }

        if (($items = $helper->getItems()) === false) {
            static::raiseError(get_class($helper) .'::getItems() returned false!');
            return false;
        }

        $names = array_map(function ($item) {
            if (!$item->hasName()) {
                return $item->getIdx();
            }
            return $item->getName();
        }, $items);

        asort($names);

        $this->sl_helper[$name] = array();

        foreach (array_keys($names) as $idx) {
            array_push($this->sl_helper[$name], $items[$idx]);
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
