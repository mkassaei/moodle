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
 * Steps definitions related to mod_quiz.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../question/tests/behat/behat_question_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Steps definitions related to mod_quiz.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2014 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_quiz extends behat_question_base {
    /**
     * Adds a question to the existing quiz with filling the form.
     *
     * The form for creating a question should be on one page.
     *
     * @When /^I add a "(?P<question_type_string>(?:[^"]|\\")*)" question to the "(?P<quiz_name_string>(?:[^"]|\\")*)" quiz with:$/
     * @param string $questiontype
     * @param string $quizname
     * @param TableNode $questiondata with data for filling the add question form
     */
    public function i_add_question_to_the_quiz_with($questiontype, $quizname, TableNode $questiondata) {
        $quizname = $this->escape($quizname);
        $editquiz = $this->escape(get_string('editquiz', 'quiz'));
        $addaquestion = $this->escape(get_string('addaquestion', 'quiz'));
        $menuxpath = "//div[contains(@class, ' page-add-actions ')][last()]//a[contains(@class, ' textmenu')]";
        $itemxpath = "//div[contains(@class, ' page-add-actions ')][last()]//a[contains(@class, ' addquestion ')]";
        return array_merge(array(
            new Given("I follow \"$quizname\""),
            new Given("I follow \"$editquiz\""),
            new Given("I click on \"$menuxpath\" \"xpath_element\""),
            new Given("I click on \"$itemxpath\" \"xpath_element\""),
                ), $this->finish_adding_question($questiontype, $questiondata));
    }

    /**
     * Set the max mark for a question on the Edit quiz page.
     *
     * @When /^I set the max mark for question "(?P<question_name_string>(?:[^"]|\\")*)" to "(?P<new_mark_string>(?:[^"]|\\")*)"$/
     * @param string $questionname the name of the question to set the max mark for.
     * @param string $newmark the mark to set
     */
    public function i_set_the_max_mark_for_quiz_question($questionname, $newmark) {
        return array(
            new Given('I follow "' . $this->escape(get_string('editmaxmark', 'quiz')) . '"'),
            new Given('I wait "2" seconds'),
            new Given('I should see "' . $this->escape(get_string('edittitleinstructions')) . '"'),
            new Given('I set the field "maxmark" to "' . $this->escape($newmark) . chr(10) . '"'),
        );
    }

    /**
     * Open the add menu on a given page, or at the end of the Edit quiz page.
     * @Given /^I open the "(?P<page_n_or_last_string>(?:[^"]|\\")*)" add to quiz menu$/
     * @param string $pageorlast either "Page n" or "last".
     */
    public function i_open_the_add_to_quiz_menu_for($pageorlast) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        if ($pageorlast == 'last') {
            $xpath = "//div[@class = 'last-add-menu']//a[contains(@class, 'textmenu') and contains(., 'Add')]";
        } else if (preg_match('~Page (\d+)~', $pageorlast, $matches)) {
            $xpath = "//li[@id = 'page-{$matches[1]}']//a[contains(@class, 'textmenu') and contains(., 'Add')]";
        } else {
            throw new ExpectationException("The I open the add to quiz menu step must specify either 'Page N' or 'last'.");
        }
        $menu = $this->find('xpath', $xpath)->click();
    }

    /**
     * Click on a given link in the moodle-actionmenu that is currently open.
     * @Given /^I follow "(?P<link_string>(?:[^"]|\\")*)" in the open menu$/
     * @param $text the text (or id, etc.) of the link to click.
     */
    public function i_follow_in_the_open_menu($linkstring) {
        $openmenuxpath = "//div[contains(@class, 'moodle-actionmenu') and contains(@class, 'show')]";
        return array(
            new Given('I click on "' . $linkstring . '" "link" in the "' . $openmenuxpath . '" "xpath_element"'),
        );
    }

    /**
     * Check whether a particular question is on a particular page of the quiz on the Edit quiz page.
     * @Given /^I should see "(?P<question_name>(?:[^"]|\\")*)" on quiz page "(?P<page_number>\d+)"$/
     */
    public function i_should_see_on_quiz_page($questionname, $pagenumber) {
        $xpath = "//li[contains(., '" . $this->escape($questionname) .
                "')][./preceding-sibling::li[contains(@class, 'pagenumber')][1][contains(., 'Page " .
                $pagenumber . "')]]";
        return array(
            new Given('"' . $xpath . '" "xpath_element" should exist'),
        );
    }
}
