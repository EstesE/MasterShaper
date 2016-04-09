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

'use strict';

var ThalliumMessage = function () {
    return true;

    var command;
    var message;
};

ThalliumMessage.prototype.setCommand = function (command) {
    this.command = command;
    return true;
}

ThalliumMessage.prototype.hasCommand = function () {
    if (typeof this.command === 'undefined' || this.command === '') {
        return false;
    }
    return true;
}

ThalliumMessage.prototype.getCommand = function () {
    if (!this.hasCommand()) {
        throw new Error('ThalliumMessage.hasCommand() returned false!');
        return false;
    }
    return this.command;
}

ThalliumMessage.prototype.setMessage = function (message) {
    this.message = message;
    return true;
}

ThalliumMessage.prototype.hasMessage = function () {
    if (typeof this.message === 'undefined' || this.message === '') {
        return false;
    }
    return true;
}

ThalliumMessage.prototype.getMessage = function () {
    if (!this.hasMessage()) {
        throw new Error('ThalliumMessage.hasMessage() returned false!');
        return false;
    }
    return this.message;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
