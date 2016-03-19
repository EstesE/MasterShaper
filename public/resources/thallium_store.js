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

var ThalliumStore = function () {
    this._store = new Object;
    this._substores = new Object;
    this._substore_lookup = new Object;

    this._identifier;
    this._uuid = this.generateUUID();
    if (typeof this._uuid === 'undefined' || this._uuid == '') {
        throw new Error('ThalliumStore, failed to generate a UUID!');
        return false;
    }
    return true;
};

ThalliumStore.prototype.set = function (key, value, store) {
    if (typeof key === 'undefined' || typeof value === 'undefined' || key === '') {
        throw new Error('set(), key parameter is invalid!');
        return false;
    }
    if (typeof store === 'undefined' || store == '') {
        this._store[key] = value;
        return this.get(key);
    }

    var substore;
    if (!(substore = this.getSubStore(store))) {
        throw new Error('set(), getSubStore() returned false!');
        return false;
    }

    return substore.get(key);
}

ThalliumStore.prototype.get = function (key, store) {
    if (typeof key === 'undefined' || key === '') {
        throw new Error('get(), key parameter is invalid!');
        return false;
    }
    if (!this.has(key)) {
        throw new Error('get(), '+ key +' value not set!');
        return false;
    }
    return this._store[key];
}

ThalliumStore.prototype.has = function (key) {
    if (typeof key === 'undefined' || key === '') {
        throw new Error('has(), key parameter is invalid!');
        return false;
    }
    if (typeof this._store[key] === 'undefined') {
        return false;
    }
    return true;
}

ThalliumStore.prototype.del = function (key) {
    if (typeof key === 'undefined' || key === '') {
        throw new Error('del(), key parameter is invalid!');
        return false;
    }
    if (!this.has(key)) {
        throw new Error('del(), no such value named ' + key + '!');
        return false;
    }
    delete this._store[key];
    return true;
}

ThalliumStore.prototype.createSubStore = function (key) {
    var substore = new ThalliumStore;
    if (typeof substore === 'undefined' || ! substore) {
        throw new Error('createSubStore(), failed to initalize ThalliumStore!');
        return false;
    }
    if (typeof key === 'undefined') {
        throw new Error('createSubStore(), key parameter is mandatory!');
        return false;
    }
    if (!substore.setIdentifier(key)) {
        throw new Error('createSubStore(), failed to set identifier!');
        return false;
    }
    var uuid;
    if (!(uuid = substore.getUUID())) {
        throw new Error('createSubStore(), failed to fetch substore\'s UUID!');
        return false;
    }
    if (typeof this._substores[uuid] !== 'undefined') {
        throw new Error('createSubStore(), a substore with the same UUID is already set!');
        return false;
    }
    this._substores[uuid] = substore;
    this._substore_lookup[key] = uuid;
    return this._substores[uuid];
}

ThalliumStore.prototype.getSubStore = function (key) {
    if (typeof key === 'undefined' || key === '') {
        throw new Error('getSubStore(), key parameter is invalid!');
        return false;
    }
    if (typeof this._substores[key] !== 'undefined') {
        return this._substores[key];
    }
    if (typeof this._substore_lookup[key] === 'undefined') {
        throw new Error('getSubStore(), no such identifier known!');
        return false;
    }
    var uuid = this._substore_lookup[key];
    if (typeof this._substores[uuid] === 'undefined') {
        throw new Error('getSubStore(), no such substore!');
        return false;
    }
    return this._substores[uuid];
}

ThalliumStore.prototype.removeSubStore = function (key) {
    if (typeof key === 'undefined' || key === '') {
        throw new Error('getSubStore(), key parameter is invalid!');
        return false;
    }
    if (typeof this._substores[key] !== 'undefined') {
        delete this._substores[key];
        return true;
    }
    if (typeof this._substore_lookup[key] === 'undefined') {
        throw new Error('getSubStore(), no such identifier known!');
        return false;
    }
    var uuid = this._substore_lookup[key];
    if (typeof this._substores[uuid] === 'undefined') {
        throw new Error('getSubStore(), no such substore!');
        return false;
    }
    delete this._substores[uuid];
    return true;
}

ThalliumStore.prototype.generateUUID = function () {
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = (d + Math.random()*16)%16 | 0;
        d = Math.floor(d/16);
        return (c=='x' ? r : (r&0x3|0x8)).toString(16);
    });
    return uuid;
};

ThalliumStore.prototype.getUUID = function () {
    if (typeof this._uuid === 'undefined' || this._uuid == '') {
        throw new Error('getUUID(), no UUID available!');
        return false;
    }
    return this._uuid;
}

ThalliumStore.prototype.setIdentifier = function (id) {
    if (typeof id === 'undefined' || id == '') {
        throw new Error('setIdentifier(), id parameter is invalid!');
        return false;
    }
    this._identifier = id;
    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
