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

namespace Thallium\Controllers;

class AuditController extends DefaultController
{
    public function log($message, $entry_type, $scene, $guid = null)
    {
        try {
            $entry = new \Thallium\Models\AuditEntryModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load AuditEntryModel', false, $e);
            return false;
        }

        if (!$entry->setMessage($message)) {
            static::raiseError("AuditEntryModel::setMessage() returned false!");
            return false;
        }

        if (!empty($guid) && !$entry->setEntryGuid($guid)) {
            static::raiseError("AuditEntryModel::setEntryGuid() returned false!");
            return false;
        }

        if (!$entry->setEntryType($entry_type)) {
            static::raiseError("AuditEntryModel::setEntryType() returned false!");
            return false;
        }

        if (!$entry->setScene($scene)) {
            static::raiseError("AuditEntryModel::setScene() returned false!");
            return false;
        }

        if (!$entry->save()) {
            static::raiseError("AuditEntryModel::save() returned false!");
            return false;
        }

        return true;
    }

    public function retrieveAuditLog($guid)
    {
        global $thallium;

        if (empty($guid) || !$thallium->isValidGuidSyntax($guid)) {
            static::raiseError(__METHOD__ .' requires a valid GUID as first parameter!');
            return false;
        }

        try {
            $log = new \Thallium\Models\AuditLogModel(array(
                FIELD_GUID => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load AuditLogModel!', false, $e);
            return false;
        }

        if (!$log->hasItems()) {
            return 'No audit log entries available!';
        }

        if (($entries = $log->getItems()) === false) {
            static::raiseError(get_class($log) .'::getItemsData() returned false!');
            return false;
        }

        if (!is_array($entries)) {
            static::raiseError(get_class($log) .'::getItemsData() returned invalid data!');
            return false;
        }

        if (empty($entries)) {
            return 'No audit log entries available!';
        }

        $txt_ary = array();

        foreach ($entries as $entry) {
            if (!$entry->hasMessage()) {
                continue;
            }
            if (($message = $entry->getMessage()) === false) {
                static::raiseError(get_class($entry) .'::getMessage() returned false!');
                return false;
            }
            $txt_ary[] = $message;
        }

        if (empty($txt_ary)) {
            return 'No audit log entries available!';
        }

        $txt_log = implode('\n', $txt_ary);
        return $txt_log;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
