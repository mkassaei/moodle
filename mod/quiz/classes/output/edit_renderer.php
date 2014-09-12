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
 * Renderer outputting the quiz editing UI.
 *
 * @package mod_quiz
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz\output;

defined('MOODLE_INTERNAL') || die();

use \mod_quiz\structure;
use \html_writer;

/**
 * Renderer outputting the quiz editing UI.
 *
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.7
 */
class edit_renderer extends \plugin_renderer_base {

    /**
     * Generates the edit page
     *
     * @param stdClass $course The course entry from DB
     * @param array $quiz Array containing quiz data
     * @param mod_quiz\structure $structure
     * @param stdClass $cm Course_modules row.
     * @param \question_edit_contexts $contexts the relevant question bank contexts.
     * @param \moodle_url $pageurl the URL to reload this page.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @return string HTML to output.
     */
    public function edit_page(\quiz $quizobj, structure  $structure,
            \question_edit_contexts $contexts, \moodle_url $pageurl, array $pagevars) {
        global $DB;
        $output = '';

        // Page title.
        $output .= $this->heading_with_help(get_string('editingquizx', 'quiz',
                format_string($quizobj->get_quiz_name())), 'editingquiz', 'quiz', '',
                get_string('basicideasofquiz', 'quiz'), 2);

        // Information at the top.
        $output .= $this->quiz_state_warnings($structure);
        $output .= $this->quiz_information($structure);
        $output .= $this->maximum_grade_input($quizobj->get_quiz(), $this->page->url);
        $output .= $this->repaginate_button($structure, $pageurl);
        $output .= $this->total_marks($quizobj->get_quiz());

        // If the quiz is empty, display an add menu.
        if (!$structure->has_questions()) {
            $output .= html_writer::tag('span', $this->add_menu_actions(0,
                    $pageurl, $contexts, $pagevars, $quizobj->get_course(),
                        $quizobj->get_cm(), $quizobj->get_quiz()), array('class' => 'add-menu-outer'));
        }

        // Show the questions organised into sections and pages.
        $output .= $this->start_section_list();

        foreach ($structure->get_quiz_sections() as $section) {
            $output .= $this->start_section($section);
            $output .= $this->questions_in_section($structure, $section, $contexts, $pagevars, $pageurl);
            $output .= $this->end_section();
        }

        $output .= $this->end_section_list();

        // Add all the required JavaScript, and pop-up HTML.
        $context = $quizobj->get_context();
        $canaddfromqbank = has_capability('moodle/question:useall', $context);
        $qbankoptions = array('class' => 'questionbank', 'cmid' => $structure->get_cmid());
        if ($canaddfromqbank) {
            $this->page->requires->yui_module('moodle-mod_quiz-quizquestionbank', 'M.mod_quiz.quizquestionbank.init', $qbankoptions);
        }

        // Add the form for random question.
        $canaddrandom = has_capability('moodle/question:useall', $context);
        if ($canaddrandom) {
            $this->page->requires->yui_module('moodle-mod_quiz-randomquestion', 'M.mod_quiz.randomquestion.init');
        }

        $qtypes = \question_bank::get_all_qtypes();
        $qtypenamesused = array();
        foreach ($qtypes as $qtypename => $qtypedata) {
            $qtypenamesused[$qtypename] = $qtypename;
        }
        // Include course AJAX.
        quiz_edit_include_ajax($quizobj->get_course(), $quizobj->get_quiz(), $qtypenamesused);

        // Include course format js module.
        $this->page->requires->js('/mod/quiz/yui/edit.js');

        $output .= $this->question_chooser();

        // Call random question form.
        if ($structure->can_be_edited()) {
            $output .= '<div class="mod_quiz_edit_forms">';
            $output .= $this->question_bank_loading();
            $output .= $this->random_question_form($pageurl, $contexts, $pagevars, $quizobj->get_cm());
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Render any warnings that might be required about the state of the quiz,
     * e.g. if it has been attempted, or if the shuffle questions option is
     * turned on.
     *
     * @param structure $structure The quiz structure.
     */
    public function quiz_state_warnings(structure $structure) {
        $warnings = $structure->get_edit_page_warnings();

        if (empty($warnings)) {
            return '';
        }

        return $this->box('<p>' . implode('</p><p>', $warnings) . '</p>', 'statusdisplay');
    }

    /**
     * Render the status bar.
     *
     * @param structure $structure The quiz structure.
     */
    public function quiz_information(structure $structure) {
        list($currentstatus, $explanation) = $structure->get_dates_summary();

        $output = html_writer::span(
                    get_string('numquestionsx', 'quiz', $structure->get_question_count()),
                    'numberofquestions') . ' | ' .
                html_writer::span($currentstatus, 'quizopeningstatus',
                    array('title' => $explanation));

        return html_writer::div($output, 'statusbar');
    }

    /**
     * Render the form for setting a quiz' overall grade
     *
     * @param object $quiz The quiz object of the quiz in question
     * @param object $pageurl The url of the current page with the parameters required
     *     for links returning to the current page, as a \moodle_url object
     * @param int $tabindex The tabindex to start from for the form elements created
     * @return int The tabindex from which the calling page can continue, that is,
     *      the last value used +1.
     */
    public function maximum_grade_input($quiz, $pageurl) {
        $o = '';
        $o .= html_writer::start_tag('div', array('class' => 'maxgrade'));
        $o .= html_writer::start_tag('form', array('method' => 'post', 'action' => 'edit.php',
                'class' => 'quizsavegradesform'));
        $o .= html_writer::start_tag('fieldset', array('class' => 'invisiblefieldset'));
        $o .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        $o .= html_writer::input_hidden_params($pageurl);
        $a = html_writer::empty_tag('input', array('type' => 'text', 'id' => 'inputmaxgrade',
                'name' => 'maxgrade', 'size' => ($quiz->decimalpoints + 2),
                'value' => quiz_format_grade($quiz, $quiz->grade)));
        $o .= html_writer::tag('label', get_string('maximumgradex', '', $a),
                array('for' => 'inputmaxgrade'));
        $o .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'savechanges', 'value' => get_string('save', 'quiz')));
        $o .= html_writer::end_tag('fieldset');
        $o .= html_writer::end_tag('form');
        $o .= html_writer::end_tag('div');
        return $o;
    }

    /**
     * Return the repaginate button
     * @param structure $structure the structure of the quiz being edited.
     */
    protected function repaginate_button(structure $structure, \moodle_url $pageurl) {

        if ($structure->can_be_repaginated()) {
            $repaginatingdisabledhtml = '';
        } else {
            $repaginatingdisabledhtml = 'disabled="disabled"';
        }

        $header = html_writer::tag('span', get_string('repaginatecommand', 'quiz'), array('class' => 'repaginatecommand'));
        $form = $this->repaginate_form($structure, $pageurl);
        $options = array('cmid' => $structure->get_cmid(), 'header' => $header, 'form' => $form);

        $rpbutton = '<input id="repaginatecommand"' . $repaginatingdisabledhtml .
        ' type="submit" name="repaginate" value="'. get_string('repaginatecommand', 'quiz') . '"/>';
        $rpcontainer = html_writer::tag('div', $rpbutton,
                array_merge(array('class' => 'rpcontainerclass'), $options));

        if (!$repaginatingdisabledhtml) {
            $this->page->requires->yui_module('moodle-mod_quiz-repaginate', 'M.mod_quiz.repaginate.init');
        }

        return $rpcontainer;
    }

    /**
     * Return the repaginate form
     * @param object $cm
     * @param object $quiz
     * @param object $pageurl
     * @param int $max, maximum number of questions per page
     */
    protected function repaginate_form(structure $structure, \moodle_url $pageurl) {
        $perpage = array();
        $perpage[0] = get_string('allinone', 'quiz');
        for ($i = 1; $i <= 50; ++$i) {
            $perpage[$i] = $i;
        }

        $select = html_writer::select($perpage, 'questionsperpage',
                $structure->get_questions_per_page(), false);

        $gostring = get_string('go');

        $formcontent = '<div class="bd">' .
                '<form action="edit.php" method="post">' .
                '<fieldset class="invisiblefieldset">' .
                html_writer::input_hidden_params($pageurl) .
                '<input type="hidden" name="sesskey" value="'.sesskey().'" />' .
                '<input type="hidden" name="repaginate" value="1" />' .
                get_string('repaginate', 'quiz', $select) .
                '<div class="quizquestionlistcontrols">' .
                ' <input type="submit" name="repaginate" value="'. get_string('go') . '"  />' .
                '</div></fieldset></form></div>';

        return html_writer::tag('div', $formcontent, array('id' => 'repaginatedialog'));
    }

    /**
     * Render the total marks available for the quiz.
     *
     * @param object $quiz The quiz object of the quiz in question
     */
    public function total_marks($quiz) {
        return html_writer::tag('span',
                get_string('totalmarksx', 'quiz', quiz_format_grade($quiz, $quiz->sumgrades)),
                array('class' => 'totalpoints'));
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'slots'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The quiz_section entry from DB
     * @return string HTML to output.
     */
    protected function start_section($section) {

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        $o .= html_writer::start_tag('li', array('id' => 'section-'.$section->id,
            'class' => 'section main clearfix'.$sectionstyle, 'role' => 'region',
            'aria-label' => $section->heading));

        $leftcontent = $this->section_left_content($section);
        $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section);
        $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        return $o;
    }

    /**
     * Generate the display of the footer part of a section
     *
     * @return string HTML to output.
     */
    protected function end_section() {
        $o = html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');

        return $o;
    }

    /**
     * Generate the content to be displayed on the left part of a section
     * before course modules are included
     *
     * @param stdClass $section The quiz_section entry from DB
     * @return string HTML to output.
     */
    protected function section_left_content($section) {
        $o = $this->output->spacer();

        return $o;
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param stdClass $section The quiz_section entry from DB
     * @return string HTML to output.
     */
    protected function section_right_content($section) {
        $o = $this->output->spacer();
        return $o;
    }

    /**
     * Renders HTML to display a list of course modules in a course section
     * Also displays "move here" controls in Javascript-disabled mode
     *
     * This function calls {@link core_course_renderer::quiz_section_question()}
     *
     * @param stdClass $course course object
     * @param int|stdClass|section_info $section relative section number or section object
     * @param int $sectionreturn section number to return to
     * @return void
     */
    public function questions_in_section(structure $structure, $section,
            $contexts, $pagevars, $pageurl) {
        $output = '';

        // Get the list of question types visible to user (excluding the question type being moved if there is one).
        $questionshtml = array();

        $slots = $structure->get_quiz_slots();
        $sectiontoslotids = $structure->get_sections_and_slots();
        if (!empty($sectiontoslotids[$section->id])) {
            foreach ($sectiontoslotids[$section->id] as $slotid) {
                $slot = $slots[$slotid];
                $questionnumber = $slot->questionid;
                $question = $structure->get_question_by_id($questionnumber);

                $output .= $this->question_row($structure, $question, $contexts, $pagevars, $pageurl);
            }
        }

        // Always output the section module list.
        return html_writer::tag('ul', $output, array('class' => 'section img-text'));
    }

    /**
     * Renders HTML to display one course module for display within a section.
     *
     * This function calls:
     * {@link core_course_renderer::quiz_section_question()}
     *
     * @param stdClass $course
     * @param cm_info $question
     * @param int|null $sectionreturn
     * @return String
     */
    public function question_row(structure $structure, $question, $contexts, $pagevars, $pageurl) {
        $output = '';
        $slotid = $this->get_question_info($structure, $question->id, 'slotid');
        $slotnumber = $this->get_question_info($structure, $question->id, 'slot');
        $pagenumber = $this->get_question_info($structure, $question->id, 'page');
        $page = $pagenumber ? get_string('pageshort', 'quiz') . ' ' . $pagenumber : null;
        $pagealt = $pagenumber ? get_string('page') . ' ' . $pagenumber : null;
        // Put page in a span for easier styling.
        $page = html_writer::tag('span', $page, array('class' => 'text'));

        $pagenumberclass = 'pagenumber';
        $dragdropclass = 'activity yui3-dd-drop';
        $prevpage = $this->get_previous_page($structure, $slotnumber - 1);
        $nextpage = $this->get_previous_page($structure, $slotnumber + 1);
        $linkpage = 2; // Unlink.
        if ($prevpage != $pagenumber) {
            // Add the add-menu at the page level.
            $addmenu = html_writer::tag('span', $this->add_menu_actions($structure,
                    $pagenumber, $pageurl, $contexts, $pagevars),
                    array('class' => 'add-menu-outer'));

            // Add the form for the add new question chooser dialogue.
            $addquestionurl = new \moodle_url('/question/addquestion.php');
            $questioncategoryid = question_get_category_id_from_pagevars($pagevars);

            // Form fields.
            $addquestionformhtml = html_writer::tag('input', null,
                    array('type' => 'hidden', 'name' => 'returnurl',
                            'value' => '/mod/quiz/edit.php?cmid='.$structure->get_cmid().'&amp;addonpage=' . $pagenumber));
            $addquestionformhtml .= html_writer::tag('input', null,
                    array('type' => 'hidden', 'name' => 'cmid', 'value' => $structure->get_cmid()));
            $addquestionformhtml .= html_writer::tag('input', null,
                    array('type' => 'hidden', 'name' => 'appendqnumstring', 'value' => 'addquestion'));
            $addquestionformhtml .= html_writer::tag('input', null,
                    array('type' => 'hidden', 'name' => 'category', 'value' => $questioncategoryid));
            $addquestionformhtml .= html_writer::tag('div', $addquestionformhtml);

            // Form.
            $addquestionformhtml .= html_writer::tag('form', $addquestionformhtml,
                    array('class' => 'addnewquestion', 'method' => 'post', 'action' => $addquestionurl));

            $output .= html_writer::tag('li', $page.$addmenu.$addquestionformhtml,
                    array('class' => $pagenumberclass . ' ' . $dragdropclass.' page', 'id' => 'page-' . $pagenumber,
                            'title' => $pagealt));
        }

        if ($nextpage != $pagenumber) {
            $linkpage = 1; // Link.
        }

        if ($questionhtml = $this->question($structure, $question, $pageurl)) {
            $questionclasses = 'activity ' . $question->qtype . ' qtype_' . $question->qtype . ' slot';
            $output .= html_writer::tag('li', $questionhtml, array('class' => $questionclasses, 'id' => 'slot-' . $slotid));
        }

        $lastslot = $structure->get_last_slot();
        if ($lastslot->id != $slotid) {
            // Add pink page button.
            $joinhtml = page_split_join_button($structure->get_quiz(), $question, $linkpage);
            $pagebreakclass = $linkpage == 1 ? 'break' : '';
            $output .= html_writer::tag('li', $joinhtml, array('class' => $dragdropclass.' page_join '.$pagebreakclass));
        }

        return $output;
    }

    /**
     * Returns the add menu.
     * @param int $page the page the question will be added on.
     * @param \moodle_url $thispageurl the URL to reload this page.
     * @param \question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @param stdClass $course the course settings.
     * @param stdClass $cm course_modules row.
     * @param stdClass $quiz the quiz settings.
     * @return string HTML for the menu.
     */
    public function add_menu_actions(structure $structure, $page, \moodle_url $pageurl,
            \question_edit_contexts $contexts, array $pagevars) {

        $actions = $this->edit_menu_actions($structure, $page, $pageurl, $contexts, $pagevars);
        if (empty($actions)) {
            return '';
        }
        $menu = new \action_menu();
        $menu->set_alignment(\action_menu::BR, \action_menu::BR);
        $trigger = html_writer::tag('span', get_string('add', 'quiz'), array('class' => 'add-menu'));
        $menu->set_menu_trigger($trigger);

        // Disable the link if quiz has attempts.
        if (!$structure->can_be_edited()) {
            return $this->render($menu);
        }

        foreach ($actions as $action) {
            if ($action instanceof \action_menu_link) {
                $action->add_class('add-menu');
            }
            $menu->add($action);
        }
        $menu->attributes['class'] .= ' page-add-actions commands';

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        return $this->render($menu);
    }

    /**
     * Returns the list of adding actions.
     * @param int $page the page the question will be added on.
     * @param \moodle_url $thispageurl the URL to reload this page.
     * @param \question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @param stdClass $course the course settings.
     * @param stdClass $cm course_modules row.
     * @param stdClass $quiz the quiz settings.
     * @return array the actions.
     */
    public function edit_menu_actions(structure $structure, $page, \moodle_url $pageurl,
            \question_edit_contexts $contexts, array $pagevars) {
        $questioncategoryid = question_get_category_id_from_pagevars($pagevars);
        static $str;
        if (!isset($str)) {
            $str = get_strings(array('addaquestion', 'addarandomquestion',
                    'addarandomselectedquestion', 'questionbank'), 'quiz');
        }

        // Get section, page, slotnumber and maxmark.
        $actions = array();

        // Add a new question to the quiz.
        $returnurl = new \moodle_url($pageurl, array('addonpage' => $page));
        $params = array('returnurl' => $returnurl->out_as_local_url(false),
                'cmid' => $structure->get_cmid(), 'category' => $questioncategoryid,
                'addonpage' => $page, 'appendqnumstring' => 'addquestion');

        $actions['addaquestion'] = new \action_menu_link_secondary(
            new \moodle_url('/question/addquestion.php', $params),
            new \pix_icon('t/add', $str->addaquestion, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $str->addaquestion, array('class' => 'cm-edit-action addquestion', 'data-action' => 'addquestion')
        );

        // Call question bank.
        $icon = new \pix_icon('t/add', $str->questionbank, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $title = get_string('addquestionfrombanktopage', 'quiz', $page);
        $attributes = array('class' => 'cm-edit-action questionbank',
                'data-header' => $title, 'data-action' => 'questionbank', 'data-addonpage' => $page);
        $actions['questionbank'] = new \action_menu_link_secondary($pageurl, $icon, $str->questionbank, $attributes);

        // Add a random question.
        $returnurl = new \moodle_url('/mod/quiz/edit.php', array('cmid' => $structure->get_cmid(), 'data-addonpage' => $page));
        $params = array('returnurl' => $returnurl, 'cmid' => $structure->get_cmid(), 'appendqnumstring' => 'addarandomquestion');
        $url = new \moodle_url('/mod/quiz/addrandom.php', $params);
        $icon = new \pix_icon('t/add', $str->addarandomquestion, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $attributes = array('class' => 'cm-edit-action addarandomquestion', 'data-action' => 'addarandomquestion');
        $title = get_string('addrandomquestiontopage', 'quiz', $page);
        $attributes = array_merge(array('data-header' => $title, 'data-addonpage' => $page), $attributes);
        $actions['addarandomquestion'] = new \action_menu_link_secondary($url, $icon, $str->addarandomquestion, $attributes);

        return $actions;
    }

    /**
     * Renders HTML to display one question in a quiz section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * @param stdClass $course
     * @param cm_info $question
     * @param int|null $sectionreturn
     * @return string
     */
    public function question(structure $structure, $question, $pageurl) {
        $output = '';

        $output .= html_writer::start_tag('div');

        // Print slot number.
        $slotnumber = $this->get_question_info($structure, $question->id, 'slot');

        if ($structure->can_be_edited()) {
            $output .= $this->question_move_icon($question);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));
        $output .= html_writer::tag('span', $question->displayednumber, array('class' => 'slotnumber'));

        // This div is used to indent the content.
        $output .= html_writer::div('', 'mod-indent');

        // Start a wrapper for the actual content to keep the indentation consistent.
        $output .= html_writer::start_tag('div');

        // Display the link to the question (or do nothing if question has no url).
        $cmname = $this->question_name($structure, $question, $pageurl);

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $cmname;

            $questionicons = '';
            $questionicons .= quiz_question_preview_button($structure->get_quiz(), $question);

            // You cannot delete questions when quiz has been attempted,
            // display delete ion only when there is no attepts.
            if ($structure->can_be_edited()) {
                $questionicons .= quiz_question_delete_button($structure->get_quiz(), $question);
            }

            $questionicons .= $this->marked_out_of_field($structure->get_quiz(), $question);
            $questionicons .= ' ' . $this->regrade_action($question);

            $output .= html_writer::span($questionicons, 'actions'); // Required to add js spinner icon.

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance.
        }

        $output .= html_writer::end_tag('div'); // ...$indentclasses.

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Returns the move action.
     *
     * @param object $question The module to produce a move button for
     * @return The markup for the move action, or an empty string if not available.
     */
    public function question_move_icon($question) {
        static $str;
        static $baseurl;

        if (!isset($str)) {
            $str = get_strings(array('move'));
        }

        if (!isset($baseurl)) {
            $baseurl = new \moodle_url('/course/mod.php', array('sesskey' => sesskey()));
        }

        return html_writer::link(
            new \moodle_url($baseurl, array('copy' => $question->id)),
            $this->pix_icon('i/dragdrop', $str->move, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            array('class' => 'editing_move', 'data-action' => 'move')
        );
    }

    /**
     * Renders html to display a name with the link to the question on a quiz edit page
     *
     * If question is unavailable for the user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for question that never have separate pages (i.e. labels)
     * this function returns an empty string
     *
     * @param question $question
     * @return string
     */
    public function question_name(structure $structure, $question, $pageurl) {
        $output = '';

        $editurl = new \moodle_url('/question/question.php', array(
                'returnurl' => $pageurl->out_as_local_url(),
                'cmid' => $structure->get_cmid(), 'id' => $question->id));

        // Accessibility: for files get description via icon, this is very ugly hack!
        $instancename = quiz_question_tostring($question);
        $altname = $question->name;

        $qtype = \question_bank::get_qtype($question->qtype, false);
        $namestr = $qtype->local_name();

        $icon = $this->pix_icon('icon', $namestr, $qtype->plugin_name(), array('title' => $namestr,
                'class' => 'icon activityicon', 'alt' => ' ', 'role' => 'presentation'));
        // Need plain question name without html tags for link title.
        $title = shorten_text(format_string($question->name), 100);
        // Display the link itself.
        $activitylink = $icon . html_writer::tag('span', $instancename, array('class' => 'instancename'));
        $output .= html_writer::link($editurl, $activitylink,
                array('title' => get_string('editquestion', 'quiz').' '.$title));
        return $output;
    }

    /**
     * @param object $quiz The quiz object of the quiz in question
     * @param object $question the question
     * @return the HTML for a marked out of question grade field.
     */
    public function marked_out_of_field($quiz, $question) {
        return html_writer::span(quiz_format_question_grade($quiz, $question->maxmark), 'instancemaxmark',
                array('title'=>get_string('maxmark', 'quiz')));
    }

    /**
     * Returns the regrade action.
     *
     * @param stdClass $question The question to produce editing buttons for
     * @param int $sr The section to link back to (used for creating the links)
     * @return The markup for the regrade action, or an empty string if not available.
     */
    public function regrade_action($question) {
        return html_writer::span(
            html_writer::link(
                new \moodle_url('#'),
                $this->pix_icon('t/editstring', '', 'moodle', array('class' => 'iconsmall visibleifjs', 'title' => '')),
                array(
                    'class' => 'editing_maxmark',
                    'data-action' => 'editmaxmark',
                    'title' => get_string('editmaxmark', 'quiz'),
                )
            )
        );
    }

    /**
     * Render the question chooser dialogue.
     */
    public function question_chooser() {
        $container = html_writer::tag('div', print_choose_qtype_to_add_form(array(), null, false),
                array('id' => 'qtypechoicecontainer'));
        return html_writer::tag('div', $container, array('class' => 'createnewquestion'));
    }

    /**
     * Return the questionbank form
     * @param int $page the page the question will be added on.
     * @param \moodle_url $thispageurl the URL to reload this page.
     * @param \question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @param stdClass $course the course settings.
     * @param stdClass $cm course_modules row.
     * @param stdClass $quiz the quiz settings.
     * @return array with two elements. The question bank pop-up header and contents.
     */
    public function question_bank_loading() {
        return html_writer::tag('div', html_writer::empty_tag('img',
                array('alt' => 'loading', 'class' => 'loading-icon', 'src' => $this->pix_url('i/loading'))),
                array('class' => 'questionbankloading'));
    }

    /**
     * Return random question form.
     * @param \moodle_url $thispageurl the URL to reload this page.
     * @param \question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @param stdClass $cm course_modules row.
     */
    protected function random_question_form(\moodle_url $thispageurl, \question_edit_contexts $contexts, array $pagevars, $cm) {

        if (!$contexts->have_cap('moodle/question:useall')) {
            return '';
        }

        $randomform = new \quiz_add_random_form(new \moodle_url('/mod/quiz/addrandom.php'), $contexts);
        $randomform->set_data(array(
                'category' => $pagevars['cat'],
                'returnurl' => $thispageurl->out_as_local_url(true),
                'cmid' => $cm->id
        ));
        return html_writer::tag('div', $randomform->render(), array('class' => 'randomquestionformforpopup'));
    }

    /**
     * Return the questionbank form
     * @param int $page the page the question will be added on.
     * @param \moodle_url $thispageurl the URL to reload this page.
     * @param \question_edit_contexts $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@link question_edit_setup()}.
     * @param stdClass $course the course settings.
     * @param stdClass $cm course_modules row.
     * @param stdClass $quiz the quiz settings.
     * @return array with two elements. The question bank pop-up header and contents.
     */
    public function get_questionbank_contents(\moodle_url $thispageurl,
            \question_edit_contexts $contexts, array $pagevars, $course, $cm, $quiz) {

        // Create quiz question bank view.
        $questionbank = new \quiz_question_bank_view($contexts, $thispageurl, $course, $cm, $quiz);
        $questionbank->set_quiz_has_attempts(quiz_has_attempts($quiz->id));

        $output = $questionbank->render('editq',
                                        $pagevars['qpage'],
                                        $pagevars['qperpage'],
                                        $pagevars['cat'],
                                        $pagevars['recurse'],
                                        $pagevars['showhidden'],
                                        $pagevars['qbshowtext']);
        $form = html_writer::tag('div', $output, array('class' => 'bd'));
        return html_writer::tag('div', $form, array('class' => 'questionbankformforpopup'));
    }

    /**
     * @param object $quiz
     * @param int $questionid
     * @return array, a list (sectionid, page-number, slot-number, maxmark)
     */
    protected function get_section($structure, $sectionid) {
        if (!$sectionid) {
            // Possible, printout a notification or an error, but that should not happen.
            return null;
        }
        $sections = $structure->get_quiz_sections();
        if (!$sections) {
            return null;
        }
        foreach ($sections as $key => $section) {
            if ((int)$section->id === (int)$sectionid) {
                return $section->heading;
            }
        }
        return null;
    }

    /**
     *
     * @param object $quiz
     * @param int $questionid
     * @param string, 'all' for returning list (sectionid, page-number, slot-number, maxmark),
     * 'section' for returning section heding, 'page' for returning page number,
     * 'slot' for returning slot-number and 'mark' for returning maxmark.
     * @return array, a list (sectionid, page-number, slot-number, maxmark), or the value for the given string
     */
    protected function get_question_info($structure, $questionid, $info = 'all') {
        foreach ($structure->get_quiz_slots() as $slotid => $slot) {
            if ((int)$slot->questionid === (int)$questionid) {
                if ($info === 'all') {
                    return array($slot->sectionid, $slot->page, $slot->id, $slot->maxmark);
                }
                if ($info === 'section') {
                    return $this->get_section($structure, $slot->sectionid);
                }
                if ($info === 'page') {
                    return $slot->page;
                }
                if ($info === 'slot') {
                    return $slot->slot;
                }
                if ($info === 'mark') {
                    return $slot->maxmark;
                }
                if ($info === 'slotid') {
                    return $slot->id;
                }
            }
        }
        return null;
    }

    protected function get_previous_page($structure, $prevslotnumber) {
        if ($prevslotnumber < 1) {
            return 0;
        }
        foreach ($structure->get_quiz_slots() as $slotid => $slot) {
            if ($slot->slot == $prevslotnumber) {
                return $slot->page;
            }
        }
        return 0;
    }
}
