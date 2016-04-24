<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
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

namespace Thallium\Views;

class SkeletonView extends DefaultView
{
    protected static $view_class_name = 'skeleton';

    /**
     * overwrite parent show() method as we do not have a lot
     * to do here.
     */
    public function show()
    {
        global $tmpl;

        if (!$tmpl->templateExists('skeleton.tpl')) {
            static::raiseError(__METHOD__ .'(), skeleton.tpl does not exist!');
            return false;
        }

        return $tmpl->fetch('skeleton.tpl');
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
