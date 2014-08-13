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
 * Add questions from question bank functionality for a popup in quiz editing page.
 *
 * @package   mod_quiz
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


var CSS = {
        QBANKLOADING: 'div.questionbankloading',
        QBANKLINK: 'a.questionbank',
        QBANK: '.questionbank',
        ADDTOQUIZCONTAINER: 'td.addtoquizaction'
};

var PARAMS = {
    PAGE: 'addonpage',
    HEADER: 'header'
};

var POPUP = function() {
    POPUP.superclass.constructor.apply(this, arguments);
};

Y.extend(POPUP, Y.Base, {
    qbank: Y.one(CSS.QBANK),
    loadingDiv: '',
    dialogue: null,

    create_dialogue: function() {
        // Create a dialogue on the page and hide it.
        config = {
            headerContent : this.qbank._node.getAttribute(PARAMS.HEADER),
            bodyContent : Y.one(CSS.QBANKLOADING),
            draggable : true,
            modal : true,
            context: [CSS.QBANK, 'tr', 'br', ['beforeShow']],
            centered: true,
            width: null,
            visible: false,
            postmethod: 'form',
            footerContent: null,
            extraClasses: ['mod_quiz_qbank_dialogue']
        };
        this.dialogue = new M.core.dialogue(config);
        this.dialogue.bodyNode.delegate('click', this.link_clicked, 'a[href]', this);
        this.dialogue.hide();

        this.loadingDiv = this.dialogue.bodyNode.getHTML();

        Y.later(100, this, function() {this.load_content(window.location.search);});
    },

    initializer : function() {
        this.create_dialogue();

        Y.all(CSS.QBANKLINK).each(function(node) {
            var page = node.getData(PARAMS.PAGE);
            node.on('click', this.display_dialog, this, page);
        }, this);
    },

    display_dialog : function (e, page) {
        e.preventDefault();
        this.dialogue.show();
    },

    load_content : function(queryString) {
        Y.log('Starting load.');
        this.dialogue.bodyNode.setHTML(this.loadingDiv);
        if (this.dialogue.visible) {
            Y.later(0, this.dialogue, this.dialogue.centerDialogue);
        }

        window.history.replaceState(null, '', M.cfg.wwwroot + '/mod/quiz/edit.php' + queryString);

        Y.io(M.cfg.wwwroot + '/mod/quiz/questionbank.ajax.php' + queryString, {
            method: 'GET',
            on: {
                success: this.load_done,
                failure: this.load_failed
            },
            context: this
        });

        Y.log('Load started.');
    },

    load_done: function(transactionid, response) {
        var result = JSON.parse(response.responseText);
        if (!result.status || result.status !== 'OK') {
            // Because IIS is useless, Moodle can't send proper HTTP response
            // codes, so we have to detect failures manually.
            this.load_failed(transactionid, response);
            return;
        }

        Y.log('Load completed.');

        this.dialogue.bodyNode.setHTML(result.contents);
        Y.use('moodle-question-chooser', function() {M.question.init_chooser({courseid: 2});}); // TODO hard-coded id.
        this.dialogue.bodyNode.one('form').delegate('change', this.options_changed, '.searchoptions', this);

        if (this.dialogue.visible) {
            Y.later(0, this.dialogue, this.dialogue.centerDialogue);
        }
    },

    load_failed: function() {
        Y.log('Load failed.');
    },

    link_clicked: function(e) {
        if (e.currentTarget.ancestor(CSS.ADDTOQUIZCONTAINER)) {
            // These links need to work like normal.
            return;
        }
        e.preventDefault();
        this.load_content(e.currentTarget.get('search'));
    },

    options_changed: function(e) {
        e.preventDefault();
        this.load_content('?' + Y.IO.stringify(e.currentTarget.get('form')));
    }
});

M.mod_quiz = M.mod_quiz || {};
M.mod_quiz.quizquestionbank = M.mod_quiz.quizquestionbank || {};
M.mod_quiz.quizquestionbank.init = function() {
    return new POPUP();
};
