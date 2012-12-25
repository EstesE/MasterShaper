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

require_once "shaper.class.php";

$ms = new MASTERSHAPER;

if(isset($_SERVER['argv']) && isset($_SERVER['argv'][1])) {
   switch($_SERVER['argv'][1]) {
      case 'load': $ms->load(); break;
      case 'unload': $ms->unload(); break;
   }
}

// lookup user/group names to their  IDs
if(($user_info = posix_getpwnam(RUNAS_USER)) === false) {
   $ms->throwError("Failed to lookup user ". RUNAS_USER);
}
if(($group_info = posix_getgrnam(RUNAS_GROUP)) == false) {
   $ms->throwError("Failed to lookup group ". RUNAS_GROUP);
}

// Setup
$options = array(
    'appName' => 'shaper_agent',
    'appDir' => dirname(__FILE__),
    'appRunAsGID' => $user_info['uid'],
    'appRunAsUID' => $group_info['gid'],
    //'logLocation' => '/tmp/shaper_agent.log',
    //'appPidLocation' => '/tmp/shaper_agent/shaper_agent.pid',
    //'logVerbosity' => 7,
);

// disconnect parent processes database connection
global $db;
unset($db);

System_Daemon::setOptions($options);
System_Daemon::start();

// enable gargabe collector
gc_enable();

// spawn task manager
$taskmgr_pid = $ms->init_task_manager();
// spawn statistics collector
$collect_pid = $ms->init_stats_collector();

// wait for any kill signal
while(1) {

   if(System_Daemon::isDying()) {
      pcntl_wait($taskmgr_pid);
      pcntl_wait($collect_pid);
      exit(0);
   }

   // sleep a second
   System_Daemon::iterate(1);
}

unset($db);

?>
