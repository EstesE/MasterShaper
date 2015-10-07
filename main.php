<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ***************************************************************************/

require_once "static.php";

spl_autoload_register("autoload");

use MasterShaper\Controllers as Controllers;

$mode = null;

if (
    isset($_SERVER) &&
    isset($_SERVER['argv']) &&
    isset($_SERVER['argv'][1]) &&
    $_SERVER['argv'][1] == 'incoming'
) {
    $mode = 'queue_only';
}

try {
    $ms = new Controllers\MasterShaper();
} catch (Exception $e) {
    print $e->getMessage();
    exit(1);
}

if (!is_null($mode)) {
    exit(0);
}

if (!$ms->show()) {
    exit(1);
}

exit(0);

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
