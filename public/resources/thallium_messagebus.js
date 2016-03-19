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

var ThalliumMessageBus = function (id) {
    this.element = id;
    this.messages = new Array;
    this.recvMessages = new Array;
    this.subscribers = new Object;
    this.ajaxRequests = new Array;
    this.pollerId;
    this.rpcEnabled = true;

    if (!(this.pollerId = setInterval("mbus.poll()", 1000))) {
        throw new Error('Failed to start ThalliumMessageBus.poll()!');
        return false;
    }

    $(document).on('Thallium:notifySubscribers', function (event) {
        this.notifySubscribers();
    }.bind(this));

    $(window).unload(function () {
        this.ajaxRequests.forEach(function (req) {
            if (typeof req.abort !== 'function') {
                return true;
            }
            req.abort();
            return true;
        });
        return true;
    }.bind(this));
    return true;
};

ThalliumMessageBus.prototype.add = function (message) {
    if (!message) {
        throw new Error('No message to add provided!');
        return false;
    }

    if (typeof(message) != 'object') {
        throw new Error('parameter is not an object!');
        return false;
    }

    this.messages.push(message);
    return true;
}

ThalliumMessageBus.prototype.fetchMessages = function () {
    var fetched_messages = new Array;
    var message;

    while ((message = this.messages.shift())) {
        fetched_messages.push(message);
    }

    return fetched_messages;
}

ThalliumMessageBus.prototype.getMessagesCount = function () {
    return this.messages.length;
}

ThalliumMessageBus.prototype.getReceivedMessages = function () {
    var _messages = new Array;
    var message;

    while (message = this.recvMessages.shift()) {
        _messages.push(message);
    }
    return _messages;
}

ThalliumMessageBus.prototype.getReceivedMessagesCount = function () {
    return this.recvMessages.length;
}

ThalliumMessageBus.prototype.send = function (messages) {
    // will not send an empty message
    if (!this.getMessagesCount()) {
        return true;
    }

    var messages, md;

    if (typeof (messages = this.fetchMessages()) === 'undefined') {
        throw new Error("fetchMessages() failed!");
        return false;
    }

    try {
        var json_str = JSON.stringify(messages);
    } catch (e) {
        throw new Error('Failed to convert messages to JSON string! '+ e);
        return false;
    }

    if (!(md = forge.md.sha1.create())) {
        throw new Error('Failed to initialize forge SHA1 message digest!');
        return false;
    }

    if (!md.update(json_str)) {
        throw new Error('forge SHA1 failed on json input!');
        return false;
    }

    var json = new Object;
    json.count = messages.length;
    json.size = json_str.length;
    json.hash = md.digest().toHex();
    json.json = json_str;

    try {
        var submitmsg = JSON.stringify(json);
    } catch (e) {
        throw new Error('Failed to convert messages to JSON string! '+ e);
        return false;
    }

    if (!submitmsg) {
        throw new Error('No message to send provided!');
        return false;
    }

    if (typeof(submitmsg) != 'string') {
        throw new Error('parameter is not a string!');
        return false;
    }

    $.ajax({
        context: this,
        global: false,
        type: 'POST',
        url: 'rpc.html',
        retries: 0,
        data: ({
            type : 'rpc',
            action : 'submit-messages',
            messages : submitmsg
        }),
        beforeSend: function (jqXHR) {
            this.ajaxRequests.push(jqXHR);
            return true;
        },
        complete: function (jqXHR) {
            var idx;
            if (!(idx = $.inArray(jqXHR, this.ajaxRequests))) {
                return true;
            }
            this.ajaxRequests.splice(idx, 1);
            return true;
        },
        error: function (jqXHR, textStatus, errorThrown) {
            var error_text = 'An error occured during AJAX operation.';
            if (textStatus == 'timeout' || (
                textStatus == 'error' &&
                (typeof errorThrown === 'undefined' || !errorThrown)
            )) {
                if (typeof jqXHR.retries === 'undefined') {
                    jqXHR.retries = 0;
                }
                jqXHR.retries++;
                if (jqXHR.retries <= 3) {
                    $.ajax(this);
                    return;
                }
            }
            error_text+= 'Retries: '+ jqXHR.retries +'.';
            if (typeof textStatus !== 'undefined' && textStatus) {
                error_text+= ' Type: ' + textStatus + '.';
            }
            if (typeof errorThrown !== 'undefined' && errorThrown) {
                error_text+= ' Message: ' + errorThrown + '.';
            }
            throw new Error(error_text);
        },
        success: function (data) {
            if (data != "ok") {
                throw new Error('Failed to submit messages! ' + data);
                return false;
            }
        }.bind(this)
    });

    return true;
}

ThalliumMessageBus.prototype.poll = function () {
    $.ajax({
        context: this,
        global: false,
        type: 'POST',
        url: 'rpc.html',
        retries: 0,
        data: ({
            type : 'rpc',
            action : 'retrieve-messages',
        }),
        beforeSend: function (jqXHR) {
            this.ajaxRequests.push(jqXHR);
            return true;
        },
        complete: function (jqXHR) {
            var idx;
            if (!(idx = $.inArray(jqXHR, this.ajaxRequests))) {
                return true;
            }
            this.ajaxRequests.splice(idx, 1);
            return true;
        },
        error: function (jqXHR, textStatus, errorThrown) {
            var error_text = 'An error occured during AJAX operation.';
            if (textStatus == 'timeout' || (
                textStatus == 'error' &&
                (typeof errorThrown === 'undefined' || !errorThrown)
            )) {
                if (typeof jqXHR.retries === 'undefined') {
                    jqXHR.retries = 0;
                }
                jqXHR.retries++;
                if (jqXHR.retries <= 3) {
                    $.ajax(this);
                    return;
                }
            }
            error_text+= 'Retries: '+ jqXHR.retries +'.';
            if (typeof textStatus !== 'undefined' && textStatus) {
                error_text+= ' Type: ' + textStatus + '.';
            }
            if (typeof errorThrown !== 'undefined' && errorThrown) {
                error_text+= ' Message: ' + errorThrown + '.';
            }
            throw new Error(error_text);
        },
        success: function (data) {
            this.parseResponse(data);
        }.bind(this)
    });

    return true;
}

ThalliumMessageBus.prototype.parseResponse = function (data) {
    var md;

    if (!data) {
        throw new Error('Requires data to be set!');
        return false;
    }

    try {
        var json = JSON.parse(data);
    } catch (e) {
        console.log(data);
        throw new Error('Failed to parse response! ' + e);
        return false;
    }

    if (
        typeof json.hash === 'undefined' ||
        typeof json.size === 'undefined' ||
        typeof json.json === 'undefined' ||
        typeof json.count === 'undefined'
    ) {
        throw new Error('Response is invalid!')
        return false;
    }

    if (json.json.length != json.size) {
        throw new Error('Response size does not match!');
        return false;
    }

    if (!(md = forge.md.sha1.create())) {
        throw new Error('Failed to initialize forge SHA1 message digest!');
        return false;
    }

    if (!md.update(json.json)) {
        throw new Error('forge SHA1 failed on json input!');
        return false;
    }

    if (json.hash != md.digest().toHex()) {
        throw new Error('Hash does not match!');
        return false;
    }

    // no messages included? then we are done.
    if (json.count == 0) {
        return true;
    }

    try {
        var messages = JSON.parse(json.json);
    } catch (e) {
        console.log(data);
        throw new Error('Failed to parse JSON field!' + e);
        return false;
    }

    if (messages.length != json.count) {
        throw new Error('Response meta data stat '+ json.count +' message(s) but only found '+ messages.length +'!');
        return false;
    }

    for (var message in messages) {
        this.recvMessages.push(messages[message]);
    }

    $(document).trigger("Thallium:notifySubscribers");
    return true;
};

ThalliumMessageBus.prototype.subscribe = function (name, category, handler, data) {
    if (!name) {
        throw new Error('No name provided!');
        return false;
    }

    if (!category) {
        throw new Error('No category provided!');
        return false;
    }

    if (!handler) {
        throw new Error('No handler provided!');
        return false;
    }

    if (typeof data === 'undefined') {
        data = null;
    }

    if (this.subscribers[name]) {
        throw new Error('A subscriber named '+ name +' has already been registered. It has been unsubscribed now!');
        this.unsubscribe(name);
    }

    this.subscribers[name] = new Object;
    this.subscribers[name].category = category;
    this.subscribers[name].handler = handler;
    this.subscribers[name].data = data;
    return true;
}

ThalliumMessageBus.prototype.unsubscribe = function (name) {
    if (!this.subscribers[name]) {
        return true;
    }

    delete this.subscribers[name];
    return true;
}

ThalliumMessageBus.prototype.getSubscribers = function (category) {
    if (!category) {
        return this.subscribers;
    }

    var subscribers = new Array;
    for (var subname in this.subscribers) {
        if (this.subscribers[subname].category != category) {
            continue;
        }
        subscribers.push(this.subscribers[subname]);
    }

    return subscribers;
}

ThalliumMessageBus.prototype.notifySubscribers = function () {
    var subscribers, messages, cnt;

    // if there are no messages pending, we do not bother our
    // subscribers.
    if (!(cnt = this.getReceivedMessagesCount())) {
        return true;
    }

    if (!(messages = this.getReceivedMessages())) {
        throw new Error('Failed to query received messages!');
        return false;
    }

    for (var msgid in messages) {
        if (!(subscribers = this.getSubscribers(messages[msgid].command))) {
            throw new Error('Failed to retrieve subscribers list!');
            return false;
        }

        for (var subid in subscribers) {
            if (!subscribers[subid].handler(messages[msgid], subscribers[subid].data)) {
                throw new Error('Subscriber "'+ subid +'" returned false!');
                return false;
            }
        }
    }
    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
