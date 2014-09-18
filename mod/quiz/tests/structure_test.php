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
 * Quiz events tests.
 *
 * @package    mod_quiz
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->dirroot . '/mod/quiz/editlib.php');

/**
 * Unit tests for quiz events.
 *
 * @package    mod_quiz
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_structure_testcase extends advanced_testcase {

    public $sections = array();
    public $defaultslots = array();

    protected function prepare_quiz_data() {

        $this->resetAfterTest(true);

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance(array('course' => $course->id, 'questionsperpage' => 0,
            'grade' => 100.0, 'sumgrades' => 2));

        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);

        return array($quiz, $cm, $course);
    }

    public function test_create() {
        $structure = \mod_quiz\structure::create();

        $this->assertInstanceOf('\mod_quiz\structure', $structure);
    }

    public function test_get_quiz_slots() {
        // Get basic quiz.
        list($quiz, $cm, $course) = $this->prepare_quiz_data();
        $structure = \mod_quiz\structure::create_for($quiz);

        // When no slots exist or slots propery is not set.
        $slots = $structure->get_quiz_slots();
        $this->assertInternalType('array', $slots);
        $this->assertCount(0, $slots);

        // Append slots to the quiz.
        $testslots = $this->get_default_quiz_slots($quiz);
        $structure = \mod_quiz\structure::create_for($quiz);

        // Are the correct slots returned?
        $slots = $structure->get_quiz_slots();
        $this->assertEquals($testslots, $slots);
    }

    public function test_get_quiz_sections() {
        // Get basic quiz.
        list($quiz, $cm, $course) = $this->prepare_quiz_data();
        $structure = \mod_quiz\structure::create_for($quiz);

        // Are the correct sections returned?
        $sections = $structure->get_quiz_sections();
        $this->assertCount(count(1), $sections);
    }

    public function test_move_slot() {
        global $DB;

        list($quiz, $cm, $course) = $this->prepare_quiz_data();
        $structure = \mod_quiz\structure::create_for($quiz);

        // Append slots to the quiz.
        $testslots = $this->reset_slots($quiz, $structure);

        $this->assertInstanceOf('\mod_quiz\structure', $structure);
        $slots = $structure->get_quiz_slots();

        // Slots don't move. Page unchanged.
        $idmove = $this->get_slot_id_by_slot_number('2');
        $idbefore = $this->get_slot_id_by_slot_number('1');
        $structure->move_slot($quiz, $idmove, $idbefore, '2');
        $slotsmoved = $this->get_saved_quiz_slots($quiz, $structure);

        $this->assertEquals($testslots, $slotsmoved);

        // Slots don't move. Page changed.
        $idmove = $this->get_slot_id_by_slot_number('2');
        $idbefore = $this->get_slot_id_by_slot_number('1');
        $structure->move_slot($quiz, $idmove, $idbefore, '1');
        $slotsmoved = $this->get_saved_quiz_slots($quiz, $structure);
        $testslots[$idmove]->page = '1';

        $this->assertEquals($testslots, $slotsmoved);

        $testslots = $this->reset_slots($quiz, $structure);

        // Slots move 2 > 3. Page unchanged. Pages not reordered.
        $idmove = $this->get_slot_id_by_slot_number('2');
        $idbefore = $this->get_slot_id_by_slot_number('3');
        $structure->move_slot($quiz, $idmove, $idbefore, '2');
        $slotsmoved = $this->get_saved_quiz_slots($quiz, $structure);
        $testslots[$idbefore]->slot = '2';
        $testslots[$idmove]->slot = '3';
        $testslots[$idmove]->page = '2';

        $this->assertEquals($testslots, $slotsmoved);

        $testslots = $this->reset_slots($quiz, $structure);

        // Slots move 6 > 7. Page changed. Pages not reordered.
        $idmove = $this->get_slot_id_by_slot_number('6');
        $idbefore = $this->get_slot_id_by_slot_number('7');
        $structure->move_slot($quiz, $idmove, $idbefore, '3');
        $slotsmoved = $this->get_saved_quiz_slots($quiz, $structure);

        // Set test data.
        // Move slot and page.
        $testslots[$idbefore]->slot = '6';
        $testslots[$idmove]->slot = '7';
        $testslots[$idmove]->page = '3';
        $this->assertEquals($testslots, $slotsmoved);

        $testslots = $this->reset_slots($quiz, $structure);

        // Slots unmoved . Page changed slot 6 . Pages not reordered.
        $idmove = $this->get_slot_id_by_slot_number('6');
        $idbefore = $this->get_slot_id_by_slot_number('5');
        $pagenumber = 2;
        $structure->move_slot($quiz, $idmove, $idbefore, strval($pagenumber));
        $slotsmoved = $this->get_saved_quiz_slots($quiz, $structure);

        // Set test data.
        // Move slot and page.

        $testslots[$idmove]->page = strval($pagenumber);
        $this->assertEquals($testslots, $slotsmoved);

        $testslots = $this->reset_slots($quiz, $structure);

        // Slots move 1 > 2. Page changed. Page 2 becomes page 1. Pages reordered.
        $idmove = $this->get_slot_id_by_slot_number('1');
        $idbefore = $this->get_slot_id_by_slot_number('2');
        $structure->move_slot($quiz, $idmove, $idbefore, '2');
        $slotsmoved = $this->get_saved_quiz_slots($quiz, $structure);

        // Set test data.
        // Move slot and page.
        $testslots[$idbefore]->slot = '1';
        $testslots[$idbefore]->page = '1';
        $testslots[$idmove]->slot = '2';
        $testslots[$idmove]->page = '1';

        // Now reorder the pages.
        $pagenumber = 1;
        $slotnumber = 1;
        $testslots[$this->get_slot_id_by_slot_number($slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = ++$pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = ++$pagenumber;
        $this->assertEquals($testslots, $slotsmoved);

        $testslots = $this->reset_slots($quiz, $structure);

        // Slots move 6 > 3. Page changed. Page 2 becomes page 1. Pages reordered.
        $idmove = $this->get_slot_id_by_slot_number('6');
        $idbefore = $this->get_slot_id_by_slot_number('2');
        $pagenumber = 2;

        $structure->move_slot($quiz, $idmove, $idbefore, $pagenumber);
        $slotsmoved = $this->get_saved_quiz_slots($quiz, $structure);

        // Now reorder the pages.
        $pagenumber = 2;
        $slotnumber = 5;
        $this->update_slot_page_and_slot($testslots, $slotnumber, $pagenumber, $slotnumber + 1);
        $this->update_slot_page_and_slot($testslots, -- $slotnumber, $pagenumber, $slotnumber + 1);
        $this->update_slot_page_and_slot($testslots, -- $slotnumber, $pagenumber, $slotnumber + 1);

        $moveslot = $testslots[$idmove];

        // Move slot and page.
        $moveslot->slot = '3';
        $moveslot->page = '2';

        $this->assertEquals($testslots, $slotsmoved);

    }

    /**
     * Test removing slots from a quiz.
     */
    public function test_quiz_remove_slot() {
        global $SITE, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Setup a quiz with 1 standard and 1 random question.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $standardq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));

        quiz_add_quiz_question($standardq->id, $quiz);
        quiz_add_random_questions($quiz, 0, $cat->id, 1, false);

        // Get the random question.
        $randomq = $DB->get_record('question', array('qtype' => 'random'));

        $structure = \mod_quiz\structure::create_for($quiz);

        // Check that the setup looks right.
        $this->assertEquals(2, $structure->get_question_count());
        $this->assertEquals($standardq->id, $structure->get_question_in_slot(1)->questionid);
        $this->assertEquals($randomq->id, $structure->get_question_in_slot(2)->questionid);

        // Remove the standard question.
        $structure->remove_slot($quiz, 1);

        $alteredstructure = \mod_quiz\structure::create_for($quiz);

        // Check the new ordering, and that the slot number was updated.
        $this->assertEquals(1, $alteredstructure->get_question_count());
        $this->assertEquals($randomq->id, $alteredstructure->get_question_in_slot(1)->questionid);

        // Check that the ordinary question was not deleted.
        $this->assertTrue($DB->record_exists('question', array('id' => $standardq->id)));

        // Remove the random question.
        $structure->remove_slot($quiz, 1);
        $alteredstructure = \mod_quiz\structure::create_for($quiz);

        // Check that new ordering.
        $this->assertEquals(0, $alteredstructure->get_question_count());

        // Check that the random question was deleted.
        $this->assertFalse($DB->record_exists('question', array('id' => $randomq->id)));
    }

    public function test_refresh_page_numbers() {
        list($quiz, $cm, $course) = $this->prepare_quiz_data();
        $structure = \mod_quiz\structure::create_for($quiz);

        // Append slots to the quiz.
        $testslots = $this->reset_slots($quiz, $structure);

        $this->assertInstanceOf('\mod_quiz\structure', $structure);
        $slots = $structure->get_quiz_slots();

        // Test: Nothing changed.
        $structure->refresh_page_numbers($quiz, $slots);

        $this->assertEquals($testslots, $slots);

        // Test: Page break added at slot 3. Pages reordered.
        $testslots = $this->reset_slots($quiz, $structure);
        $slots = $structure->get_quiz_slots();

        $idmove = $this->get_slot_id_by_slot_number('3');
        $slotmove = $slots[$idmove]->page = '3';

        $structure->refresh_page_numbers($quiz, $slots);

        // Now reorder the pages in test slots.
        $pagenumber = 3;
        $slotnumber = 3;
        $testslots[$this->get_slot_id_by_slot_number($slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = ++$pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = ++$pagenumber;

        $this->assertEquals($testslots, $slots);

        // Test: Page break removed at slot 1. Pages reordered from the start.
        $testslots = $this->reset_slots($quiz, $structure);
        $slots = $structure->get_quiz_slots();

        $idmove = $this->get_slot_id_by_slot_number('1');
        $slotmove = $slots[$idmove]->page = '2';

        $structure->refresh_page_numbers($quiz, $slots);

        // Now reorder the pages in test slots.
        $pagenumber = 1;
        $slotnumber = 1;
        $testslots[$this->get_slot_id_by_slot_number($slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = $pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = ++$pagenumber;
        $testslots[$this->get_slot_id_by_slot_number(++$slotnumber)]->page = ++$pagenumber;

        $this->assertEquals($testslots, $slots);
    }

    private function update_slot_page_and_slot($slots, $slotnumber, $pagenumber, $slot) {
        $currentslot = $slots[$this->get_slot_id_by_slot_number($slotnumber, $slots)];
        $currentslot->page = $pagenumber;
        $currentslot->slot = $slot;
    }

    /**
     * Get a slot by it's slot number. Throws an exception if it is missing.
     * @return stdClass the requested slot.
     */
    public function get_slot_by_slot_number($slotnumber, $slots=array()) {
        $slotnumber = strval($slotnumber);
        if (!count($slots)) {
            $slots = $this->defaultslots;
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
    public function get_slot_id_by_slot_number($slotnumber, $slots=array()) {
        $slot = $this->get_slot_by_slot_number($slotnumber, $slots);
        if (!$slot) {
            return null;
        }

        return $slot->id;
    }

    public function reset_slots($quiz, $structure) {
        $testslots = $this->get_default_quiz_slots($quiz);
        $structure->set_quiz_slots($testslots);
        $this->save_quiz_slots_to_db($structure);
        $structure->populate_structure($quiz);
        return $structure->get_quiz_slots();
    }

    public function get_saved_quiz_slots($quiz, $structure) {
        $structure->populate_structure($quiz);
        $slots = $structure->get_quiz_slots();
        return $slots;
    }

    public function save_quiz_slots_to_db($structure, array $slots = array()) {
        global $DB;
        $table = 'quiz_slots';
        $quizid = null;

        if (!count($slots)) {
            $slots = $structure->get_quiz_slots();
        }

        $savedslots = $DB->get_records($table);

        $slotreorder = array();
        foreach ($savedslots as $savedslot) {
            if (!$quizid) {
                $quizid = $savedslot->quizid;
            }
            $slotreorder[$savedslot->slot] = $slots[$savedslot->id]->slot;
        }
        update_field_with_unique_index('quiz_slots',
                        'slot', $slotreorder, array('quizid' => $quizid));

        foreach ($slots as $slot) {
            if ($DB->get_field($table, 'id', array('id' => $slot->id))) {
                $DB->update_record($table, $slot);
            } else {
                $DB->insert_record($table, $slot);
            }
        }

        // Get updated slot ids.
        $structure->set_quiz_slots($slots);
    }

    /**
     * Populate quiz slots with dummy data while the database is waiting
     * @param object $quiz
     * @return array
     */
    public function get_default_quiz_slots($quiz) {
        global $DB;

        /*
         * Rows are in the format array(id, quizid, slot, page, questionid, maxmark).
         *
         * $data[] = array($uniqueid++.'', $quiz->id, '1', 1.'', '1', '1.0000000');
         * $data[] = array($uniqueid++.'', $quiz->id, '2', 2.'', '2', '1.0000000');
         * $data[] = array($uniqueid++.'', $quiz->id, '3', 2.'', '3', '1.0000000');
         * $data[] = array($uniqueid++.'', $quiz->id, '4', 2.'', '4', '1.0000000');
         * $data[] = array($uniqueid++.'', $quiz->id, '5', 2.'', '5', '1.0000000');
         * $data[] = array($uniqueid++.'', $quiz->id, '6', 2.'', '6', '1.0000000');
         * $data[] = array($uniqueid++.'', $quiz->id, '7', 3.'', '7', '1.0000000');
         * $data[] = array($uniqueid++.'', $quiz->id, '8', 4.'', '8', '1.0000000');
         */

        // Slots already created return them.
        if ($this->get_default_slots()) {
            return $this->get_default_slots();
        }

        // Create slots.
        $pagenumber = 1;
        $pagenumberdefaults = array(2, 7, 8);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        for ($i = 0; $i < 8; $i ++) {
            $numq = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));

            if (in_array($i + 1, $pagenumberdefaults)) {
                $pagenumber++;
            }
            // Add them to the quiz.
            quiz_add_quiz_question($numq->id, $quiz, $pagenumber);
        }

        $records = $DB->get_records('quiz_slots', array('quizid' => $quiz->id), 'slot');

        foreach ($records as $record) {
            $record->sectionid = 1;
        }

        $this->defaultslots = $records;

        return $this->get_default_slots();
    }

    public function get_default_slots() {
        $slots = array();
        foreach ($this->defaultslots as $slot) {
            $slots[$slot->id] = clone $slot;
        }
        return $slots;
    }
}
