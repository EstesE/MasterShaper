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

namespace Thallium\Models ;

class AuditLogModel extends DefaultModel
{
    protected static $model_table_name = 'audit';
    protected static $model_column_prefix = 'audit';
    protected static $model_has_items = true;
    protected static $model_items_model = 'AuditEntryModel';
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
