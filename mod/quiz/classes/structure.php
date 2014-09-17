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

class structure {
    /** Constant to represent splitting two pages. */
    const SPLIT = 'split';
    /** Constant to represent joining two pages. */
    const JOIN  = 'join';

    /** @var \quiz the quiz this is the structure of. */
    protected $quizobj = null;

    /** @var stdClass[] the quiz_slots rows for this quiz. */
    protected $questions = array();

    /** @var stdClass[] the quiz_slots rows for this quiz. */
    protected $slots = array();

    /**
     * @var stdClass[] will be the quiz_sections rows, once that table exists.
     * For now contains one dummy section.
     */
    protected $sections = array();

    /** @var int[] slot number => section id, for the first slot in each section. */
    protected $slottosectionids = array();

    /** @var int[][] section number => slot ids, the slots in each section. */
    protected $sectiontoslotids = array();

    /** @var int[] slot number => slot id. */
    protected $slottoslotids = array();

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
     * @return stdClass Retrieve a quiz from the DB with the given id.
     */
    public function load_quiz($quizid) {
        global $DB;
        return $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
    }

    public function has_questions() {
        return !empty($this->questions);
    }

    public function get_question_count() {
        return count($this->questions);
    }

    public function get_question_by_id($questionid) {
        return $this->questions[$questionid];
    }

    public function get_cmid() {
        return $this->quizobj->get_cmid();
    }

    public function get_quizid() {
        return $this->quizobj->get_quizid();
    }

    public function get_quiz() {
        return $this->quizobj->get_quiz();
    }

    public function is_shuffled() {
        return $this->quizobj->get_quiz()->shufflequestions;
    }

    public function can_be_repaginated() {
        return !$this->is_shuffled() && $this->can_be_edited()
                && $this->get_question_count() >= 2;
    }

    public function can_be_edited() {
        if ($this->canbeedited === null) {
            $this->canbeedited = !quiz_has_attempts($this->quizobj->get_quizid());
        }
        return $this->canbeedited;
    }

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
     * @return stdClass the requested slot.
     */
    public function get_slot_by_id($slotid, $slots = array()) {
        if (!count($slots)) {
            $slots = $this->slots;
        }
        if (!array_key_exists($slotid, $slots)) {
            throw new \coding_exception('The \'slotid\' could not be found.');
        }
        return $slots[$slotid];
    }

    /**
     * Get all the questions in a section of the quiz.
     * @param int $sectionid the section id.
     * @return array of question/slot objects.
     */
    public function get_questions_in_section($sectionid) {
        $questions = array();
        $slots = $this->get_quiz_slots();
        $sectiontoslotids = $this->get_sections_and_slots();
        if (!empty($sectiontoslotids[$sectionid])) {
            foreach ($sectiontoslotids[$sectionid] as $slotid) {
                $slot = $slots[$slotid];
                $questionnumber = $slot->questionid;
                $questions[] = $this->get_question_by_id($questionnumber);
            }
        }
        return $questions;
    }

    /**
     * Get a slot by it's slot number. Throws an exception if it is missing.
     * @return stdClass the requested slot.
     */
    public function get_slot_by_slot_number($slotnumber, $slots = array()) {
        $slotnumber = strval($slotnumber);
        if (!count($slots)) {
            $slots = $this->slots;
        }
        foreach ($slots as $slot) {
            if ($slot->slot !== $slotnumber) {
                continue;
            }

            return $slot;
        }

        return null;
    }

    /**
     * Get a slotid by it's slot number. Throws an exception if it is missing.
     * @return stdClass the requested slot.
     */
    public function get_slot_id_by_slot_number($slotnumber, $slots = array()) {
        $slot = $this->get_slot_by_slot_number($slotnumber, $slots);
        if (!$slot) {
            return null;
        }

        return $slot->id;
    }

    /**
     * @return stdClass[] the sections in this quiz.
     */
    public function get_quiz_sections() {
        return $this->sections;
    }

    /**
     * @return int[][] the slots in each section.
     */
    public function get_sections_and_slots() {
        return $this->sectiontoslotids;
    }

    public function get_quiz_section_heading($section) {
        if (!property_exists($section, 'heading')) {
            return '';
        }
        return $section->heading;
    }

    /**
     * Populate this class with the structure for a given quiz.
     * @param unknown_type $quiz
     */
    public function populate_structure($quiz) {
        global $DB;

        $this->questions = $DB->get_records_sql(
                "SELECT q.*, qc.contextid, slot.id AS slotid, slot.maxmark, slot.slot, slot.page
                   FROM {question} q
                   JOIN {question_categories} qc ON qc.id = q.category
                   JOIN {quiz_slots} slot ON slot.questionid = q.id
                  WHERE slot.quizid = ?", array($quiz->id));

        $this->slots = $DB->get_records('quiz_slots',
                array('quizid' => $quiz->id), 'slot');
        $this->slotsinorder = array();
        foreach ($this->slots as $slot) {
            $this->slotsinorder[$slot->slot] = $slot;
        }

        $this->sections = array(
            1 => (object) array('id' => 1, 'quizid' => $quiz->id,
                    'heading' => 'Section 1', 'firstslot' => 1, 'shuffle' => false)
        );

        $this->populate_slot_to_sectionids($quiz);
        $this->populate_slots_with_sectionids($quiz);
        $this->populate_missing_questions();
        $this->populate_question_numbers();
    }

    public function populate_slot_to_sectionids($quiz) {
        foreach ($this->sections as $section) {
            $this->slottosectionids[$section->firstslot] = $section->id;
        }
    }

    public function populate_slots_with_sectionids($quiz) {
        $slots = $this->get_quiz_slots($quiz);
        $sectionid = 0;
        $sectiontoslotids = array();
        $currentslottosectionid = 1;
        foreach ($slots as $slot) {
            if (array_key_exists($slot->slot, $this->slottosectionids)) {
                $sectionid = $this->slottosectionids[$slot->slot];
            }

            $slot->sectionid = $sectionid;
            if (!array_key_exists($slot->sectionid, $sectiontoslotids)) {
                $sectiontoslotids[$slot->sectionid] = array();
            }

            $sectiontoslotids[$slot->sectionid][] = $slot->id;
        }

        $this->sectiontoslotids = $sectiontoslotids;
    }

    public function create_slot_to_slotids($slots) {
        $slottoslotids = array();
        foreach ($slots as $slot) {
            $slottoslotids[$slot->slot] = $slot->id;
        }
        return $slottoslotids;
    }

    protected function populate_missing_questions() {
        // Address missing question types.
        foreach ($this->slots as $slot) {
            $questionid = $slot->questionid;

            // If the questiontype is missing change the question type.
            if (!array_key_exists($questionid, $this->questions)) {
                $fakequestion = new \stdClass();
                $fakequestion->id = $questionid;
                $fakequestion->category = 0;
                $fakequestion->qtype = 'missingtype';
                $fakequestion->name = get_string('missingquestion', 'quiz');
                $fakequestion->slot = $slot->slot;
                $fakequestion->maxmark = 0;
                $fakequestion->questiontext = ' ';
                $fakequestion->questiontextformat = FORMAT_HTML;
                $fakequestion->length = 1;
                $this->questions[$questionid] = $fakequestion;

            } else if (!\question_bank::qtype_exists($this->questions[$questionid]->qtype)) {
                $this->questions[$questionid]->qtype = 'missingtype';
            }
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

        $slottoslotids = $this->create_slot_to_slotids($this->slots);
        $movingslot = $this->slots[$idmove];

        // Empty target slot means move slot to first.
        if (empty($idbefore)) {
            $targetslot = $this->slots[$slottoslotids[1]];
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
     * Refresh slot numbering of quiz slots
     * Required after deleting slots.
     * @param object $quiz the quiz object.
     */
    public function refresh_slot_numbers($quiz, $slots=array()) {
        $slottoslotids = $this->create_slot_to_slotids($slots);
        $slotnumber = 1;
        foreach ($slottoslotids as $slottoslotid) {
            $slots[$slottoslotid]->slot = $slotnumber;
            $slotnumber++;
        }

        return $slots;
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

    public function get_last_slot() {
        $slots = $this->get_quiz_slots();
        $keys = array_keys($slots);
        $id = array_pop($keys);
        return $slots[$id];
    }

    public function set_quiz_slots(array $slots) {
        $this->slots = $slots;
    }

    public function set_quiz_sections(array $sections) {
        $this->sections = $sections;
    }

    public function set_quiz_slottoslotids(array $slottoslotids) {
        $this->slottoslotids = $slottoslotids;
    }

    public function find_slot_by_slotnumber($slots, $slotnumber) {
        foreach ($slots as $slot) {
            if ($slot->slot !== $slotnumber) {
                continue;
            }
            return $slot;
        }
    }

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
}
