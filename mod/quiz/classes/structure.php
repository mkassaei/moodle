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
 * The structure of the quiz. That is, which questions it is built up
 * from. This is used on the Edit quiz page (edit.php) and also when
 * starting an attempt at the quiz (startattempt.php). Once an attempt
 * has been started, then the attempt holds the specific set of questions
 * that that student should answer, and we no longer use this class.
 *
 * @package   mod_quiz
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz;

use core\plugininfo\theme;
class structure {
    /** Constant to represent splitting two pages. */
    const SPLIT = 'split';
    /** Constant to represent joining two pages. */
    const JOIN  = 'join';

    /** @var \quiz the quiz this is the structure of. */
    protected $quizobj = null;

    /**
     * @var stdClass[] the questions in this quiz. Contains the row from the questions
     * table, with the data from the quiz_slots table added, and also question_categories.contextid.
     */
    protected $questions = array();

    /** @var stdClass[] quiz_slots.id => the quiz_slots rows for this quiz, agumented by sectionid. */
    protected $slots = array();

    /** @var stdClass[] quiz_slots.slot => the quiz_slots rows for this quiz, agumented by sectionid. . */
    protected $slotsinorder = array();

    /**
     * @var stdClass[] currently a dummy. Holds data that will match the
     * quiz_sections, once it exists.
     */
    protected $sections = array();

    /** @var bool caches the results of can_be_edited. */
    protected $canbeedited = null;

    /**
     * Create an instance of this class representing an empty quiz.
     * @return structure
     */
    public static function create() {
        return new self();
    }

    /**
     * Create an instance of this class representing the structure of a given quiz.
     * @return structure
     */
    public static function create_for($quiz) {
        $structure = self::create();
        $structure->populate_structure($quiz);
        return $structure;
    }

    /**
     * Create an instance of this class representing the structure of a given quiz.
     * @param quiz $quizobj the quiz.
     * @return structure
     */
    public static function create_for_quiz($quizobj) {
        $structure = self::create_for($quizobj->get_quiz());
        $structure->quizobj = $quizobj;
        return $structure;
    }

    /**
     * @return boolean whether there are any questions in the quiz.
     */
    public function has_questions() {
        return !empty($this->questions);
    }

    /**
     * @return int the number of questions in the quiz.
     */
    public function get_question_count() {
        return count($this->questions);
    }

    /**
     * Get the information about the question with this id.
     * @param int $questionid The question id.
     * @return \stdClass the data from the questions table, augmented with
     * question_category.contextid, and the quiz_slots data for the question in this quiz.
     */
    public function get_question_by_id($questionid) {
        return $this->questions[$questionid];
    }

    /**
     * Get the information about the question in a given slot.
     * @param int $slotnumber the index of the slot in question.
     * @return \stdClass the data from the questions table, augmented with
     * question_category.contextid, and the quiz_slots data for the question in this quiz.
     */
    public function get_question_in_slot($slotnumber) {
        return $this->questions[$this->slotsinorder[$slotnumber]->questionid];
    }

    /**
     * @return int the course_modules.id for the quiz.
     */
    public function get_cmid() {
        return $this->quizobj->get_cmid();
    }

    /**
     * @return int the quiz.id for the quiz.
     */
    public function get_quizid() {
        return $this->quizobj->get_quizid();
    }

    /**
     * @return \stdClass the quiz settings row from the database.
     */
    public function get_quiz() {
        return $this->quizobj->get_quiz();
    }

    /**
     * @return bool whether the question in the quiz are shuffled for each attempt.
     */
    public function is_shuffled() {
        return $this->quizobj->get_quiz()->shufflequestions;
    }

    /**
     * Quizzes can only be repaginated if they have not been attempted, the
     * questions are not shuffled, and there are two or more questions.
     * @return bool whether this quiz can be repaginated.
     */
    public function can_be_repaginated() {
        return !$this->is_shuffled() && $this->can_be_edited()
                && $this->get_question_count() >= 2;
    }

    /**
     * Quizzes can only be edited if they have not been attempted.
     * @return bool whether the quiz can be edited.
     */
    public function can_be_edited() {
        if ($this->canbeedited === null) {
            $this->canbeedited = !quiz_has_attempts($this->quizobj->get_quizid());
        }
        return $this->canbeedited;
    }

    /**
     * @return int the number of questions that should be on each page of the
     * quiz by default.
     */
    public function get_questions_per_page() {
        return $this->quizobj->get_quiz()->questionsperpage;
    }

    /**
     * @return stdClass[] the slots in this quiz.
     */
    public function get_quiz_slots() {
        return $this->slots;
    }

    /**
     * Is this slot the first one on its page?
     * @param int $slotnumber the index of the slot in question.
     * @return boolean whether this slot the first one on its page.
     */
    public function is_first_slot_on_page($slotnumber) {
        if ($slotnumber == 1) {
            return true;
        }
        return $this->slotsinorder[$slotnumber]->page != $this->slotsinorder[$slotnumber - 1]->page;
    }

    /**
     * Is this slot the last one on its page?
     * @param int $slotnumber the index of the slot in question.
     * @return boolean whether this slot the last one on its page.
     */
    public function is_last_slot_on_page($slotnumber) {
        if (!isset($this->slotsinorder[$slotnumber + 1])) {
            return true;
        }
        return $this->slotsinorder[$slotnumber]->page != $this->slotsinorder[$slotnumber + 1]->page;
    }

    /**
     * Is this slot the last one in the quiz?
     * @param int $slotnumber the index of the slot in question.
     * @return boolean whether this slot the last one in the quiz.
     */
    public function is_last_slot_in_quiz($slotnumber) {
        end($this->slotsinorder);
        return $slotnumber == key($this->slotsinorder);
    }

    /**
     * Get a slot by it's id. Throws an exception if it is missing.
     * @param int $slotid the slot id.
     * @return stdClass the requested quiz_slots row.
     */
    public function get_slot_by_id($slotid) {
        if (!array_key_exists($slotid, $this->slots)) {
            throw new \coding_exception('The \'slotid\' could not be found.');
        }
        return $this->slots[$slotid];
    }

    /**
     * Get all the questions in a section of the quiz.
     * @param int $sectionid the section id.
     * @return array of question/slot objects.
     */
    public function get_questions_in_section($sectionid) {
        $questions = array();
        foreach ($this->slotsinorder as $slot) {
            if ($slot->sectionid == $sectionid) {
                $questions[] = $this->questions[$slot->questionid];
            }
        }
        return $questions;
    }

    /**
     * @return stdClass[] the sections in this quiz.
     */
    public function get_quiz_sections() {
        return $this->sections;
    }

    /**
     * @return array of strings. Warnings to show at the top of the edit page.
     */
    public function get_edit_page_warnings() {
        $warnings = array();

        if (quiz_has_attempts($this->quizobj->get_quizid())) {
            $reviewlink = quiz_attempt_summary_link_to_reports($this->quizobj->get_quiz(),
                    $this->quizobj->get_cm(), $this->quizobj->get_context());
            $warnings[] = get_string('cannoteditafterattempts', 'quiz', $reviewlink);
        }

        if ($this->is_shuffled()) {
            $updateurl = new moodle_url('/course/mod.php',
                    array('return' => 'true', 'update' => $this->quizobj->get_cmid(), 'sesskey' => sesskey()));
            $updatelink = '<a href="'.$updateurl->out().'">' . get_string('updatethis', '',
                    get_string('modulename', 'quiz')) . '</a>';
            $warnings[] = get_string('shufflequestionsselected', 'quiz', $updatelink);
        }

        return $warnings;
    }

    /**
     * Get the date information about the current state of the quiz.
     * @return array of two strings. First a short summary, then a longer
     * explanation of the current state, e.g. for a tool-tip.
     */
    public function get_dates_summary() {
        $timenow = time();
        $quiz = $this->quizobj->get_quiz();

        // Exact open and close dates for the tool-tip.
        $dates = array();
        if ($quiz->timeopen > 0) {
            if ($timenow > $quiz->timeopen) {
                $dates[] = get_string('quizopenedon', 'quiz', userdate($quiz->timeopen));
            } else {
                $dates[] = get_string('quizwillopen', 'quiz', userdate($quiz->timeopen));
            }
        }
        if ($quiz->timeclose > 0) {
            if ($timenow > $quiz->timeclose) {
                $dates[] = get_string('quizclosed', 'quiz', userdate($quiz->timeclose));
            } else {
                $dates[] = get_string('quizcloseson', 'quiz', userdate($quiz->timeclose));
            }
        }
        if (empty($dates)) {
            $dates[] = get_string('alwaysavailable', 'quiz');
        }
        $explanation = implode(', ', $dates);

        // Brief summary on the page.
        if ($timenow < $quiz->timeopen) {
            $currentstatus = get_string('quizisclosedwillopen', 'quiz',
                    userdate($quiz->timeopen, get_string('strftimedatetimeshort', 'langconfig')));
        } else if ($quiz->timeclose && $timenow <= $quiz->timeclose) {
            $currentstatus = get_string('quizisopenwillclose', 'quiz',
                    userdate($quiz->timeclose, get_string('strftimedatetimeshort', 'langconfig')));
        } else if ($quiz->timeclose && $timenow > $quiz->timeclose) {
            $currentstatus = get_string('quizisclosed', 'quiz');
        } else {
            $currentstatus = get_string('quizisopen', 'quiz');
        }

        return array($currentstatus, $explanation);
    }

    /**
     * Populate this class with the structure for a given quiz.
     * @param unknown_type $quiz
     */
    public function populate_structure($quiz) {
        global $DB;

        $slots = $DB->get_records_sql("
                SELECT slot.id AS slotid, slot.slot, slot.questionid, slot.page, slot.maxmark,
                       q.*, qc.contextid
                  FROM {quiz_slots} slot
                  LEFT JOIN {question} q ON q.id = slot.questionid
                  LEFT JOIN {question_categories} qc ON qc.id = q.category
                 WHERE slot.quizid = ?
              ORDER BY slot.slot", array($quiz->id));

        $slots = $this->populate_missing_questions($slots);

        $this->questions = array();
        $this->slots = array();
        $this->slotsinorder = array();
        foreach ($slots as $slotdata) {
            $this->questions[$slotdata->questionid] = $slotdata;

            $slot = new \stdClass();
            $slot->id = $slotdata->slotid;
            $slot->slot = $slotdata->slot;
            $slot->quizid = $quiz->id;
            $slot->page = $slotdata->page;
            $slot->questionid = $slotdata->questionid;
            $slot->maxmark = $slotdata->maxmark;

            $this->slots[$slot->id] = $slot;
            $this->slotsinorder[$slot->slot] = $slot;
        }

        $section = new \stdClass();
        $section->id = 1;
        $section->quizid = $quiz->id;
        $section->heading = '';
        $section->firstslot = 1;
        $section->shuffle = false;
        $this->sections = array(1 => $section);

        $this->populate_slots_with_sectionids($quiz);
        $this->populate_question_numbers();
    }

    protected function populate_missing_questions($slots) {
        // Address missing question types.
        foreach ($slots as $slot) {
            if ($slot->qtype === null) {
                // If the questiontype is missing change the question type.
                $slot->id = $slot->questionid;
                $slot->category = 0;
                $slot->qtype = 'missingtype';
                $slot->name = get_string('missingquestion', 'quiz');
                $slot->slot = $slot->slot;
                $slot->maxmark = 0;
                $slot->questiontext = ' ';
                $slot->questiontextformat = FORMAT_HTML;
                $slot->length = 1;

            } else if (!\question_bank::qtype_exists($slot->qtype)) {
                $slot->qtype = 'missingtype';
            }
        }

        return $slots;
    }

    public function populate_slots_with_sectionids() {
        $nextsection = reset($this->sections);
        foreach ($this->slotsinorder as $slot) {
            if ($slot->slot == $nextsection->firstslot) {
                $currentsectionid = $nextsection->id;
                $nextsection = next($this->sections);
                if (!$nextsection) {
                    $nextsection = new \stdClass();
                    $nextsection->firstslot = -1;
                }
            }

            $slot->sectionid = $currentsectionid;
        }
    }

    protected function populate_question_numbers() {
        $number = 1;
        foreach ($this->slots as $slot) {
            $question = $this->questions[$slot->questionid];
            if ($question->length == 0) {
                $question->displayednumber = get_string('infoshort', 'quiz');
            } else {
                $question->displayednumber = $number;
                $number += 1;
            }
        }
    }

    /**
     * Move a slot from its current location to a new location.
     * Reorder the slot table accordingly.
     * @param stdClass $quiz
     * @param int $id id of slot to be moved
     * @param int $idbefore id of slot to come before slot being moved
     * @param int $page new page number of slot being moved
     * @return array
     */
    public function move_slot($quiz, $idmove, $idbefore, $page) {
        global $DB, $CFG;

        $movingslot = $this->slots[$idmove];

        // Empty target slot means move slot to first.
        if (empty($idbefore)) {
            $targetslot = $this->slotsinorder[1];
        } else {
            $targetslot = $this->slots[$idbefore];
        }
        $hasslotmoved = false;
        $pagehaschanged = false;

        if (empty($movingslot)) {
            throw new moodle_exception('Bad slot ID ' . $idmove);
        }

        // Unit tests convert slot values to strings. Need as int.
        $movingslotnumber = intval($movingslot->slot);
        $targetslotnumber = intval($targetslot->slot);

        $trans = $DB->start_delegated_transaction();
        // Move slots if slots haven't already been moved ignore.
        if ($targetslotnumber - $movingslotnumber !== -1  ) {

            $slotreorder = array();
            if ($movingslotnumber < $targetslotnumber) {
                $hasslotmoved = true;
                $slotreorder[$movingslotnumber] = $targetslotnumber;
                for ($i = $movingslotnumber; $i < $targetslotnumber; $i += 1) {
                    $slotreorder[$i + 1] = $i;
                }
            } else if ($movingslotnumber > $targetslotnumber) {
                $hasslotmoved = true;
                $previousslotnumber = $targetslotnumber + 1;
                $slotreorder[$movingslotnumber] = $previousslotnumber;
                for ($i = $previousslotnumber; $i < $movingslotnumber; $i += 1) {
                    $slotreorder[$i] = $i + 1;
                }
            }

            // Slot has moved record new order.
            if ($hasslotmoved) {
                update_field_with_unique_index('quiz_slots',
                        'slot', $slotreorder, array('quizid' => $quiz->id));
            }
        }

        // Page has changed. Record it.
        if (!$page) {
            $page = 1;
        }

        if (intval($movingslot->page) !== intval($page)) {
            $DB->set_field('quiz_slots', 'page', $page,
                    array('id' => $movingslot->id));
            $pagehaschanged = true;
        }

        // Slot dropped back where it came from.
        if (!$hasslotmoved && !$pagehaschanged) {
            $trans->allow_commit();
            return;
        }

        // Refresh page numbering.
        $slots = $this->refresh_page_numbers_and_update_db($quiz);

        $trans->allow_commit();

        $this->set_quiz_slots($slots);
        $this->populate_slots_with_sectionids($quiz);
    }

    /**
     * Refresh page numbering of quiz slots
     * @param object $quiz the quiz object.
     */
    public function refresh_page_numbers($quiz, $slots=array()) {
        global $DB;
        // Get slots ordered by page then slot.
        if (!count($slots)) {
            $slots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id), 'slot, page');
        }

        // Loop slots. Start Page number at 1 and increment as required.
        $pagenumbers = array('new' => 0, 'old' => 0);

        foreach ($slots as $slot) {
            if ($slot->page !== $pagenumbers['old']) {
                $pagenumbers['old'] = $slot->page;
                ++$pagenumbers['new'];
            }

            if ($pagenumbers['new'] == $slot->page) {
                continue;
            }
            $slot->page = $pagenumbers['new'];
        }

        return $slots;
    }

    public function refresh_page_numbers_and_update_db($quiz) {
        global $DB;
        $slots = $this->refresh_page_numbers($quiz);

        // Record new page order.
        foreach ($slots as $slot) {
            $DB->set_field('quiz_slots', 'page', $slot->page,
                    array('id' => $slot->id));
        }

        return $slots;
    }

    /**
     * Remove a question from a quiz
     * @param object $quiz the quiz object.
     * @param int $questionid The id of the question to be deleted.
     */
    public function remove_slot($quiz, $slotnumber) {
        global $DB;

        $slot = $DB->get_record('quiz_slots', array('quizid' => $quiz->id, 'slot' => $slotnumber));
        $maxslot = $DB->get_field_sql('SELECT MAX(slot) FROM {quiz_slots} WHERE quizid = ?', array($quiz->id));
        if (!$slot) {
            return;
        }

        $trans = $DB->start_delegated_transaction();
        $DB->delete_records('quiz_slots', array('id' => $slot->id));
        for ($i = $slot->slot + 1; $i <= $maxslot; $i++) {
            $DB->set_field('quiz_slots', 'slot', $i - 1,
                    array('quizid' => $quiz->id, 'slot' => $i));
        }

        $qtype = $DB->get_field('question', 'qtype', array('id' => $slot->questionid));
        if ($qtype === 'random') {
            // This function automatically checks if the question is in use, and won't delete if it is.
            question_delete_question($slot->questionid);
        }

        $this->refresh_page_numbers_and_update_db($quiz);

        $trans->allow_commit();
    }

    /**
     * Change the max mark for a slot.
     *
     * Saves changes to the question grades in the quiz_slots table and any
     * corresponding question_attempts.
     * It does not update 'sumgrades' in the quiz table.
     *
     * @param stdClass $slot    row from the quiz_slots table.
     * @param float    $maxmark the new maxmark.
     * @return bool true if the new grade is different from the old one.
     */
    public function update_slot_maxmark($slot, $maxmark) {
        global $DB;

        if (abs($maxmark - $slot->maxmark) < 1e-7) {
            // Grade has not changed. Nothing to do.
            return false;
        }

        $trans = $DB->start_delegated_transaction();
        $slot->maxmark = $maxmark;
        $DB->update_record('quiz_slots', $slot);
        \question_engine::set_max_mark_in_attempts(new \qubaids_for_quiz($slot->quizid),
                $slot->slot, $maxmark);
        $trans->allow_commit();

        return true;
    }

    /**
     * link/unlink a slot to a page.
     *
     * Saves changes to the slot page relationship in the quiz_slots table and reorders the paging
     * for subsequent slots.
     *
     * @param stdClass $slot    row from the quiz_slots table.
     * @param float    $maxmark the new maxmark.
     * @return bool true if the new grade is different from the old one.
     */
    public function link_slot_to_page($quiz, $slotid, $type) {
        global $DB;
        require_once("locallib.php");
        require_once('classes/repaginate.php');
        $quizid = $quiz->id;

        $repagtype = $type;
        $quizslots = $DB->get_records('quiz_slots', array('quizid' => $quizid), 'slot');
        $slot = $quizslots[$slotid];
        $repaginate = new \mod_quiz\repaginate($quizid, $quizslots);
        $repaginate->repaginate($slot->slot, $repagtype);
        $slots = $this->refresh_page_numbers_and_update_db($quiz);

        return $slots;
    }

    /**
     * @return stdClass get the last slot in the quiz.
     */
    public function get_last_slot() {
        return end($this->slotsinorder);
    }

    public function set_quiz_slots(array $slots) {
        $this->slots = $slots;
    }

    public function set_quiz_sections(array $sections) {
        $this->sections = $sections;
    }
}
