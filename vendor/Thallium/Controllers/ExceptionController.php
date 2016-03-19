<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015> <Andreas Unterkircher>
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

namespace Thallium\Controllers;

class ExceptionController extends \Exception
{
    protected $previous;

    public function __construct($message, $captured_exception = null)
    {
        parent::__construct($message, null, $captured_exception);
    }

    public function getText()
    {
        $text = "";
        if ($previous = $this->getPrevious()) {
            $text.= "<br /><br />". str_replace("\n", "<br />\n", $previous->getMessage()) ."<br /><br />\n";
            $text.= "Backtrace:<br />\n". str_replace("\n", "<br />\n", $previous->getTraceAsString());
            return $text;
        }

        $text.= "<br /><br />". str_replace("\n", "<br />\n", $this->getMessage()) ."<br /><br />\n";
        $text.= "Backtrace:<br />\n". str_replace("\n", "<br />\n", parent::getTraceAsString());
        return $text;
    }

    public function getJson()
    {
        $text = array();

        if ($previous = $this->getPrevious()) {
            $text = $previous->getMessage();
            $trace = $previous->getTraceAsString();
        } else {
            $text = $this->getMessage();
            $trace = parent::getTraceAsString();
        }

        if (($json = json_encode(array('error' => 1, 'text' => $text, 'trace' => $trace))) === false) {
            exit("json_encode() failed!");
        }

        return $json;
    }

    public function __toString()
    {
        global $thallium, $router;

        if ((isset($router) && $router->isRpcCall()) &&
            (!isset($thallium->backgroundJobsRunning) || empty($thallium->backgroundJobsRunning))
        ) {
            return $this->getJson();
        }

        return $this->getText();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
