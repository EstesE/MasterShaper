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

header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'; script-src 'self';"); // FF 23+ Chrome 25+ Safari 7+ Opera 19+
header("X-Content-Security-Policy: default-src 'self'; script-src 'self';"); // IE 10+
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

require_once '../main.php';

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
