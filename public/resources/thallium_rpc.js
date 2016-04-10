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

function rpc_object_delete(elements, successMethod)
{
    if (typeof elements === 'undefined') {
        throw new Error('elements parameter is not defined!');
        return false;
    }
    if (!(elements instanceof Array)) {
        throw new Error('elements is not an Array!');
        return false;
    }

    var ids = new Array;
    var guids = new Array;
    var models = new Array;
    var titles = new Array;
    var substore;

    elements.forEach(function (element) {
        var id, guid, model, title;
        if (!(element instanceof jQuery) ) {
            throw new Error("element is not a jQuery object!");
            return false;
        }

        if (!(id = element.attr('data-id'))) {
            throw new Error('no attribute "data-id" found!');
            return false;
        }

        ids.push(id);

        if (!(guid = element.attr('data-guid'))) {
            throw new Error('no attribute "data-guid" found!');
            return false;
        }

        guids[id] = guid;

        if (!(model = element.attr('data-model'))) {
            throw new Error('no attribute "data-model" found!');
            return false;
        }

        models[id] = model;

        if (!(title = element.attr('data-action-title'))) {
            throw new Error('no attribute "data-action-title" found!');
            return false;
        }

        titles[id] = title;
    });

    if (!(substore = store.createSubStore('delete'))) {
        throw new Error('failed to allocate a ThalliumStore for this action!');
        return false;
    }

    var del_wnd = substore.set('progresswnd', show_modal('progress', {
        header : 'Deleting...',
        icon : 'remove icon',
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    }));

    var progressbar = substore.set('progressbar', del_wnd.find('.description .ui.indicating.progress'));

    if (!progressbar) {
        throw new Error('Can not find the progress bar in the modal window!');
        return false;
    }

    ids.forEach(function (id) {
        var msg_body = new Object;
        msg_body.id = safe_string(id);
        msg_body.guid = safe_string(guids[id]);
        msg_body.model = safe_string(models[id]);

        var msg = new ThalliumMessage;
        msg.setCommand('delete-request');
        msg.setMessage(msg_body);
        if (!mbus.add(msg)) {
            throw new Error('ThalliumMessageBus.add() returned false!');
            return false;
        }
    });

    mbus.subscribe('delete-replies-handler', 'delete-reply', function (reply, substore) {
        var newData, value, del_wnd, progressbar;

        if (typeof reply === 'undefined' || !reply) {
            throw new Error('reply is empty!');
            return false;
        }
        if (typeof substore === 'undefined' || !substore) {
            throw new Error('substore is not provided!');
            return false;
        }

        if (!(del_wnd = substore.get('progresswnd'))) {
            throw new Error('Have no reference to the modal window!');
            return false;
        }
        if (!(progressbar = substore.get('progressbar'))) {
            throw new Error('Have no reference to the progressbar!');
            return false;
        }

        newData = new Object;

        if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
            newData.percent = value[1];
        }
        if (reply.body) {
            newData.text = {
                active : reply.body,
                success: reply.body
            };
        }
        if (!progressbar.hasClass('active')) {
            progressbar.addClass('active');
        }

        progressbar.progress(newData);
        del_wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        del_wnd.modal('hide');
        mbus.unsubscribe('delete-replies-handler');

        store.removeSubStore(substore.getUUID());

        if (typeof successMethod !== 'undefined') {
            return successMethod();
        }

        location.reload();
        return true;

    }.bind(this), substore);

    if (!mbus.send()) {
        throw new Error('ThalliumMessageBus.send() returned false!');
        return false;
    }

    return true;
}

function rpc_object_update(element, successMethod, customData)
{
    var target, input, action, model, key, id, value, url, data;

    if (!(element instanceof jQuery) ) {
        throw new Error("element is not a jQuery object!");
        return false;
    }

    target = element.attr('data-target');

    if (typeof target === 'undefined' || target == '') {
        throw new Error('no attribute "data-target" found!');
        return false;
    }

    if (!(input = element.find('input[name="'+target+'"], textarea[name="'+target+'"]'))) {
        throw new Error("Failed to get input element!");
        return false;
    }

    if (!(action = input.attr('data-action'))) {
        throw new Error("Unable to locate 'data-action' attribute!");
        return false;
    }

    if (!(model = input.attr('data-model'))) {
        throw new Error("Unable to locate 'data-model' attribute!");
        return false;
    }

    if (!(key = input.attr('data-key'))) {
        throw new Error("Unable to locate 'data-key' attribute!");
        return false;
    }

    if (!(id = input.attr('data-id'))) {
        throw new Error("Unable to locate 'data-id' attribute!");
        return false;
    }

    if (input.attr('type') === 'checkbox') {
        if (input.prop('checked')) {
            if ((value = input.attr('data-checked')) === undefined) {
                value = 'Y';
            }
        } else {
            if ((value = input.attr('data-unchecked')) === undefined) {
                value = 'N';
            }
        }
    } else {
        if (typeof (value = input.val()) === 'undefined') {
            return false;
        }
    }

    action = safe_string(action);
    model = safe_string(model);
    key = safe_string(key);
    id = safe_string(id);
    value = safe_string(value);

    if (typeof window.location.pathname !== 'undefined' &&
        window.location.pathname != '' &&
        !window.location.pathname.match(/\/$/)
    ) {
        url = window.location.pathname;
    } else {
        url = 'rpc.html';
    }

    data = ({
        type   : 'rpc',
        action : action,
        model  : model,
        id     : id,
        key    : key,
        value  : value
    });

    if (typeof customData !== 'undefined') {
        data.customData = customData;
    }

    $.ajax({
        type: 'POST',
        url: url,
        retries: 0,
        data: data,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus == 'timeout') {
                this.retries++;
                if (this.retries <= 3) {
                    $.ajax(this);
                    return;
                }
            }
            throw new Error('Failed to contact server! ' + textStatus);
        },
        success: function (data) {
            if (data != 'ok') {
                throw new Error('Server returned: ' + data + ', length ' + data.length);
                return;
            }
            if (action == 'add') {
                location.reload();
                return;
            }
            if (typeof successMethod === 'undefined') {
                return;
            }
            successMethod(element, data);
            return;
        }
    });

    return true;
}

function rpc_fetch_jobstatus()
{
    if (!mbus.poll()) {
        throw new Error('MessageBus.poll() returned false!');
        return false;
    }
}

function rpc_object_delete2(element)
{
    if (!(element instanceof jQuery) ) {
        throw new Error("element is not a jQuery object!");
        return false;
    }

    if (!(id = element.attr('data-id'))) {
        throw new Error('no attribute "data-id" found!');
        return false;
    }

    if (!(guid = element.attr('data-guid'))) {
        throw new Error('no attribute "data-guid" found!');
        return false;
    }

    id = safe_string(id);
    guid = safe_string(guid);

    if (typeof window.location.pathname !== 'undefined' &&
        window.location.pathname != '' &&
        !window.location.pathname.match(/\/$/)
    ) {
        url = window.location.pathname;
    } else {
        url = 'rpc.html';
    }

    $.ajax({
        type: 'POST',
        url: url,
        data: ({
            type   : 'rpc',
            action : 'delete-document',
            id     : id,
            guid   : guid
        }),
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            throw new Error('Failed to contact server! ' + textStatus);
        },
        success: function (data) {
            if (data != 'ok') {
                throw new Error('Server returned: ' + data + ', length ' + data.length);
                return;
            }
            location.reload();
            return;
        }
    });

    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
