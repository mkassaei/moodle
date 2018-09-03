// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * JavaScript to allow dragging options to slots (using mouse down or touch) or tab through slots using keyboard.
 *
 * @package    qtype_ddimageortext
 * @subpackage form
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'qtype_ddimageortext/ddutil'], function ($, u) {

    "use strict";

    var t = {
        pendingid: null,
        params: null,
        topNode: null,
        backgroundImage: null,
        dragItemsArea: null,
        dragItemHomes: null,
        maxSizes:null,
        afterimageloaddone: false,
        fp: null, // Object containing functions associated with the file picker.
        pollTimer: null, // Timer reference.
        imageReady: false, // The background image is loaded.
        bgImg: null, // Reference to the jQuery background image object.

        /**
         * Initialise the form javascript features.
         * @param {Object} config Array of topnode and maxsizes
         */
        init: function(params) {
            t.pendingid = 'qtype_ddimageortext-form-' + Math.random().toString(36).slice(2); // Random string.
            M.util.js_pending(t.pendingid);
            t.params = params;
            t.bgImg = t.params.maxsizes.bgimage;
            t.maxSizes = t.params.maxsizes;
            t.fp = t.filePickers();
            t.topNode = $(t.params.topnode);
            t.dragItemsArea = $(t.topNode).find('div.dragitems');
            t.dragItemHomes = $(t.topNode).find('div.dragitems .draghome');

            //u.formInit(t.topNode);
            //var dragItems = $('fieldset#id_draggableitemheader').find('div.fcontainer').children();
            //console.log($(dragItems));
            //$('div.droparea .dragitems').append(dragItems);
            // u.setDrags(t.topNode);
            // u.setDrops(t.params);

            t.setupPreviewArea(t.topNode);
            u.update_padding_sizes_all(t.topNode);
            u.setOptionsForDragItemSelectors();
            //u.setupFormEvents(t.topNode);
            u.setup_form_events(t.topNode);
            u.whenImageReady(t.topNode, t.maxSizes);
            u.loadDragHomes(t.topNode);
            u.after_all_images_loaded(t.topNode);
            //u.create_all_drag_and_drops(t.topNode);
            t.update_visibility_of_file_pickers();
            //t.draw_dd_area();
            //console.log(u.initDrags(t.topNode));
            //console.log('u.initDrags(t.topNode)');
        },
        update_visibility_of_file_pickers: function() {
            for (var i = 0; i < t.form.getFormValue('noitems', []); i++) {
                if ('image' === t.form.getFormValue('drags', [i, 'dragitemtype'])) {
                    $('input#id_dragitem_' + i).parent().parent().css('display', 'block');
                } else {
                    $('input#id_dragitem_' + i).parent().parent().css('display', 'none');
                }
            }
        },
        constrain_image_size: function(e, imagetype) {
            var reduceby = Math.max(e.target.width / 't.params.maxsizes.' + imagetype + '.width',
                e.target.height / 't.params.maxsizes.' + imagetype + '.height');
            if (reduceby > 1) {
                e.target.css('width', Math.floor(e.target.width / reduceby));
            }
            $(e.target).addClass('constrained');
            //$(e.target).detach('load', t.constrain_image_size(e, imagetype));
        },


        set_options_for_drag_item_selectors: function() {
            var dragitemsoptions = {'0': ''};
            for (var i = 0; i < t.form.getFormValue('noitems', []); i++) {
                var label = t.form.getFormValue('draglabel', [i]);
                var file = t.fp.file(t.form.toNameWithIndex('dragitem', [i]));
                if ('image' === t.form.getFormValue('drags', [i, 'dragitemtype']) && file.name !== null) {
                    dragitemsoptions[i + 1] = (i + 1) + '. ' + label + ' (' + file.name + ')';
                } else if (label !== '') {
                    dragitemsoptions[i + 1] = (i + 1) + '. ' + label;
                }
            }
            for (i = 0; i < t.form.getFormValue('nodropzone', []); i++) {
                var selector = $('#id_drops_' + i + '_choice');
                console.log(dragitemsoptions);
                console.log(selector);
                console.log('selector ------------------');

                //var selector = $('fieldset.felement.fgroup' + ' #id_drops_' + i + '_choice');
                var selectedvalue = selector.val();
                selector.find('option').remove();
                for (var value in dragitemsoptions) {
                    value = +value;
                    var option = '<option value="' + value + '">' + dragitemsoptions[value] + '</option>';
                    selector.append(option);
                    var optionnode = selector.find('option[value="' + value + '"]');
                    if (value === +selectedvalue) {
                        optionnode.attr('selected', true);
                    } else {
                        if (value !== 0) { // No item option is always selectable.
                            var cbel = $('#id_drags_' + (value - 1) + '_infinite');
                            if (cbel && !cbel.get('checked')) {
                                if (t.item_is_allocated_to_dropzone(value)) {
                                    optionnode.attr('disabled', true);
                                }
                            }
                        }
                    }
                }
            }
        },

        stop_selector_events: function() {
            //$('fieldset#id_dropzoneheader select').detachAll();
            $('fieldset#id_dropzoneheader select').detach();
        },

        /**
         * Checks if the specified drag item is allocated to a dropzone.
         *
         * @method item_is_allocated_to_dropzone
         * @param {Number} value of the drag item to check
         * @return {Boolean} true if item is allocated to dropzone
         */
        item_is_allocated_to_dropzone: function(itemvalue) {
            return $('fieldset#id_dropzoneheader select').find(function(selectNode) {
                return Number($(selectNode).val()) === itemvalue;
            });
        },

        setup_form_events: function() {
            // Events triggered by changes to form data.

            // X and y coordinates.
            $('fieldset#id_dropzoneheader input').on('blur', function(e) {
                var name = e.target.attr('name');
                var draginstanceno = t.form.fromNameWithIndex(name).indexes[0];
                var fromform = [t.form.getFormValue('drops', [draginstanceno, 'xleft']),
                                t.form.getFormValue('drops', [draginstanceno, 'ytop'])];
                var constrainedxy = t.constrain_xy(draginstanceno, fromform);
                t.form.getFormValue('drops', [draginstanceno, 'xleft'], constrainedxy[0]);
                t.form.getFormValue('drops', [draginstanceno, 'ytop'], constrainedxy[1]);
            });
            // Change in selected item.
            $('fieldset#id_dropzoneheader select').on('change', function(e) {
                var name = e.target.attr('name');
                var draginstanceno = t.form.fromNameWithIndex(name).indexes[0];
                var old = t.st.drag_item(draginstanceno);
                if (old !== null) {
                    old.remove(true);
                }
                //t.draw_dd_area();
            });

            for (var i = 0; i < this.form.getFormValue('noitems', []); i++) {
                // Change to group selector.
                //$('#fgroup_id_drags_' + i + ' select.draggroup').on('change', t.redraw_dd_area, this);
                //$('#fgroup_id_drags_' + i + ' select.dragitemtype').on('change', t.redraw_dd_area, this);
                $('fieldset#draggableitemheader_' + i + ' input[type="text"]')
                    //.on('blur', t.set_options_for_drag_item_selectors());
                    .on('blur', u.setOptionsForDragItemSelectors());
                // Change to infinite checkbox.
                $('fieldset#draggableitemheader_' + i + ' input[type="checkbox"]')
                    //.on('change', t.set_options_for_drag_item_selectors());
                    .on('change', u.setOptionsForDragItemSelectors());
            }
            // // Event on file picker new file selection.
            // Y.after(function(e) {
            //     var name = this.fp.name(e.id);
            //     if (name !== 'bgimage') {
            //         this.doc.drag_items().remove(true);
            //     }
            //     this.draw_dd_area();
            // }, M.form_filepicker, 'callback', this);
        },
        // make sure xy value is not out of bounds of bg image
        constrain_xy: function(draginstanceno, bgimgxy) {
            var drag = $(t.topNode).one('div.dragitems').one('.draginstance' + draginstanceno);
            var xleftconstrained =
                Math.min(bgimgxy[0], t.bgImg.css('width') - drag.get('offsetWidth'));
            var ytopconstrained =
                Math.min(bgimgxy[1], t.st.bg_img().get('height') - drag.get('offsetHeight'));
            xleftconstrained = Math.max(xleftconstrained, 0);
            ytopconstrained = Math.max(ytopconstrained, 0);
            return [xleftconstrained, ytopconstrained];
        },


        /**
         * Redraws drag and drop preview area.
         *
         * @method redraw_dd_area
         */
        redraw_dd_area: function() {
            var drags = $(t.topNode + ' div.dragitems drags');
            console.log('dddddddddddddddd drags');

            t.doc.drag_items().remove(true);
            t.draw_dd_area();
        },

        /**
         * When a new file gets loaded into the filepicker then display the image.
         * This form event is not in the setupFormEvents section as it needs to be delayed till after
         * filepicker's javascript has finished.
         */
        filepickerOnChange: function() {
            console.log($('form.mform'));
            t.loadPreviewImage();
            //$('form.mform').on('change', '#id_bgimage', t.loadPreviewImage());
        },
        /**
         * Loads the preview background image.
         */
        loadPreviewImage: function() {
            var bgimageurl = t.fp.file('bgimage').href;
            t.bgImg = $(t.params.topnode).find('.dropbackground');
            //t.bgImg.one('load', t.afterImageLoaded());
            t.bgImg.one('load', t.after_all_images_loaded());
            t.bgImg.attr('src', bgimageurl);
            t.bgImg.css(
                {
                    'border': '1px solid #000',
                    'max-width': 'none'
                }
            );
        },
        /**
         * Functions to run after background image loaded.
         */
        afterImageLoaded: function() {
            t.constrainImageSize();
            // Place the dropzone area over the background image (adding one to account for the border).
            $('.dropzone').css('position', 'relative').css('top', (t.bgImg.height() + 1) * -1);
            $('.droparea').css('height', t.bgImg.height() + 20);
            console.log($('.droparea'));
            console.log($('.droparea').css('height'));
            console.log(t.bgImg.css('height'));
            t.addDropzones();
        },
        dropZones: function() {
            return t.topNode.one('div.dropzones div.dropzone');
        },
        dropZoneGroup: function(groupno) {
            return t.topNode.one('div.dropzones div.group' + groupno);
        },
        /**
         * Limits the background image display size.
         */
        constrainImageSize: function() {
            var reduceby = Math.max(t.bgImg.width() / t.params.maxsizes.bgimage.width,
                t.bgImg.height() / t.params.maxsizes.bgimage.height);
            if (reduceby > 1) {
                t.bgImg.css('width', Math.floor(t.bgImg.width() / reduceby));
            }
            t.bgImg.addClass('constrained');
        },

        /**
         * Move events handlers.
         * @param {Object} e Event object
         */
        moveStart: function(e) {
            var dropzoneNo = e.currentTarget.className.baseVal.slice(4);
            // It would be ideal to use the dragdrop library here, so editing this qtype could
            // be done on a mobile device, but the dragProxy passed here does not work with
            // an svgjs element.
            // e.g. dd.start(event, t.shapes[dropzoneNo].movehandle, t.move, t.moveEnd);
            t.shapes[dropzoneNo].moving = true;
            t.shapes[dropzoneNo].resizeing = false;
        },
        move: function(e) {
            var cxy, xy, newxy, changex, changey;
            var dropzoneNo = e.currentTarget.className.baseVal.slice(4);
            if (t.shapes[dropzoneNo].hasOwnProperty('moving') && t.shapes[dropzoneNo].moving) {
                cxy = t.shapes[dropzoneNo].center;
                // Note for some reason FF has difficulty with e.offsetX and Y.
                changex = e.movementX;
                changey = e.movementY;
                cxy = [cxy[0] + changex, cxy[1] + changey];
                switch (t.shapes[dropzoneNo].shape) {
                    case 'circle':
                        t.shapes[dropzoneNo].resizehandle
                            .cx(cxy[0] + t.shapes[dropzoneNo].radius)
                            .cy(cxy[1]);
                        t.shapes[dropzoneNo].dropzone
                            .cx(cxy[0])
                            .cy(cxy[1]);
                        break;
                    case 'rectangle':
                        t.shapes[dropzoneNo].resizehandle
                            .cx(cxy[0] + (t.shapes[dropzoneNo].width / 2))
                            .cy(cxy[1] + (t.shapes[dropzoneNo].height / 2));
                        t.shapes[dropzoneNo].top = [cxy[0] - (t.shapes[dropzoneNo].width / 2),
                            cxy[1] - (t.shapes[dropzoneNo].height / 2)];
                        t.shapes[dropzoneNo].dropzone
                            .cx(cxy[0])
                            .cy(cxy[1]);
                        break;
                    case 'polygon':
                        xy = t.shapes[dropzoneNo].xy;
                        newxy = [];
                        for (var i = 0; i < xy.length; i++) {
                            t.shapes[dropzoneNo].resizehandle[i]
                                .cx(xy[i][0] + changex)
                                .cy(xy[i][1] + changey);
                            newxy[i] = [xy[i][0] + changex, xy[i][1] + changey];
                        }
                        t.shapes[dropzoneNo].xy = newxy;
                        t.shapes[dropzoneNo].dropzone.plot(newxy);
                        break;
                }
                t.shapes[dropzoneNo].movehandle
                    .cx(cxy[0])
                    .cy(cxy[1]);
                t.shapes[dropzoneNo].center = cxy;
                t.shapes[dropzoneNo].markerText
                    .cx(cxy[0])
                    .cy(cxy[1] + 15);
            }
        },
        moveEnd: function(e) {
            var dropzoneNo = e.currentTarget.className.baseVal.slice(4);
            var value, i;
            t.shapes[dropzoneNo].moving = false;
            // Save the coords to the form.
            switch (t.shapes[dropzoneNo].shape) {
                case 'circle':
                    value = t.shapes[dropzoneNo].center[0] + ',' + t.shapes[dropzoneNo].center[1] + ';' +
                        t.shapes[dropzoneNo].radius;
                    t.form.setFormValue('drops', [dropzoneNo, 'coords'], value);
                    break;
                case 'rectangle':
                    value = Math.round(t.shapes[dropzoneNo].top[0]) + ',' + Math.round(t.shapes[dropzoneNo].top[1]) + ';' +
                        Math.round(t.shapes[dropzoneNo].width) + ',' + Math.round(t.shapes[dropzoneNo].height);
                    t.form.setFormValue('drops', [dropzoneNo, 'coords'], value);
                    break;
                case 'polygon':
                    value = '';
                    for (i = 0; i < t.shapes[dropzoneNo].xy.length; i++) {
                        value = value + Math.round(t.shapes[dropzoneNo].xy[i][0]) + ',' +
                            Math.round(t.shapes[dropzoneNo].xy[i][1]) + ';';
                    }
                    value = value.slice(0, value.length - 1);
                    t.form.setFormValue('drops', [dropzoneNo, 'coords'], value);
                    break;
            }
        },
        /**
         * For polygon shaped dropzone only, adds a new vertex (handle).
         * @param {string} dropzoneNo
         * @param {string} resizeno The current vertex or handle number.
         */
        addNewHandle: function(dropzoneNo, resizeno) {
            // Work out the 'next' vertex, add a new vertex between this vertex and the next vertex.
            var coords, xy, i, next;
            coords = '';
            xy = t.shapes[dropzoneNo].xy;
            for (i = 0; i < xy.length; i++) {
                coords = coords + Math.round(xy[i][0]) + ',' + Math.round(xy[i][1]) + ';';
                if (i === Number(resizeno)) {
                    if ((i + 1) === xy.length) {
                        next = 0;
                    } else {
                        next = i + 1;
                    }
                    // Make sure we only put integer numbers into the form.
                    coords = coords + Math.round((xy[i][0] + xy[next][0]) / 2) + ',' +
                        Math.round((xy[i][1] + xy[next][1]) / 2) + ';';
                }
            }
            // Add new coords to form, then redraw the dropzones.
            coords = coords.slice(0, coords.length - 1);
            t.form.setFormValue('drops', [dropzoneNo, 'coords'], coords);
            t.addDropzones();
        },
        /**
         * Returns the coordinates for a drop zone.
         * @param {string} dropzoneNo
         * @returns {string} coords
         */
        getCoords: function(dropzoneNo) {
            var coords = t.form.getFormValue('drops', [dropzoneNo, 'coords']);
            return coords.replace(new RegExp("\\s*", 'g'), '');
        },
        /**
         * Add html for the preview area.
         */
        setupPreviewArea: function(topnode) {
            t.topNode.find('div.fcontainer').append(
                '<div class="ddarea">' +
                    '<div class="droparea"><img class="dropbackground" /></div>' +
                    '<div class="dragitems"></div>' +
                    '<div class="dropzones"></div>' +
                '</div>');
            // var dragitemhomes = topnode.find('.draghome');
            // t.update_padding_sizes_all(topnode);
            // $('div.dragitems').append(dragitemhomes);
        },

        /**
         * Events linked to form actions.
         */
        setupFormEvents: function() {
            // Changes to labels in the Markers section.
            $('fieldset#id_draggableitemheader').on('change', 'input', function() {
                t.set_options_for_drag_item_selectors();
            });
            $('fieldset#id_draggableitemheader').on('change', 'select', function() {
                t.set_options_for_drag_item_selectors();
            });
            // Change in Drop zones section - shape and marker.
            $('fieldset#id_dropzoneheader').on('change', 'select', function(e) {
                var res = e.currentTarget.id.match(/^id_drops_(\d+)_([a-z]*)$/);
                if (!res) {
                    return;
                }
                if (res[2] === 'shape') {
                    //t.updateShape(res[1], e.currentTarget.value);
                } else {
                    t.addDropzones();
                }
            });
            // Change in Drop zones section - manual changes to coordinates.
            $('fieldset#id_dropzoneheader').on('change', 'input', function() {
                t.addDropzones();
            });
        },
        /**
         * Low level operations on form.
         */
        form: {
            toNameWithIndex: function(name, indexes) {
                var indexString = name;
                for (var i = 0; i < indexes.length; i++) {
                    indexString = indexString + '[' + indexes[i] + ']';
                }
                return indexString;
            },
            getEl: function(name, indexes) {
                var form = document.getElementById('mform1');
                return form.elements[this.toNameWithIndex(name, indexes)];
            },
            getFormValue: function(name, indexes) {
                var el = this.getEl(name, indexes);
                if (el === undefined) {
                    return null;
                }
                if (el.type === 'checkbox') {
                    return el.checked;
                } else {
                    return el.value;
                }
            },
            setFormValue: function(name, indexes, value) {
                var el = this.getEl(name, indexes);
                if (el.type === 'checkbox') {
                    el.checked = value;
                } else {
                    el.value = value;
                }
            },
            fromNameWithIndex: function(name) {
                var toreturn = {};
                toreturn.indexes = [];
                var bracket = name.indexOf('[');
                toreturn.name = name.substring(0, bracket);
                while (bracket !== -1) {
                    var end = name.indexOf(']', bracket + 1);
                    toreturn.indexes.push(name.substring(bracket + 1, end));
                    bracket = name.indexOf('[', end + 1);
                }
                return toreturn;
            }
        },

        file_pickers: function() {
            var draftitemidstoname;
            var nametoparentnode;
            if (draftitemidstoname === undefined) {
                draftitemidstoname = {};
                nametoparentnode = {};
                var filepickers = $('form.mform input.filepickerhidden');
                filepickers.each(function(filepicker) {
                    draftitemidstoname[$(filepicker).val()] = $(filepicker).attr('name');
                    nametoparentnode[$(filepicker).val()] = $(filepicker).attr('parentNode');
                });
            }
            var toreturn = {
                file: function(name) {
                    var parentnode = nametoparentnode[name];
                    var fileanchor = parentnode.find('div.filepicker-filelist a');
                    console.log(fileanchor);
                    console.log('ffffffffffffffffff fileanchor');
                    if (fileanchor) {
                        return {href: fileanchor.find('href'), name: fileanchor.html()};
                    } else {
                        return {href: null, name: null};
                    }
                },
                name: function(draftitemid) {
                    return draftitemidstoname[draftitemid];
                }
            };
            return toreturn;
        },
        /**
         * Utility to get the file name and url from the filepicker.
         * @returns {Object} object containing functions {file, name}
         */
        filePickers: function() {
            var draftItemIdsToName;
            var nameToParentNode;
            if (draftItemIdsToName === undefined) {
                draftItemIdsToName = {};
                nameToParentNode = {};
                var fp = $('form.mform input.filepickerhidden');
                fp.each(function(index, filepicker) {
                    draftItemIdsToName[filepicker.value] = filepicker.name;
                    nameToParentNode[filepicker.name] = filepicker.parentNode;
                });
            }
            return {
                file: function(name) {
                    var parentNode = $(nameToParentNode[name]);
                    var fileAnchor = parentNode.find('div.filepicker-filelist a');
                    if (fileAnchor.length) {
                        return {href: fileAnchor.get(0).href, name: fileAnchor.get(0).innerHTML};
                    } else {
                        return {href: null, name: null};
                    }
                },
                name: function(draftitemid) {
                    return draftItemIdsToName[draftitemid];
                }
            };
        }
    };
    return t;
});
