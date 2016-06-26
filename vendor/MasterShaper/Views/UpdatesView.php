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

class UpdateIanaView extends DefaultView
{
    protected static $view_default_mode = 'show';
    protected static $view_class_name = 'update_iana';

    /**
     * Page_Update_IANA constructor
     *
     * Initialize the Page_Update_IANA class
     */
    public function __construct()
    {
        $this->page = 'user_manage_rights';

    } // __construct()

    /* interface output */
    public function showList()
    {
        if ($this->is_storing()) {
            $this->store();
        }

        global $ms, $db, $tmpl;

        return $tmpl->fetch("update-iana.tpl");

    } // show()

    public function store()
    {
        global $ms, $db;
        $protocols = array();
        $ports = array();

        $db->query("TRUNCATE TABLE shaper2_protocols");
        $db->query("TRUNCATE TABLE shaper2_ports");

        /**
         * Update Protocols
         */

        if (!file_exists(MASTERSHAPER_BASE ."/contrib/protocol-numbers.xml")) {
            static::raiseError(
                "Can not locate protocol-numbers.xml file at: ". MASTERSHAPER_BASE ."/contrib/protocol-numbers.xml"
            );
            return false;
        }

        if (!is_readable(MASTERSHAPER_BASE ."/contrib/protocol-numbers.xml")) {
            static::raiseError(
                "Can not read protocol-numbers.xml file at: ". MASTERSHAPER_BASE ."/contrib/protocol-numbers.xml"
            );
            return false;
        }

        $xml = simplexml_load_file(MASTERSHAPER_BASE ."/contrib/protocol-numbers.xml");

        $xml_reg = $xml->registry;

        foreach ($xml_reg->record as $xml_rec) {
            if (!isset($xml_rec->name) || !is_string((string)$xml_rec->name)) {
                continue;
            }
            if (!isset($xml_rec->value) || !is_numeric((int)$xml_rec->value)) {
                continue;
            }
            if (!isset($xml_rc->description) || !is_string((string)$xml_rec->description)) {
                $xml_rec->description = "";
            }

            array_push(
                $protocols,
                array(
                    $xml_rec->name,
                    $xml_rec->description,
                    $xml_rec->value,
                )
            );
        }

        $sth = $db->prepare("
                INSERT IGNORE INTO TABLEPREFIXprotocols (
                    proto_name,
                    proto_desc,
                    proto_number
                    ) VALUES (
                        ?, ?, ?
                        )
                ");

        foreach ($protocols as $proto) {
            $db->execute($sth, array(
                        $proto[0],
                        $proto[1],
                        $proto[2],
                        ));
        }

        $db->db_sth_free($sth);

        /**
         * Update Ports
         */

        if (!file_exists(MASTERSHAPER_BASE ."/contrib/service-names-port-numbers.xml")) {
            static::raiseError(
                "Can not locate protocol-numbers.xml file at: ". MASTERSHAPER_BASE
                ."/contrib/service-names-port-numbers.xml"
            );
            return false;
        }

        if (!is_readable(MASTERSHAPER_BASE ."/contrib/service-names-port-numbers.xml")) {
            static::raiseError(
                "Can not read protocol-numbers.xml file at: ". MASTERSHAPER_BASE
                ."/contrib/service-names-port-numbers.xml"
            );
            return false;
        }

        $xml = simplexml_load_file(MASTERSHAPER_BASE ."/contrib/service-names-port-numbers.xml");

        foreach ($xml->record as $xml_rec) {
            if (!isset($xml_rec->name) || !is_string((string)$xml_rec->name)) {
                continue;
            }
            if (!isset($xml_rec->number) || !is_numeric((int)$xml_rec->number)) {
                continue;
            }
            if (!isset($xml_rc->description) || !is_string((string)$xml_rec->description)) {
                $xml_rec->description = "";
            }

            array_push($ports, array(
                        $xml_rec->name,
                        $xml_rec->description,
                        $xml_rec->number,
                        ));
        }

        $sth = $db->prepare("
                INSERT IGNORE INTO TABLEPREFIXports (
                    port_name,
                    port_desc,
                    port_number
                    ) VALUES (
                        ?, ?, ?
                        )
                ");

        foreach ($ports as $port) {
            $db->execute($sth, array(
                        $port[0],
                        $port[1],
                        $port[2]
                        ));
        }

        $db->db_sth_free($sth);

        printf("Looks like this was successful!<br />\n");

        return;

    } // store()
} // class Page_Update_IANA

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
