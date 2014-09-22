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
 * @package   mod_quiz
 * @copyright 2008 Olli Savolainen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');


/**
 * The add random questions form.
 *
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_add_random_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        $contexts = $this->_customdata['contexts'];
        $usablecontexts = $contexts->having_cap('moodle/question:useall');

        // Random from existing category section.
        $mform->addElement('header', 'categoryheader',
                get_string('randomfromexistingcategory', 'quiz'));

        $mform->addElement('questioncategory', 'category', get_string('category'),
                array('contexts' => $usablecontexts, 'top' => false));

        $mform->addElement('checkbox', 'includesubcategories', '', get_string('recurse', 'quiz'));

        list($categoryid) = explode(',', $this->_customdata['cat']);
        //$randomnumber = $this->get_number_of_questions_in_category($categoryid, false);
        $randomnumber = $this->get_random_numbers();
        $mform->addElement('select', 'randomnumber', get_string('randomnumber', 'quiz'), $randomnumber);

       $mform->addElement('submit', 'existingcategory', get_string('addrandomquestion', 'quiz'));

        // Random from a new category section.
        $mform->addElement('header', 'categoryheader',
                get_string('randomquestionusinganewcategory', 'quiz'));

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="254" size="50"');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('questioncategory', 'parent', get_string('parentcategory', 'question'),
                array('contexts' => $usablecontexts, 'top' => true));
        $mform->addHelpButton('parent', 'parentcategory', 'question');

        $mform->addElement('submit', 'newcategory',
                get_string('createcategoryandaddrandomquestion', 'quiz'));

        // Submit button
        $mform->addElement('cancel');
        $mform->closeHeaderBefore('cancel');

        $mform->addElement('hidden', 'addonpage', 0, 'id="rform_qpage"');
        $mform->setType('addonpage', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'cmid', 0);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'returnurl', 0);
        $mform->setType('returnurl', PARAM_LOCALURL);
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        if (!empty($fromform['newcategory']) && trim($fromform['name']) == '') {
            $errors['name'] = get_string('categorynamecantbeblank', 'question');
        }

        return $errors;
    }

    private function get_random_number($maxrand = 100) {
        $randomcount = array();
        if ($maxrand <= 0) {
            return 0;
        }
        if ($maxrand <= 20) {
            for ($i = 1; $i <= $maxrand; $i++) {
                $randomcount[$i] = $i;
            }
            return $randomcount;
        }
        for ($i = 1; $i <= min(10, $maxrand); $i++) {
            $randomcount[$i] = $i;
        }
        for ($i = 20; $i <= min(100, $maxrand); $i += 10) {
           $randomcount[$i] = $i;
        }
        return $randomcount;
    }

    /**
     * Return an arbitrary array for the dropdown menu
     * @return array of integers array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100)
     */
    private function get_random_numbers() {
        $maxrand = 100;
        $randomcount = array();
        for ($i = 1; $i <= min(10, $maxrand); $i++) {
            $randomcount[$i] = $i;
        }
        for ($i = 20; $i <= min(100, $maxrand); $i += 10) {
            $randomcount[$i] = $i;
        }
        return $randomcount;
    }

    /**
     * Get the number of questions in a given category and creates an array
     * of integers for a dropdown menu
     *
     * @param int $categoryid
     * @oaram bool $recurse
     * @return array of integers
     */
    private function get_number_of_questions_in_category($categoryid, $recurse) {
        $randomusablequestions =
        question_bank::get_qtype('random')->get_available_questions_from_category(
                $categoryid, $recurse);
        $maxrand = count($randomusablequestions);
        $randomcount = array();
        $disabled = null;
        if ($maxrand > 0) {
            for ($i = 1; $i <= min(10, $maxrand); $i++) {
                $randomcount[$i] = $i;
            }
            for ($i = 20; $i <= min(100, $maxrand); $i += 10) {
                $randomcount[$i] = $i;
            }
        } else {
            $randomcount[0] = 0;
            $disabled = ' disabled="disabled"';
        }
        return $randomcount;
    }

}

