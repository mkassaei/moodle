YUI.add('moodle-mod_quiz-list', function (Y, NAME) {

/**
 * The Moodle.mod_quiz.list classes provide quiz-related list functions.
 *
 * @module moodle-mod_quiz-list
 * @main
 */

Y.namespace('Moodle.mod_quiz.list');

/**
 * A collection of general list functions for use in quiz.
 *
 * @class Moodle.mod_quiz.list
 * @static
 */

M.mod_quiz = M.mod_quiz || {};
M.mod_quiz.init_list = function(params) {
    console.log('M.mod_quiz.init_list: initialised');
//    new DRAGRESOURCE(params);
};

}, '@VERSION@', {"requires": ["node"]});
