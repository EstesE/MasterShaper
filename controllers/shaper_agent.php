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

if(!isset($_SERVER['argc']) || $_SERVER['argc'] == 0)
  die("This script should only be executed from command line");

require_once "shaper.class.php";

$ms = new MASTERSHAPER;

// just to be sure
if(!$ms->is_cmdline())
  die("This script should only be executed from command line");

$run_daemonized = 1;
$run_taskmgr = 1;
$run_stats = 1;

$options = getopt("ftshv:", array(
   "foreground",
   "taskmgr-only",
   "stats-only",
   "help",
   "verbose:",
   "load",
   "unload",
));

foreach($options as $option => $value) {

   switch($option) {

      case 'load':
         case 'load':
            $retval = $ms->load();
            exit($retval);
            break;
         case 'unload':
            $retval = $ms->unload();
            exit($retval);
            break;
         case 'f':
         case 'foreground':
            $run_daemonized = 0;
            break;
         case 't':
         case 'taskmgr-only':
            $run_taskmgr = 1;
            $run_stats = 0;
            break;
         case 's':
         case 'stats-only':
            $run_stats = 1;
            $run_taskmgr = 0;
            break;
         case 'v':
         case 'verbose':
            if(!is_int($value) || $value < 1 and $value > 3) {
               show_help();
               exit(1);
            }
            $ms->set_verbosity($value);
            break;
         case 'h':
         case 'help':
         case 'default':
            show_help();
            exit(0);
            break;
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
    'appRunAsUID' => $user_info['uid'],
    'appRunAsGID' => $group_info['gid'],
    //'logLocation' => '/tmp/shaper_agent.log',
    //'appPidLocation' => '/tmp/shaper_agent/shaper_agent.pid',
    //'logVerbosity' => 7,
);

// disconnect parent processes database connection
global $db;
unset($db);

System_Daemon::setOptions($options);

if($run_daemonized == 1)
   System_Daemon::start();

// enable gargabe collector
if(function_exists("gc_enable"))
   gc_enable();

// spawn task manager
if($run_taskmgr == 1)
   $taskmgr_pid = $ms->init_task_manager();

// spawn statistics collector
if($run_stats == 1)
   $collect_pid = $ms->init_stats_collector();

// wait for any kill signal
while(1) {

   if(System_Daemon::isDying()) {
      if($run_taskmgr == 1)
         pcntl_wait($taskmgr_pid);
      if($run_stats == 1)
         pcntl_wait($collect_pid);
      exit(0);
   }

   // sleep a second
   System_Daemon::iterate(1);
}

unset($db);

function show_help()
{
   print "
shaper_agent.php - MasterShaper Agent
(c) Andreas Unterkircher <unki@netshadow.at>
http://www.mastershaper.org

./shaper_agent.php <options>

 -f   --foreground   ... do not fork into background
 -t   --taskmgr-only ... start task-manager only (load and unload rules)
 -s   --stats-only   ... start statistics collector only
 -h   --help         ... this help text
 -vx  --verbose=x    ... verbose level (1 info, 2 warn, 3 debug)

";
}

?>
