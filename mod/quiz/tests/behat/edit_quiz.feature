@mod @mod_quiz @wip
Feature: Edit quiz page
  In order to create quizzes
  As a teacher
  I need the Edit quiz page to work

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Quiz for editing |
      | Description | This quiz is used to test all aspects of the edit quiz page. |
    And I follow "Quiz for editing"
    And I follow "Edit quiz"

  @javascript
  Scenario: Do lots of adding, reordering and removing questions.
    Then I should see "Editing quiz: Quiz for editing"
    And I follow "add"
    And I follow "Add a question"
    And I set the field "True/False" to "1"
    And I press "Next"
    And I set the following fields to these values:
      | Question name | Question A          |
      | Question text | The answer is false |
    And I press "id_submitbutton"
    Then I should see "Editing quiz: Quiz for editing"
    And I should see "Question A"
