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

/**
 * JavaScript library for dealing with the question flags.
 *
 * This script, and the YUI libraries that it needs, are inluded by
 * the $PAGE->requires->js calls in question_get_html_head_contributions in lib/questionlib.php.
 *
 * @package    moodlecore
 * @subpackage questionengine
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.question = M.question || {};

M.question.flags = {
    flagattributes: [],
    actionurl: null,
    flagtext: [],
    listeners: [],

    init: function(actionurl, flagattributes, flagtext) {
        M.question.flags.flagattributes = flagattributes;
        M.question.flags.actionurl = actionurl;
        M.question.flags.flagtext = flagtext;

        Y.all("div.questionflag").each(function(flagdiv) {
            var checkbox = flagdiv.one('input[type=checkbox]');
            if (!checkbox) {
                return;
            }

            var input = Y.Node.create('<input type="hidden" class="questionflagvalue" />');
            input.set('id', checkbox.get('id'));
            input.set('name', checkbox.get('name'));
            input.set('value', checkbox.get('checked') ? 1 : 0);

            // Create an image input to replace the img tag.
            var image = Y.Node.create('<input type="image" class="questionflagimage" />');
            var flagtext = Y.Node.create('<span class="questionflagtext">.</span>');
            M.question.flags.update_flag(input, image, flagtext);

            checkbox.remove();
            flagdiv.one('label').remove();
            flagdiv.append(input);
            flagdiv.append(image);
            flagdiv.append(flagtext);
        });
        Y.delegate('click', function(e) {
            var input = this.one('input.questionflagvalue');
            input.set('value', 1 - input.get('value'));
            M.question.flags.update_flag(input, this.one('input.questionflagimage'),
                    this.one('span.questionflagtext'));
            var postdata = this.one('input.questionflagpostdata').get('value') +
                    input.get('value');
            e.halt();
            Y.io(M.question.flags.actionurl , {method: 'POST', 'data': postdata});
            M.question.flags.fire_listeners(postdata);
        }, document.body, 'div.questionflag');
    },

    update_flag: function(input, image, flagtext) {
        var value = input.get('value');
        image.setAttrs(M.question.flags.flagattributes[value]);
        flagtext.replaceChild(flagtext.create(M.question.flags.flagtext[value]),
                flagtext.get('firstChild'));
        flagtext.set('title', M.question.flags.flagattributes[value].title);
    },

    add_listener: function(listener) {
        M.question.flags.listeners.push(listener);
    },

    fire_listeners: function(postdata) {
        for (var i = 0; i < M.question.flags.listeners.length; i++) {
            M.question.flags.listeners[i](
                postdata.match(/\bqubaid=(\d+)\b/)[1],
                postdata.match(/\bslot=(\d+)\b/)[1],
                postdata.match(/\bnewstate=(\d+)\b/)[1]
            );
        }
    }
};
