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

var mbus;
var store;

$(document).ready(function () {
    try {
        mbus = new ThalliumMessageBus;
    } catch (e) {
        throw 'Failed to load ThalliumMessageBus! '+ e;
        return false;
    }

    try {
        store = new ThalliumStore;
    } catch (e) {
        throw 'Failed to load ThalliumStore! ' + e;
        return false;
    }

    /* RPC handlers */
    $("form.ui.form.add").on('submit', function () {
        rpc_object_update($(this), function (element, data) {
            if (data != "ok") {
                return true;
            }
            var savebutton = element.find('button.save');
            savebutton.transition('tada').removeClass('red shape');
            return true;
        });
        return false;
    });
    $('.inline.editable.edit.link').click(function () {
        var inlineobj = new ThalliumInlineEditable($(this));
        inlineobj.toggle();
    });
    /* RPC handlers */
    $("table tr td a.delete").click(function () {
        delete_object($(this));
    })
    $('.inline.editable.edit.link').click(function () {
        inlineobj = new ThalliumInlineEditable($(this));
        inlineobj.toggle();
    });
});

function show_modal(type, settings, id, do_function, modalclass)
{
    if (typeof type === 'undefined' || !type) {
        throw 'show_modal(), mandatory type parameter is missing!';
        return false;
    }

    if (type == 'progress') {
        var wnd = $('#progress_template').clone();
    } else if (type == 'confirm') {
        var wnd = $('#confirm_template').clone();
    } else {
        throw 'show_modal(), unsupported type!';
        return false;
    }

    if (typeof wnd === 'undefined' || !wnd) {
        throw 'show_modal(), unable to clone progress_template!';
        return false;
    }

    wnd.removeAttr('id');

    if (typeof id !== 'undefined' && id) {
        wnd.attr('id', id);
    }

    if (typeof settings === 'undefined') {
        var settings = {};
    }

    if (typeof settings.header !== 'undefined') {
        wnd.find('.header').html(settings.header);
    }
    if (typeof settings.icon !== 'undefined') {
        wnd.find('.image.content i.icon').removeClass().addClass(settings.icon);
    } else {
        settings.icon = 'icon';
    }

    if (typeof settings.iconHtml !== 'undefined') {
        wnd.find('.image.content i.' + settings.icon).html(settings.iconHtml);
    } else {
        wnd.find('.image.content i.' + settings.icon).html('');
    }

    if (typeof settings.content !== 'undefined') {
        wnd.find('.image.content .description p').html(settings.content);
    }

    if (typeof settings.closable === 'undefined') {
        settings.closable = true;
    }

    if (typeof settings.closable !== 'undefined' && !settings.closable) {
        wnd.find('i.close.icon').detach();
    } else {
        wnd.find('i.close.icon').appendTo(wnd);
    }

    if (typeof settings.hasActions === 'undefined') {
        settings.hasActions = true;
    }

    if (typeof settings.blurring === 'undefined') {
        settings.blurring = true;
    }

    if (typeof settings.hasActions === 'undefined') {
        wnd.find('.actions').detach();
    } else {
        wnd.find('.actions').appendTo(wnd);
    }

    if (typeof settings.onDeny === 'undefined') {
        settings.onDeny = function () {
            return true;
        };
    }

    if (typeof settings.onApprove === 'undefined') {
        settings.onApprove = function () {
            $(this).modal('hide');
            return true;
        };
    }

    if (typeof settings.onHidden === 'undefined') {
        settings.onHidden = function () {
            return true;
        };
    }

    if (typeof settings.detachable === 'undefined') {
        settings.detachable = true;
    }

    if (typeof settings.observeChanges === 'undefined') {
        settings.observeChanges = false;
    }

    if (typeof settings.allowMultiple === 'undefined') {
        settings.allowMultiple = false;
    }

    if (typeof do_function === 'undefined') {
        var do_function = function () {
            return true;
        };
    }

    var modal = wnd.modal({
        closable   : settings.closable,
        onDeny     : settings.onDeny,
        onApprove  : settings.onApprove,
        onHidden   : settings.onHidden,
        blurring   : settings.blurring,
        detachable : settings.detachable,
        observeChanges : settings.observeChanges,
        allowMultiple : settings.allowMultiple,
    })
    modal.modal('show').on('click.modal', do_function);

    return modal;
}

function safe_string(input)
{
    return input.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
}

function delete_object(element)
{
    var id = element.attr("data-id");

    if (typeof id === 'undefined' || id == "") {
        throw new Error('no attribute "data-id" found!');
        return;
    }

    id = safe_string(id);

    if (id == 'selected') {
        id = new Array;
        $('.checkbox.item.select[id!="select_all"]').each(function () {
            if (!($(this).checkbox('is checked'))) {
                return true;
            }
            var item = $(this).attr('id')
            if (typeof item === 'undefined' || !item || item == '') {
                return false;
            }
            item = item.match(/^select_(\d+)$/);
            if (typeof item === 'undefined' || !item || !item[1] || item[1] == '') {
                return false;
            }
            var item_id = item[1];
            id.push(item_id);
        });
        if (id.length == 0) {
            return true;
        }
    }

    var title = element.attr("data-modal-title");

    if (typeof title === 'undefined' || title === "") {
        throw 'No attribute "data-modal-title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    if (typeof text === 'undefined' || text === "") {
        if (id instanceof String && !id.match(/-all$/)) {
            text = "Do you really want to delete this item?";
        } else {
            text = "Do you really want to delete all items?";
        }
    }

    var elements = new Array;
    if (id instanceof Array) {
        id.forEach(function (value) {
            elements.push($('#delete_link_'+value));
        });
    } else {
        elements.push(element);
    }

    show_modal('confirm', {
        header : title,
        icon : 'red remove icon',
        content : text,
        onDeny : function () {
            return true;
        },
        onApprove : function () {
            $(this).modal('hide');
            return rpc_object_delete(elements, function () {
                if (typeof elements === 'undefined') {
                    return true;
                }
                if (typeof id !== 'undefined' && id == 'all') {
                    $('table#datatable tbody tr').each(function () {
                        $(this).hide(400, function () {
                            $(value).remove();
                        });
                    });
                    return true;
                }
                elements.forEach(function (value) {
                    $(value).closest('tr').hide(400, function () {
                        $(value).remove();
                    });
                });
                return true;
            });
        },
    });

    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
