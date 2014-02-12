<?php
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
 * Defines the Moodle forum used to add random questions to the quiz.
 *
 * @package    mod_quiz
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');


/**
 * The section form.
 * @package    mod_quiz
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_section_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        // Section heading name.
          $mform->addElement('header', 'sectionheader',
                get_string('quizsection', 'quiz'));

        $mform->addElement('text', 'sectionheading', get_string('quizsectionheading', 'quiz'), 'maxlength="128" size="60"');
        $mform->setType('sectionheading', PARAM_TEXT);
        $this->add_action_buttons();
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        if (trim($fromform['sectionheading']) == '') {
            $errors['sectionheading'] = get_string('sectionheadingnoempty', 'quiz');
        }

        return $errors;
    }
}

