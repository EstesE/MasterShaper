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

namespace MasterShaper\Controllers;

use MasterShaper\Views;

class ViewsController extends DefaultController
{
    private $page_map = array(
            '/^$/' => 'MainView',
            '/^main$/' => 'MainView',
            '/^queue$/' => 'QueueView',
            '/^archive$/' => 'ArchiveView',
            '/^upload$/' => 'UploadView',
            '/^keywords$/' => 'KeywordsView',
            '/^about$/' => 'AboutView',
            '/^options$/' => 'OptionsView',
            );
    private $page_skeleton;

    public function __construct()
    {
        $this->page_skeleton = new Views\SkeletonView;
    }

    /**
     * return requested page
     *
     * @param string
     */
    public function getPageUrl($page_name, $id = null)
    {
        global $db;

        $sth = $db->db_prepare("
                SELECT
                page_uri
                FROM
                ". MYSQL_PREFIX ."pages
                WHERE
                page_name LIKE ?
                ");

        $db->db_execute($sth, array(
                    $page_name,
                    ));

        if ($sth->rowCount() <= 0) {
            $db->db_sth_free($sth);
            return false;
        }

        if (($row = $sth->fetch()) === false) {
            $db->db_sth_free($sth);
            return false;
        }

        if (!isset($row->page_uri)) {
            $db->db_sth_free($sth);
            return false;
        }

        if (isset($id) && !empty($id)) {
            $row->page_uri = str_replace("[id]", (int) $id, $row->page_uri);
        }

        $db->db_sth_free($sth);
        return WEB_PATH ."/". $row->page_uri;

    }

    public function getViewName($view)
    {
        foreach (array_keys($this->page_map) as $entry) {

            if (($result = preg_match($entry, $view)) === false) {
                print "Error - unable to match ${entry} in ${view}";
                exit(1);
            }

            if ($result == 0) {
                continue;
            }

            if (!class_exists('MasterShaper\\Views\\'.$this->page_map[$entry])) {
                print "Error - view class ". $this->page_map[$entry] ." does not exist!";
                exit(1);
            }

            return $this->page_map[$entry];

        }
    }

    public function load($view, $skeleton = true)
    {
        global $ms;

        $view = 'MasterShaper\\Views\\'.$view;

        try {
            $page = new $view;
        } catch (Exception $e) {
            $ms->raiseError("Failed to load view {$view}!");
            return false;
        }

        if (!$skeleton) {
            return $page->show();
        }

        if (!($content = $page->show())) {
            return false;
        }

        // if $content=true, View has handled output already, we are done
        if ($content === true) {
            return true;
        }

        $this->page_skeleton->assign('page_content', $content);

        return $this->page_skeleton->show();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
