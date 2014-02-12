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
 * Quiz section
 * @package    mod_quiz
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once($CFG->dirroot . '/mod/quiz/section_form.php');
require_once($CFG->dirroot . '/mod/quiz/editlib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$quizid = optional_param('quizid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);
//$addonpage = optional_param('addonpage', 0, PARAM_INT);

require_login();
// Get the course object and related bits.
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

// Get the quiz object and related bits.
if (!$quiz = $DB->get_record('quiz', array('id' => $quizid))) {
    print_error(get_string('invalidquizid', 'quiz'));
}

// Require an appropriate capability here, may be  'mod/quiz:manage'

$cm = $DB->get_record('course_modules', array('id' => $cmid));

$params = array('courseid' => $course->id, 'quiz' => $quizid, 'cmid' => $cmid, 'returnurl' => $returnurl);
$thispageurl = new moodle_url('/mod/quiz/section.php', $params);
$PAGE->set_url($thispageurl);
$streditingquiz = get_string('sectionheading', 'quiz', $quiz->name);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_pagetype('mod-quiz-section');
$PAGE->navbar->add($streditingquiz);
$PAGE->set_title($streditingquiz);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$returnurl = new moodle_url($returnurl,  array('cmid' => $cmid));

if ($sectionid) {
    // We are editing an existing section.
    //get data from db
    $data = get_data_from_db();
} else {
    // We are adding a new section.
    $data = new stdClass();
    $data->sectionheading = '';
    $data->returnurl = $returnurl;
    $data->courseid = $courseid;
    $data->quizid = $quizid;
}

$mform = new quiz_section_form($returnurl);
$mform->set_data($data);
if ($sectionid) {
    // We are editing the form.
    echo $OUTPUT->heading(get_string('editingquizsection', 'quiz'));
} else {
    // We are adding a new section to the quiz.
    echo $OUTPUT->heading(get_string('addingquizsection', 'quiz'));
}
$mform->display();

if ($mform->is_cancelled()) {
    redirect($returnurl);
    //redirect('/mod/quiz/edit.php');
} else if ($data = $mform->get_data()) {
    if (!empty($data->sectionheading)) {
        add_to_log($quiz->course, 'quiz', 'addsectionheading',
                'view.php?id=' . $cm->id, $categoryid, $cm->id);
        if ($sectionid) {
        // Update db table.
        } else {
            // Insert row to db table.
        }
    } else {
        // Validation method would take care  of this.
        // Section headings cannot be empty.
    }
    redirect($returnurl);
}

//echo $OUTPUT->footer();

