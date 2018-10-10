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
 * @subpackage util
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/dragdrop'], function ($, dnd) {

    "use strict";

    var t = {
        dnd: dnd,
        pendingid: null,
        /**
         * dargs contains all the draggable items into a structure.
         */
        drags: [],
        /**
         * drops contains al the place holders
         */
        drops: [],
        afterimageloaddone: false,

        // aDrag: function(topnode) {
        //     // Drag constructor method.
        //
        //     return {
        //         Drag: function (groupno, no, choiceno, instanceno, infinite) {
        //             this._groupno = groupno;
        //             this._no = no;
        //             this._choiceno = choiceno;
        //             this._instanceno = instanceno;
        //             this._infinite = infinite;
        //         },
        //         //
        //     Drag.prototype.getGroupNo = function () {
        //         return this._groupno;
        //     },
        //     Drag.prototype.getNo = function () {
        //         return this._no;
        //     },
        //     Drag.prototype.getChoiceNo = function () {
        //         return this._choiceno;
        //     },
        //     Drag.prototype.getInstanceNo = function () {
        //         return this._instanceno;
        //     },
        //     Drag.prototype.getInfinite = function () {
        //         return this._infinite;
        //     },
        //     }
        // },

        /**
         *
         * @param topnode
         * @param groupno
         * @param no
         * @param choice
         * @param instanceno
         * @returns {{setId: setId}}
         */
        drag: function (topnode, groupno, no, choice, instanceno) {
            // TODO:
            return {
                set: function (val) {
                    id = val;
                }
            }
        },

        drop : function () {
            // Set default values;
            var id = 0;
            var dragId = 0;
            var xy = [0, 0];
            return {
                setId: function (val) {
                    // TODO: validity check.
                    id = val;
                },
                setDragId: function (val) {
                    // TODO: validity check.
                    dragId = val;
                },
                setLeftTop: function (arr) {
                    // TODO: validity check.
                    if (!isArray(arr)) {
                        console.log(arr + " is not an array!");
                    } else {
                        xy = arr;
                    }
                },
                getId: function () {
                    return id;
                },
                getDragId: function () {
                    return dragId;
                },
                getLeftTop: function () {
                    return xy;
                }
            }
        },
        setDrags: function (topnode) {
            var drags = $('fieldset#id_draggableitemheader').find('.fcontainer').children();
            var types = $('fieldset#id_draggableitemheader').find('.fcontainer').find('select.dragitemtype');
            var draghomes = $(t.drag_item_homes(topnode));

            // get the shufflesanswers value for the drags.
            for (var i = 0; i < drags.length; i++) {
                var shuffle = $(drags[i]).find('input#id_shuffleanswers').val();

                var drag = $(drags[i]).find('div#fgroup_id_drags_' + i);
                var group = 0;
                var type = $(drags[i]).find('select.dragitemtype').val();
                if ($(drags[i]).find('select.draggroup').val() !== undefined) {
                    group = 'g' + $(drags[i]).find('select.draggroup').val();
                }
                var infinite = $(drags[i]).find('input#id_drags_' + '_infinite').val();
                t.drags.group = $(drags[i]);
                //t.drags.group = $(drags[i]);
            }
            t.drags = drags;
            t.drags.shuffle = $(drags[0]).find('input#id_shuffleanswers').val();
        },

        setDrops: function (params) {
            //return params.drops;
            var drops = $('fieldset#id_dropzoneheader').find('.fcontainer').children();
            for (var i = 0; i < drops.length; i++) {
                console.log(drops[i]);
                console.log(t.getDropValues(i));
            }
            console.log('pppp');
            t.drops = drops;
        },
        getDropValues: function(draginstanceno) {
            var fromform = [
                t.form.getFormValue('drops', [draginstanceno, 'xleft']),
                t.form.getFormValue('drops', [draginstanceno, 'ytop']),
                t.form.getFormValue('drops', [draginstanceno, 'choice'])
            ];
            return fromform;
        },

        loadDragHomes: function(topnode) {
            // Set up drag items homes.
            for (var i = 0; i < t.form.getFormValue('noitems', []); i++) {
                t.loadDragHome(topnode, i);
            }
        },
        loadDragHome: function(topnode, dragitemno) {
            var url = null;
            if ('image' === t.form.getFormValue('drags', [dragitemno, 'dragitemtype'])) {
                url = t.fp.file(t.form.toNameWithIndex('dragitem', [dragitemno])).href;
            }
            t.add_or_update_drag_item_home(topnode, dragitemno, url,
                t.form.getFormValue('draglabel', [dragitemno]),
                t.form.getFormValue('drags', [dragitemno, 'draggroup']));
        },
        add_or_update_drag_item_home: function(topnode, dragitemno, url, alt, group) {
            var oldhome = $($(topnode.find('div.dragitems')).find(' div.dragitems .dragitemhomes' + dragitemno));
            var classes = 'draghome dragitemhomes' + dragitemno + ' group' + group;
            var imghtml = '<img class="' + classes + '" src="' + url + '" alt="' + alt + '" />';
            var divhtml = '<div class="' + classes + '">' + alt + '</div>';
            var dragItemsArea = topnode.find('div.dragitems');

            if (oldhome === null) {
                if (url) {
                    dragItemsArea.append(imghtml);
                } else if (alt !== '') {
                    dragItemsArea.append(divhtml);
                }
            } else {
                if (url) {
                    dragItemsArea.append(imghtml).after(oldhome);
                } else if (alt !== '') {
                    dragItemsArea.append(divhtml).after(oldhome);
                }
                oldhome.empty();
            }
            var newlycreated = dragItemsArea.find('.dragitemhomes' + dragitemno);
            if (newlycreated !== null) {
                newlycreated.data('groupno', group);
                newlycreated.data('dragitemno', dragitemno);
            }
        },
        update_drag_instances: function(topnode) {
            // Set up drop zones.
            for (var i = 0; i < t.form.getFormValue('nodropzone', []); i++) {
                var dragitemno = t.form.getFormValue('drops', [i, 'choice']);
                if (dragitemno !== '0' && $(t.drag_item(topnode, i) === null)) {
                    var drag = $(t.clone_new_drag_item(topnode, i, dragitemno - 1));
                    if (drag !== null) {
                        if (drag.offset() !== undefined) {
                            var values = t.getDropValues(t.getDragInstance(drag));
                            var xy = t.convert_to_window_xy(topnode, [values[0], values[1]]);
                            drag.css(
                                {
                                    // TODO: xy should be exact after conversion.
                                    // 'left' : xy[0],
                                    // 'top' : xy[1],
                                    'left' : xy[0] - 285,
                                    'top' : xy[1] + 80,
                                    'visibility': 'visible',
                                    'position': 'absolute'
                                }
                            );
                            t.reposition_drag_for_form(topnode, t.getDragInstance(drag));
                        }
                        drag.on('mousedown touchstart', t.mouseDownOrTouchStart(topnode, drag));
                        // TODO: see: t.st.draggable_for_form(drag);
                    }
                }
            }
        },

        draggable_for_form: function(drag) {
            var dd = new Y.DD.Drag({
                node: drag,
                dragMode: 'point'
            }).plug(Y.Plugin.DDConstrained, {constrain2node: topnode});
            dd.on('drag:end', function(e) {
                var dragnode = e.target.get('node');
                var draginstanceno = dragnode.getData('draginstanceno');
                var gooddrop = dragnode.getData('gooddrop');

                if (!gooddrop) {
                    mainobj.reset_drag_xy(draginstanceno);
                } else {
                    mainobj.set_drag_xy(draginstanceno, [e.pageX, e.pageY]);
                }
            }, this);
            dd.on('drag:start', function(e) {
                var drag = e.target;
                drag.get('node').setData('gooddrop', false);
            }, this);
        },

        clone_new_drag_item: function(topnode, draginstanceno, dragitemno) {
            var draghome = $(t.drag_item_home(topnode, dragitemno));
            if (draghome.offset() !== undefined) {
                var drag = $(draghome.clone());
                drag.removeClass('dragitemhomes' + dragitemno);
                drag.addClass('dragitems' + dragitemno);
                drag.addClass('draginstance' + draginstanceno);
                drag.removeClass('draghome');
                drag.addClass('drag');
                drag.data('draginstanceno', draginstanceno);
                drag.data('dragitemno', dragitemno);
                draghome.parent().append(drag);
                return drag;
            }
        },

        bg_img: function(topnode) {
            return topnode.find('.dropbackground');
        },
        load_bg_img: function(topnode, url) {
            var dropbgarea = $(topnode.find('div.droparea'));
            dropbgarea.html('<img class="dropbackground" src="' + url + '"/>');
            $('fieldset#id_previewplaceholder').append(dropbgarea);
        },
        drag_items: function(topnode) {
            //return $(topnode.find('div.dragitems .drag'));
            return $($(topnode.find('div.dragitems')).find('.drag'));
        },
        drag_item: function(topnode, draginstanceno) {
            return $($(topnode.find('div.dragitems')).find('.draginstance' + draginstanceno));
        },
        drag_items_in_group: function(topnode, groupno) {
            return $($(topnode.find('div.dragitems')).find('.drag.group' + groupno));
        },
        drag_item_homes: function(topnode) {
            //return $(topnode.find('div.dragitems')).find('.draghome');
            return $(topnode.find('div.dragitems .draghome'));
        },
        drag_item_home: function(topnode, dragitemno) {
            var dragitemsarea = $(topnode.find('div.dragitems'));
            return $(dragitemsarea.find('.dragitemhomes' + dragitemno));
        },
        drag_items_cloned_from: function(topnode, dragitemno) {
            return $(topnode.find('div.dragitems')).find('.dragitems' + dragitemno);
        },
        drop_zones: function(topnode) {
            return $(topnode.find('div.dropzones div.dropzone'));
        },
        drop_zone_group: function(topnode, groupno) {
            return $(topnode.find('div.dropzones div.group' + groupno));
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
            $(e.target).detach('load', t.constrain_image_size(e, imagetype));
        },

        reposition_drags_for_form: function(topnode) {
            $(t.drag_items(topnode)).each(function(index, drag) {
                var draginstanceno = $(drag).data('draginstanceno');
                t.reposition_drag_for_form(topnode, draginstanceno);

            });
            M.util.js_complete(t.pendingid);
        },
        reposition_drag_for_form: function(topnode, draginstanceno) {
            var drag = t.drag_item(topnode, draginstanceno);
            if (null !== drag && !drag.hasClass('yui3-dd-dragging')) {
                var fromform = [t.form.getFormValue('drops', [draginstanceno, 'xleft']),
                    t.form.getFormValue('drops', [draginstanceno, 'ytop'])];
                if (fromform[0] === '' && fromform[1] === '') {
                    var dragitemno = drag.data('dragitemno');
                    drag.offset($(t.drag_item_home(topnode, dragitemno)).offset());
                } else {
                    drag.offset(t.convert_to_window_xy(topnode, fromform));
                }
            }
        },
        reposition_drags_for_question: function(topnode, dotimeout) {
            t.drag_items(topnode).removeClass('placed');
            t.drag_items(topnode).each(function(index, dragitem) {

                // TODO: use a backToOrigin function instead.
                // if (dragitem.dd !== undefined) {
                //     dragitem.dd.detachAll('drag:start');
                // }
            });
            t.drop_zones(topnode).each(function(index, dropzone) {
                var relativexy = $(dropzone).data('xy');
                $(dropzone).offset(t.convert_to_window_xy(relativexy));
                var inputcss = 'input#' + $(dropzone).data('inputid');
                var input = topnode.one(inputcss);
                var choice = input.val();
                if (choice !== "") {
                    var dragitem = t.get_unplaced_choice_for_drop(choice, $(dropzone));
                    if (dragitem !== null) {
                        dragitem.offset(dropzone.offset());
                        dragitem.addClass('placed');
                        // TODO: use the new DD functionality here
                        // if (dragitem.dd !== undefined) {
                        //     dragitem.dd.once('drag:start', function(e, input) {
                        //         input.set('value', '');
                        //         e.target.get('node').removeClass('placed');
                        //     }, this, input);
                        // }
                    }
                }
            });
            t.drag_items(topnode).each(function(index, dragitem) {
                if (!$(dragitem).hasClass('placed') && !$(dragitem).hasClass('yui3-dd-dragging')) {
                    var dragitemhome = $(t.drag_item_home($(dragitem).data(topnode, 'dragitemno')));
                    $(dragitem).offset(dragitemhome.offset());
                }
            });
            if (dotimeout) {
                //TODO: If I need ths, do it more efficiently
                //setTimeout(t.reposition_drags_for_question(topnode), 1000);
            }
        },
        update_padding_sizes_all: function(topnode) {
            for (var groupno = 1; groupno <= 8; groupno++) {
                t.update_padding_size_for_group(topnode, groupno);
            }
        },
        update_padding_size_for_group: function(topnode, groupno) {
            var groupitems = $(topnode.find('.draghome.group' + groupno));
            if (groupitems.length !== 0) {
                var maxwidth = 0;
                var maxheight = 0;
                groupitems.each(function(index, item) {
                    maxwidth = Math.max(maxwidth, $(item).innerWidth());
                    maxheight = Math.max(maxheight, $(item).innerHeight());
                });
                groupitems.each(function(index, item) {
                    var margintopbottom = Math.round((10 + maxheight - $(item).innerHeight()) / 2);
                    var marginleftright = Math.round((10 + maxwidth - $(item).innerWidth()) / 2);
                    $(item).css('padding', margintopbottom + 'px ' + marginleftright + 'px ' +
                        margintopbottom + 'px ' + marginleftright + 'px');
                });
                var dropZoneGroup = $(t.drop_zone_group(topnode, groupno));
                dropZoneGroup.css({'width': maxwidth + 10, 'height': maxheight + 10});
            }
        },

        /**
         * Add html for the preview area.
         */
        setupPreviewArea: function(topnode) {
            topnode.find('div.fcontainer').append(
                '<div class="ddarea">' +
                    '<div class="droparea"><img class="dropbackground" /></div>' +
                    '<div class="dragitems"></div>' +
                    '<div class="dropzones"></div>' +
                '</div>');
        },

        /**
         * Prevents adding drop zones until the preview background image is ready to load.
         */
        whenImageReady: function(topnode, maxsize) {
            if (t.imageReady) {
                return;
            }
            t.fp = t.filePickers(topnode);
            var bgimageurl = t.fp.file('bgimage').href;
            if (bgimageurl !== null) {
                if (t.pollTimer !== null) {
                    //clearTimeout(t.pollTimer);
                    t.pollTimer = null;
                }
                t.imageReady = true;
                t.filepickerOnChange(topnode, maxsize);
                t.loadPreviewImage(topnode, maxsize);
            } else {
                // It would be better to use an onload or onchange event rather than this timeout.
                // Unfortunately attempts to do this early are overwritten by filepicker during its loading.
                t.pollTimer = setTimeout(t.whenImageReady(topnode, maxsize), 1000);
            }
        },
        /**
         * When a new file gets loaded into the filepicker then display the image.
         * This form event is not in the setupFormEvents section as it needs to be delayed till after
         * filepicker's javascript has finished.
         */
        filepickerOnChange: function(topnode, maxsize) {
            $('form.mform').on('change', t.loadPreviewImage(topnode, maxsize));
        },
        /**
         * Loads the preview background image.
         */
        loadPreviewImage: function(topnode, maxsize) {
            var bgimageurl = t.fp.file('bgimage').href;
            t.bgImg = $(topnode.find('.dropbackground'));
            //t.bgImg.one('load', t.afterImageLoaded(topnode, maxsize));
            //t.bgImg.one('load', t.after_all_images_loaded(topnode));
            t.bgImg.attr('src', bgimageurl);
            t.bgImg.css({'border': '1px solid #000', 'max-width': 'none'});
        },
        after_all_images_loaded: function(topnode) {
            t.reposition_drags_for_form(topnode);
            t.update_padding_sizes_all(topnode);
            t.update_drag_instances(topnode);
            t.reposition_drags_for_form(topnode);
            t.init_drops(topnode);
            t.update_padding_sizes_all(topnode);
            t.setOptionsForDragItemSelectors();
            t.setupFormEvents(t.topNode);
            //t.setup_form_events(topnode);
            // Y.later(500, this, this.reposition_drags_for_form, [], true);
        },

        /**
         * Functions to run after background image loaded.
         */
        afterImageLoaded: function(topnode, maxsize) {
            t.constrainImageSize(maxsize);
            // Place the dropzone area over the background image (adding one to account for the border).
            $('#ddm-dropzone').css('position', 'relative').css('top', (t.bgImg.height() + 1) * -1);
            $('#ddm-droparea').css('height', t.bgImg.height() + 20);
            t.update_padding_sizes_all(topnode);
            //t.addDropzones(topnode);
        },
        /**
         * Limits the background image display size.
         */
        constrainImageSize: function(maxsize) {
            console.log(maxsize.bgimage);
            console.log('maxsize ----------------');

            // var reduceby = Math.max(e.target.width / 't.params.maxsizes.' + imagetype + '.width',
            //     e.target.height / 't.params.maxsizes.' + imagetype + '.height');
            // if (reduceby > 1) {
            //     e.target.css('width', Math.floor(e.target.width / reduceby));
            // }
            // $(e.target).addClass('constrained');

            var reduceby = Math.max(t.bgImg.width() / maxsize.bgimage.width,
                t.bgImg.height() / maxsize.bgimage.height);
            if (reduceby > 1) {
                t.bgImg.css('width', Math.floor(t.bgImg.width() / reduceby));
            }
            t.bgImg.addClass('constrained');
        },
        /**
         * Returns the coordinates for a drop zone.
         * @param {string} dropzoneNo
         * @returns {string} coords
         */
        getCoords: function(dropzoneNo) {
            var coords = t.form.getFormValue('drops', [dropzoneNo, 'coords']);
            console.log(coords);
            console.log('coords ----------');

            return coords.replace(new RegExp("\\s*", 'g'), '');
        },

        /**
         * When a new marker is added this function updates the Marker dropdown controls in Drop zones.
         */
        setOptionsForDragItemSelectors: function() {
            var dragItemsOptions = {'0': ""};
            var noItems = t.form.getFormValue('noitems', []);
            var selectedValues = [];
            var selector;
            var fp = t.filePickers();
            var i, label;
            for (i = 0; i < noItems; i++) {
                label = t.form.getFormValue('draglabel', [i]);
                var file = fp.file(t.form.toNameWithIndex('dragitem', [i]));
                if ('image' === t.form.getFormValue('drags', [i, 'dragitemtype']) && file.name !== null) {
                    dragItemsOptions[i + 1] = (i + 1) + '. ' + label + ' (' + file.name + ')';
                } else if (label !== '') {
                    dragItemsOptions[i + 1] = (i + 1) + '. ' + label;
                }
                if (label !== "") {
                    // HTML escape the label.
                    dragItemsOptions[i] = $('<div/>').text(label).html();
                }
            }
            // Get all the currently selected drags for each drop.
            for (i = 0; i < t.form.getFormValue('nodropzone', []); i++) {
                selector = $('#id_drops_' + i + '_choice');
                console.log(selector);
                console.log('selector ---');
                selectedValues[i] = Number(selector.val());
            }
            console.log(selectedValues);
            console.log(dragItemsOptions);
            for (i = 0; i < t.form.getFormValue('nodropzone', []); i++) {
                selector = $('#id_drops_' + i + '_choice');
                // Remove all options for drag choice.
                //selector.find('option').remove();
                // And recreate the options.
 
                for (var value in dragItemsOptions) {
                    value = Number(value);
                    var option = '<option value="' + value + '">' + dragItemsOptions[value] + '</option>';
                    //selector.append(option);
                    var optionnode = selector.find('option[value="' + value + '"]');

                    // Is this the currently selected value?
                    if (value === selectedValues[i]) {
                        optionnode.attr('selected', true);
                    } else {
                        // It is not the currently selected value, is it selectable?
                        if (value !== 0) { // The 'no item' option is always selectable.
                            // Variables to hold form values about this drag item.
                            var noofdrags = t.form.getFormValue('drags', [value - 1, 'noofdrags']);
                            if (Number(noofdrags) !== 0) { // 'noofdrags === 0' means infinite.
                                // Go through all selected values in drop downs.
                                for (var k in selectedValues) {
                                    // Count down 'noofdrags' and if reach zero then set disabled option for this drag item.
                                    if (Number(selectedValues[k]) === value) {
                                        if (Number(noofdrags) === 1) {
                                            optionnode.attr('disabled', true);
                                            break;
                                        } else {
                                            noofdrags--;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        },
        initDrags: function (topnode) {
            var drags, drag;
            var dragItems = $('fieldset#id_draggableitemheader').find('div.fcontainer').children();
            for (var i = 0; i < dragItems.length; i++) {
                console.log($(dragItems[i]));
            }
            console.log(dragItems.length);
            console.log('1111111111111');
            return dragItems;
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
        formInit: function (topnode) {
           t.create_all_drag_and_drops(topnode); 
        },
        create_all_drag_and_drops: function(topnode, readonly) {
            //t.init_drops(topnode);
            var i = 0;
            $(t.drag_item_homes(topnode)).each(function(index, dragitemhome) {
                var dragitemno = Number(t.getClassnameNumericSuffix($(dragitemhome), 'dragitemhomes'));
                var choice = t.getClassnameNumericSuffix($(dragitemhome), 'choice');
                var group = t.getClassnameNumericSuffix($(dragitemhome), 'group');
                var groupsize = $(t.drop_zone_group(topnode, group)).length;
                var dragnode = $(t.clone_new_drag_item(topnode, i, dragitemno));
                dragnode.css('width', $(dragitemhome).width());
                dragnode.offset($(dragitemhome).offset());
                dragnode.offset($(dragitemhome).offset());

                console.log($(dragitemhome).width());
                console.log($(dragitemhome).offset());
                console.log(dragnode);
                console.log('dragnode ----------------');

                i++;
                if (!readonly) {
                    t.draggable_for_question(dragnode, group, choice);
                }
                if (dragnode.hasClass('infinite')) {
                    var dragstocreate = groupsize - 1;
                    while (dragstocreate > 0) {
                        dragnode = t.clone_new_drag_item(i, dragitemno);
                        i++;
                        if (!readonly) {
                            t.draggable_for_question(dragnode, group, choice);

                            // Prevent scrolling whilst dragging on Adroid devices.
                            //this.prevent_touchmove_from_scrolling(dragnode);
                        }
                        dragstocreate--;
                    }
                }
            });
            t.reposition_drags_for_question(topnode, false);
            if (!readonly) {
                t.drop_zones(topnode).css('tabIndex', 0);
                t.drop_zones(topnode).each( function(e) {
                        e.on('dragchange', t.drop_zone_key_press(e));
                    });
            }
            M.util.js_complete(t.pendingId);
        },
        drop_zone_key_press: function(e) {
            switch (e.direction) {
                case 'next' :
                    t.place_next_drag_in(e.target);
                    break;
                case 'previous' :
                    t.place_previous_drag_in(e.target);
                    break;
                case 'remove' :
                    t.remove_drag_from_drop(e.target);
                    break;
            }
            e.preventDefault();
            //t.reposition_drags_for_question(topnode);
        },
        place_next_drag_in: function(drop) {
            t.search_for_unplaced_drop_choice(drop, 1);
        },
        place_previous_drag_in: function(drop) {
            t.search_for_unplaced_drop_choice(drop, -1);
        },
        search_for_unplaced_drop_choice: function(drop, direction) {
            var next;
            var current = this.current_drag_in_drop(drop);
            if ('' === current) {
                if (direction === 1) {
                    next = 1;
                } else {
                    next = 1;
                    var groupno = drop.getData('group');
                    this.doc.drag_items_in_group(groupno).each(function(drag) {
                        next = Math.max(next, drag.getData('choice'));
                    }, this);
                }
            } else {
                next = +current + direction;
            }
            var drag;
            do {
                if (this.get_choices_for_drop(next, drop).size() === 0) {
                    this.remove_drag_from_drop(drop);
                    return;
                } else {
                    drag = this.get_unplaced_choice_for_drop(next, drop);
                }
                next = next + direction;
            } while (drag === null);
            this.place_drag_in_drop(drag, drop);
        },

        init_drops: function(topnode) {
            var dropareas = $(topnode).find('div.dropzones');
            var groupnodes = {};
            for (var groupno = 1; groupno <= 8; groupno++) {
                var groupnode = $('<div class = "dropzonegroup' + groupno + '"></div>');
                dropareas.append(groupnode);
                groupnodes[groupno] = groupnode;
            }
            return;
            var drop_hit_handler = function(e) {
                console.log(e);
                console.log('eeeeeeeeeeeee');
                var drag = e.drag.get('node');
                var drop = e.drop.get('node');
                if (Number(drop.data('group')) === drag.data('group')) {
                    //t.place_drag_in_drop(drag, drop);
                    console.log(e);
                }
            };
            console.log(groupnodes);
            for (var dropno in $('drops')) {
                var drop = $('drops')[dropno];
                var nodeclass = 'dropzone group' + drop.group + ' place' + dropno;
                var title = drop.text.replace('"', '\"');
                if (!title) {
                    title = M.util.get_string('blank', 'qtype_ddimageortext');
                }
                var dropnodehtml = '<div title="' + title + '" class="' + nodeclass + '">' +
                    '<span class="accesshide">' + title + '</span>&nbsp;</div>';
                var dropnode = Y.Node.create(dropnodehtml);
                groupnodes[drop.group].append(dropnode);
                dropnode.setStyles({'opacity': 0.5});
                dropnode.setData('xy', drop.xy);
                dropnode.setData('place', dropno);
                dropnode.setData('inputid', drop.fieldname.replace(':', '_'));
                dropnode.setData('group', drop.group);
                // var dropdd = new Y.DD.Drop({
                //     node: dropnode, groups: [drop.group]});
                // dropdd.on('drop:hit', drop_hit_handler, this);
            }
        },
        draggable_for_question: function(drag, group, choice) {
            // new Y.DD.Drag({
            //     node: drag,
            //     dragMode: 'point',
            //     groups: [group]
            // }).plug(Y.Plugin.DDConstrained, {constrain2node: topnode});
            drag.data('group', group);
            drag.data('choice', choice);
        },

        setup_form_events: function(topnode) {
            // Events triggered by changes to form data.
            // X and y coordinates.
            $('fieldset#id_dropzoneheader input').on('blur', function (e) {
                var name = e.target.attr('name');
                var draginstanceno = t.form.fromNameWithIndex(name).indexes[0];
                var fromform = [t.form.getFormValue('drops', [draginstanceno, 'xleft']),
                    t.form.getFormValue('drops', [draginstanceno, 'ytop'])];
                var constrainedxy = t.constrain_xy(draginstanceno, fromform);
                t.form.getFormValue('drops', [draginstanceno, 'xleft'], constrainedxy[0]);
                t.form.getFormValue('drops', [draginstanceno, 'ytop'], constrainedxy[1]);
            });
            // Change in selected item.
            $('fieldset#id_dropzoneheader select').on('change', function (e) {
                var name = e.target.attr('name');
                var draginstanceno = t.form.fromNameWithIndex(name).indexes[0];
                var old = t.drag_item(topnode, draginstanceno);
                if (old !== null) {
                    old.remove(true);
                }
                //t.draw_dd_area();
            });
        },
        /**
         * Events linked to form actions.
         */
        setupFormEvents: function(topnode) {
            // Changes to labels in the Markers section.
            $('fieldset#id_draggableitemheader').on('change', 'input', function() {
                t.setOptionsForDragItemSelectors();
            });
            $('fieldset#id_draggableitemheader').on('change', 'select', function() {
                t.setOptionsForDragItemSelectors();
            });
            // Change in Drop zones section - manual changes to coordinates.
            $('fieldset#id_dropzoneheader').on('change', 'input', function() {
                t.setOptionsForDragItemSelectors();
                //t.addDropzones();
            });
        },

        /**
         * Draws or re-draws all dropzones in the preview area based on form data.
         * Call this function when there is a change in the form data.
         */
        addDropzones: function(topnode) {
            var darggableItems = $('fieldset#id_draggableitemheader');
            var noOfDropzones, dropzoneNo, dragItemNo, draggableText;
            noOfDropzones = t.form.getFormValue('nodropzone', []);
            console.log(darggableItems);
            console.log(noOfDropzones);
            console.log(darggableItems.find('div.fcontainer'));
            console.log('************************* darggableItems');
            for (dropzoneNo = 0; dropzoneNo < noOfDropzones; dropzoneNo++) {
                dragItemNo = t.form.getFormValue('drops', [dropzoneNo, 'choice']);
                draggableText = t.form.getFormValue('draglabel', [dragItemNo]);
                var drag = t.clone_new_drag_item(topnode, dropzoneNo, dragItemNo);
                console.log(dropzoneNo);
                console.log(dragItemNo);
                console.log(draggableText);
                console.log(drag);
                console.log('draggableText -------------------');
                if (drag !== null) {
                    console.log(drag);
                    console.log('nnnnnnnnnnnnnnnnnnnnnnnnnnn');
                    //this.doc.draggable_for_form(drag);
                }
                var draghomes = $(topnode.find('div.fcontainer'));
                console.log(draghomes);
                console.log('draghomes -----------');

            }
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

        reset_drag_xy: function(draginstanceno) {
            t.form.setFormValue('drops', [draginstanceno, 'xleft'], '');
            t.form.setFormValue('drops', [draginstanceno, 'ytop'], '');
        },
        set_drag_xy: function(topnode, draginstanceno, xy) {
            xy = t.constrain_xy(topnode, draginstanceno, t.convert_to_bg_img_xy(topnode, xy));
            t.form.setFormValue('drops', [draginstanceno, 'xleft'], Math.round(xy[0]));
            t.form.setFormValue('drops', [draginstanceno, 'ytop'], Math.round(xy[1]));
        },
        // make sure xy value is not out of bounds of bg image
        constrain_xy: function(topnode, draginstanceno, bgimgxy) {
            var drag = $(t.drag_item(topnode, draginstanceno));
            var xleftconstrained =
                Math.min(bgimgxy[0], $(t.bg_img(topnode)).width() - drag.width());
            var ytopconstrained =
                Math.min(bgimgxy[1], $(t.bg_img(topnode)).height() - drag.height());
            xleftconstrained = Math.max(xleftconstrained, 0);
            ytopconstrained = Math.max(ytopconstrained, 0);
            return [xleftconstrained, ytopconstrained];
        },
        convert_to_bg_img_xy: function(topnode, windowxy) {
            console.log(windowxy);
            console.log($(t.bg_img(topnode)).offset().top);
            console.log('windowxy ----');
            return [Number(windowxy[0]) - $(t.bg_img(topnode)).offset().left - 1,
                Number(windowxy[1]) - $(t.bg_img(topnode)).offset().top - 1];
        },
        convert_to_window_xy: function(topnode, bgimgxy) {
            return [Number(bgimgxy[0]) + $(t.bg_img(topnode)).offset().left - 1,
                Number(bgimgxy[1]) + $(t.bg_img(topnode)).offset().top - 1];
        },

        /**
         * Return a function on mousedown or touchstart.
         * @param dragProxy
         * @returns {Function}
         */
        mouseDownOrTouchStart: function(topnode, dragProxy) {
            var draginstanceno = t.getDragInstance(dragProxy);
            var group = t.getGroup(dragProxy);
            var draginfo = {};
            draginfo.group = group;
            draginfo.instanceno = draginstanceno;
            draginfo.drag = dragProxy;
            draginfo.origin = dragProxy.position();
            return function (e) {
                if (dnd.prepare(e).start === true) {
                    e.preventDefault();
                    dragProxy.css('cursor', 'move');
                    dragProxy.addClass('moodle-has-zindex');
                    console.log(e.pageX);
                    console.log([e.pageX, e.pageY]);
                    var om = function (x, y) {
                        t.onMoveForm(topnode, [x, y], draginfo, dragProxy);
                    };
                    var od = function (x, y) {
                        t.onDropForm(topnode, [x, y], draginfo, dragProxy);
                    };
                    dragProxy.css('z-index', (Number(dragProxy.css('z-index')) + 1));
                    dnd.start(e, dragProxy, om, od);
                } else {
                    console.log(dragProxy);
                    console.log('dragProxy yyyyyyyyyyyyyy');
                }
            };
        },

        /**
         * Called when user drags a drag item.
         *
         * @param topnode
         * @param x
         * @param y
         * @param draginfo
         * @param dragProxy
         */
        onMoveForm: function (topnode, xy, draginfo, dragProxy) {
            console.log(draginfo);
            console.log(dragProxy);
            console.log(xy);
            console.log('onMoveForm xy');
            t.set_drag_xy(topnode, draginfo.instanceno, xy);
        },

        /**
         * Called when user drops a drag item and applies the change.
         *
         * @param topnode
         * @param draginfo
         * @param dragProxy
         */
        onDropForm: function (topnode, xy, draginfo, dragProxy) {
            //var constrainedxy = t.constrain_xy(topnode, draginfo.instanceno, xy);
            t.set_drag_xy(topnode, draginfo.instanceno, xy);
            return;
        },
        /**
         * Called when user drags a drag item.
         *
         * @param topNode
         * @param x
         * @param y
         * @param draginfo
         * @param dragProxy
         */
        onMove: function (topNode, x, y, draginfo, dragProxy) {
            var container = $(topNode).find('.formulation');
            if (t.isDragOutsideQuestionContainer(container, x, y)) {
                // Highlight the border around the question container.
                container.addClass('outside-container');

                dragProxy.on('mouseup', function() {
                    // Remove the highlighted border around the question container.
                    container.removeClass('outside-container');
                });
                return;
            }
            var drops = $(t.cssSelectors(topNode).dropsInGroup(draginfo.group));
            drops.each(function(index, drop){
                if (t.isDragCloseToTarget(dragProxy, $(drop))) {
                    $(drop).addClass('valid-drag-over-drop');
                } else {
                    $(drop).removeClass('valid-drag-over-drop');
                }
            });
        },

        /**
         * Called when user drops a drag item and applies the change.
         *
         * @param topNode
         * @param draginfo
         * @param dragProxy
         */
        onDrop: function (topNode, draginfo, dragProxy) {
            var drops = $(t.cssSelectors(topNode).dropsInGroup(draginfo.group));
            var breakOut = false;
            drops.each(function(index, drop){
                // If dropping the same drag inside the drop break out.
                if (t.isDragInTheSameDrop(dragProxy, $(drop))) {
                    breakOut = true;
                    return false;
                }
                if (t.isDragCloseToTarget(dragProxy, $(drop))) {
                    $(drop).removeClass('valid-drag-over-drop');
                    t.putDragInDrop(topNode, $(dragProxy), $(drop));

                    breakOut = true;
                    return false;
                }
            });
            if(breakOut) {
                //breakOut = false;
                return false;
            } else {
                t.putBackToOrigin(topNode, dragProxy);
            }
        },
        /**
         * Return the number at the end of the prefix.
         * @param node
         * @param prefix
         * @returns {*|number}
         */
        getClassnameNumericSuffix: function (node, prefix) {
            var classes = node.attr('class');
            if (classes !== '') {
                var classesArr = classes.split(' ');
                for (var index = 0; index < classesArr.length; index++) {
                    var patt1 = new RegExp('^' + prefix + '([0-9])+$');
                    if (patt1.test(classesArr[index])) {
                        var patt2 = new RegExp('([0-9])+$');
                        var match = patt2.exec(classesArr[index]);
                        return Number(match[0]);
                    }
                }
            }
            throw 'Prefix "' + prefix + '" not found in class names.';
        },
        getDragInstance: function (node) {
            return t.getClassnameNumericSuffix(node, 'draginstance');
        },
        getChoice: function (node) {
            return t.getClassnameNumericSuffix(node, 'choice');
        },
        getGroup: function (node) {
            return t.getClassnameNumericSuffix(node, 'group');
        },
        getPlace: function (node) {
            return t.getClassnameNumericSuffix(node, 'place');
        },
        getNo: function (node) {
            return t.getClassnameNumericSuffix(node, 'no');
        },
        set_options_for_drag_item_selectors: function() {
            var dragitemsoptions = {'0': ''};
            for (var i = 0; i < t.form.getFormValue('noitems', []); i++) {
                var label = t.form.getFormValue('draglabel', [i]);
                var fp = t.filePickers();
                var file = fp.file(t.form.toNameWithIndex('dragitem', [i]));
                if ('image' === t.form.getFormValue('drags', [i, 'dragitemtype']) && file.name !== null) {
                    dragitemsoptions[i + 1] = (i + 1) + '. ' + label + ' (' + file.name + ')';
                } else if (label !== '') {
                    dragitemsoptions[i + 1] = (i + 1) + '. ' + label;
                }
                console.log(dragitemsoptions);
                console.log('dragitemsoptions');
            }
            for (i = 0; i <= t.form.getFormValue('nodropzone', []); i++) {
                var selector = $('#id_drops_' + i + '_choice');
                console.log(selector);
                console.log('selector ------------------');

                //var selector = $('fieldset.felement.fgroup' + ' #id_drops_' + i + '_choice');
                var selectedvalue = selector.val();
                selector.find('option').remove(selector);
                for (var value in dragitemsoptions) {
                    value = Number(value);
                    var option = '<option value"=' + value + '">' + dragitemsoptions[value] + '</option>';
                    selector.append(option);
                    var optionnode = selector.find('option[value="' + value + '"]');
                    if (value === +selectedvalue) {
                        optionnode.attr('selected', true);
                    } else {
                        if (value !== 0) { // No item option is always selectable.
                            var cbel = $('#id_drags_' + (value - 1) + '_infinite');
                            if (cbel && !cbel.css('checked')) {
                                if (t.item_is_allocated_to_dropzone(value)) {
                                    optionnode.attr('disabled', true);
                                }
                            }
                        }
                    }
                }
            }
        },
        /**
         * Checks if the specified drag item is allocated to a dropzone.
         *
         * @method item_is_allocated_to_dropzone
         * @param {Number} value of the drag item to check
         * @return {Boolean} true if item is allocated to dropzone
         */
        item_is_allocated_to_dropzone: function(itemvalue) {
            return $('fieldset#id_dropzoneheader select').each(function(index, selectNode) {
                return Number($(selectNode).val()) === itemvalue;
            });
        },
        poll_for_image_load: function(e, topnode, waitforimageconstrain, pause, doafterwords) {
            if (t.afterimageloaddone) {
                return;
            }
            var bgdone = $(t.bg_img(topnode)).attr('complete');
            if (waitforimageconstrain) {
                bgdone = bgdone && $(t.bg_img(topnode)).hasClass('constrained');
            }
            var alldragsloaded = !$(t.drag_item_homes(topnode)).each(function(dragitemhome) {
                // in 'some' loop returning true breaks the loop and is passed as return value from
                // 'some' else returns false. Can be though of as equivalent to ||.
                if ($(dragitemhome).attr('tagName') !== 'IMG') {
                    return false;
                }
                var done = $(dragitemhome).attr('complete');
                if (waitforimageconstrain) {
                    done = done && $(dragitemhome).hasClass('constrained');
                }
                return !done;
            });
            if (bgdone && alldragsloaded) {
                if (t.polltimer !== null) {
                    t.polltimer.cancel();
                    t.polltimer = null;
                }
                var pollarguments = [e, topnode, waitforimageconstrain, pause, doafterwords];
                $(t.drag_item_homes(topnode)).detach('load', t.poll_for_image_load(pollarguments));
                $(t.bg_img(topnode)).detach('load', t.poll_for_image_load(pollarguments));
                if (pause !== 0) {
                    //Y.later(pause, this, doafterwords);
                } else {
                    //doafterwords.call(this);
                }
                t.afterimageloaddone = true;
            } else if (t.polltimer === null) {
                console.log('t.polltimer is null *************');
                //var pollarguments = [null, waitforimageconstrain, pause, doafterwords];
                //this.polltimer =
                //    Y.later(1000, this, this.poll_for_image_load, pollarguments, true);
            }
        }
    };
    return t;
});
