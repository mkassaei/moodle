@mod @mod_quiz @repaginate
Feature: Edit quiz page
  This feature test the repaginate button where queestions can be repaginate with
  N question(s) per page on the quiz editing page.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1    | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    |

    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"

  @javascript @repaginate_s1
  Scenario: Repaginate questions with N question(s) per page as well as clicking
    on "add page break" or "Remove page break" icons to repaginate in any desired format.

    Then I should see "Editing quiz: Quiz 1"

    # Add the first Essay question.
    And I follow "Add"
    And I follow "a new question"
    And I set the field "qtype_qtype_essay" to "1"
    And I press "submitbutton"
    Then I should see "Adding an Essay question"
    And I set the field "Question name" to "Essay 01 new"
    And I set the field "Question text" to "Please write 100 words about Essay 01"
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Essay 01 new"

    # Add the second Essay question.
    And I follow "Add"
    And I follow "a new question"
    And I set the field "qtype_qtype_essay" to "1"
    And I press "submitbutton"
    Then I should see "Adding an Essay question"
    And I set the field "Question name" to "Essay 02 new"
    And I set the field "Question text" to "Please write 200 words about Essay 02"
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Essay 02 new"

    # Start repaginating.
    And I should see "Page 1"
    And I should not see "Page 2"

    # click on 'Add page break' icon between slot 1 and slot 2.
    And I click on "//a[contains(@href, 'slot=1')]//img[@title=\"Add page break\"]" "xpath_element"
    And I should see "Page 2"

    # click on 'Remove page break' icon between slot 1 and slot 2.
    And I click on "//a[contains(@href, 'slot=1')]//img[@title=\"Remove page break\"]" "xpath_element"
    And I should not see "Page 2"

    # Add the third Essay question.
    And I follow "Add"
    And I follow "a new question"
    And I set the field "qtype_qtype_essay" to "1"
    And I press "submitbutton"
    Then I should see "Adding an Essay question"
    And I set the field "Question name" to "Essay 03 new"
    And I set the field "Question text" to "Please write 200 words about Essay 03"
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Essay 03 new"

    # We have 3 question on one page.
    And I should see "Page 1"
    And I should not see "Page 2"
    And I should not see "Page 3"
 
    # We have 2 questions in page 1 and one question in page 2.
    # click on 'Add page break' icon between slot 2 and slot 3.
    And I click on "//a[contains(@href, 'slot=2')]//img[@title=\"Add page break\"]" "xpath_element"
    And I should see "Page 1"
    And I should see "Page 2"
 
    # We have 3 questions on each page.
    # click on 'Add page break' icon between slot 1 and slot 2.
    And I click on "//a[contains(@href, 'slot=1')]//img[@title=\"Add page break\"]" "xpath_element"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"

    # Remove both page breaks.
    And I click on "//a[contains(@href, 'slot=1')]//img[@title=\"Remove page break\"]" "xpath_element"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should not see "Page 3"

    And I click on "//a[contains(@href, 'slot=2')]//img[@title=\"Remove page break\"]" "xpath_element"
    And I should see "Page 1"
    And I should not see "Page 2"
    And I should not see "Page 3"

    # Repaginate one question per page.
    When I press "Repaginate"
    Then I should see "Repaginate with"
    And I set the field "menuquestionsperpage" to "1"
    When I press "Go"
    Then I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"

    # Add the forth Essay question in a new page (Page 4).
    And I click on "//a[@id=\"action-menu-toggle-2\"]" "xpath_element"
    And I click on "//li[@id='page-3']//a[contains(., 'a new question')]" "xpath_element"
    And I set the field "qtype_qtype_essay" to "1"
    And I press "submitbutton"
    Then I should see "Adding an Essay question"
    And I set the field "Question name" to "Essay 04 new"
    And I set the field "Question text" to "Please write 300 words about Essay 04"
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Essay 04 new"
    Then I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"

    And I click on "//a[contains(@href, 'slot=3')]//img[@title=\"Add page break\"]" "xpath_element"
    And I should see "Page 4"

    # Repaginate with 2 questions per page.
    When I press "Repaginate"
    Then I should see "Repaginate with"
    And I set the field "menuquestionsperpage" to "2"
    When I press "Go"
    Then I should see "Page 1"
    And I should see "Page 2"
    And I should not see "Page 3"
    And I should not see "Page 4"

    # Repaginate with unlimited questions per page (All questions on Page 1).
    When I press "Repaginate"
    Then I should see "Repaginate with"
    And I set the field "menuquestionsperpage" to "Unlimited"
    When I press "Go"
    Then I should see "Page 1"
    And I should not see "Page 2"
    And I should not see "Page 3"
    And I should not see "Page 4"
    