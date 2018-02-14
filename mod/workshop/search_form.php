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
 * This file contains the forms for worksgop search options.
 *
 * @package   mod_workshop
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/mod/workshop/locallib.php');

/**
 * Assignment grading options form
 *
 * @package   mod_workshop
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_workshop_search_form extends moodleform {
    /**
     * Define this form - called from the parent constructor.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'idnumber', get_string('idnumber'), array('size' => 16));
        $mform->setType('idnumber', PARAM_RAW);

        $mform->addElement('text', 'fname', get_string('firstname'), array('size' => 32));
        $mform->setType('fname', PARAM_RAW);

        $mform->addElement('text', 'lname', get_string('lastname'), array('size' => 32));
        $mform->setType('lname', PARAM_RAW);

        // List of options to be used for filtering.
        $options = array(workshop_search::NO_FILTER => get_string('searchnofilter', 'workshop'),
                workshop_search::SUBMISSION_SUBMITTED => get_string('submissionsubmitted', 'workshop'),
                workshop_search::SUBMISSION_NOT_SUBMITTED => get_string('submissionnotsubmitted', 'workshop'),
                3 => get_string('nogradeyet', 'workshop'),
                4 => get_string('nothingtoreview', 'workshop'),
                5 => get_string('notassessed', 'workshop'));
        $dirtyclass = array('class' => 'ignoredirty');
        $mform->addElement('select', 'filter', get_string('searchfilter', 'workshop'), $options, $dirtyclass);

        // Hidden params.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, get_string('searchandupdatetable', 'workshop'));
    }

    /**
     * Validate the input data.
     * @param array $data
     * @param array $files
     * @return array
     * @throws coding_exception
     */
    public function validation($data, $files) {
        global $DB, $COURSE;
        $errors = parent::validation($data, $files);
        // Check if a valid search option has been chosen.
        $allusers = get_enrolled_users(context_course::instance($COURSE->id));
        $alluserids = array_keys($allusers);
        if ($data['idnumber']) {
            if (!$users = $this->get_user_id($alluserids, 'idnumber', $data['idnumber'])) {
                $errors['idnumber'] = get_string('usernotfoundbyidnumber', 'workshop', $data['idnumber']);
            }
        }
        if ($data['fname']) {
            if (!$users = $this->get_user_id($alluserids, 'firstname', $data['fname'])) {
                $errors['fname'] = get_string('usernotfoundbyfirstname', 'workshop', $data['fname']);
            }
        }
        if ($data['lname']) {
            if (!$users = $this->get_user_id($alluserids, 'lastname', $data['lname'])) {
                $errors['lname'] = get_string('usernotfoundbylastname', 'workshop', $data['lname']);
            }
        }
        return $errors;
    }

    /**
     * Return array of users.
     * @param array $alluserids
     * @param string $fieldname
     * @param string $value
     * @return array
     */
    private function get_user_id($alluserids, $fieldname, $value) {
        global $DB;
        $sql = "SELECT u.id
                    FROM {user} u
                    WHERE LOWER(u.$fieldname) like LOWER('$value%')";
        if ($users = $DB->get_records_sql($sql)) {
            $userids = array_keys($users);
            foreach ($userids as $i => $userid) {
                if (!in_array($userid, $alluserids)) {
                    unset($userids[$i]);
                }
            }
            return $users;
        }
    }
}
