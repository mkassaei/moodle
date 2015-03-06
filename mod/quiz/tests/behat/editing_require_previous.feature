@mod @mod_quiz
Feature: Edit quizzes where some questions require the previous one to have been completed
  In order to create quizzes where later questions can only be seen after earlier ones are answered
  As a teacher
  I need to be able to configure this on the Edit quiz page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And I log in as "teacher1"

  @javascript
  Scenario: The first question cannot depend on the previous (whatever is in the DB)
    When the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | immediatefeedback  |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    |
      | Test questions   | truefalse   | TF1  | First question  |
    And quiz "Quiz 1" contains the following questions:
      | question | page | requireprevious |
      | TF1      | 1    | 1               |
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    Then "be attempted" "link" should not exist
    # The text "be attempted" is used as a relatively unique string in both the add and remove links.

  @javascript
  Scenario: If the second question depends on the first, that is shown
    When the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | immediatefeedback  |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    |
      | Test questions   | truefalse   | TF1  | First question  |
      | Test questions   | truefalse   | TF2  | Second question |
    And quiz "Quiz 1" contains the following questions:
      | question | page | requireprevious |
      | TF1      | 1    | 0               |
      | TF2      | 1    | 1               |
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    Then "This question cannot be attempted until the previous question has been completed." "link" should be visible

  @javascript
  Scenario: The second question can be set to depend on the first
    When the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | immediatefeedback  |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    |
      | Test questions   | truefalse   | TF1  | First question  |
      | Test questions   | truefalse   | TF2  | Second question |
      | Test questions   | truefalse   | TF3  | Third question  |
    And quiz "Quiz 1" contains the following questions:
      | question | page | requireprevious |
      | TF1      | 1    | 0               |
      | TF2      | 1    | 0               |
      | TF3      | 1    | 0               |
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    And I follow "No restriction on when question 2 can be attempted • Click to change"
    Then "Question 2 cannot be attempted until the previous question 1 has been completed • Click to change" "link" should be visible
    And "No restriction on when question 3 can be attempted • Click to change" "link" should be visible

  @javascript
  Scenario: A question that did depend on the previous can be un-linked
    When the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | immediatefeedback  |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    |
      | Test questions   | truefalse   | TF1  | First question  |
      | Test questions   | truefalse   | TF2  | Second question |
      | Test questions   | truefalse   | TF3  | Third question  |
    And quiz "Quiz 1" contains the following questions:
      | question | page | requireprevious |
      | TF1      | 1    | 0               |
      | TF2      | 1    | 1               |
      | TF3      | 1    | 1               |
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    And I follow "Question 3 cannot be attempted until the previous question 2 has been completed • Click to change"
    Then "Question 2 cannot be attempted until the previous question 1 has been completed • Click to change" "link" should be visible
    And "No restriction on when question 3 can be attempted • Click to change" "link" should be visible

  @javascript
  Scenario: Question dependency cannot apply to deferred feedback quizzes so UI is hidden
    When the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | deferredfeedback  |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    |
      | Test questions   | truefalse   | TF1  | First question  |
      | Test questions   | truefalse   | TF2  | Second question |
    And quiz "Quiz 1" contains the following questions:
      | question | page | requireprevious |
      | TF1      | 1    | 0               |
      | TF2      | 1    | 1               |
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    Then "be attempted" "link" should not exist

  @javascript
  Scenario: A question can never depend on an essay
    When the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | immediatefeedback  |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext   |
      | Test questions   | essay       | Story | First question |
      | Test questions   | truefalse   | TF1   | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page | requireprevious |
      | Story    | 1    | 0               |
      | TF1      | 1    | 0               |
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    Then "be attempted" "link" should not exist

  @javascript
  Scenario: A question can never depend on a description
    When the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | immediatefeedback  |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext   |
      | Test questions   | description | Info | Read me        |
      | Test questions   | truefalse   | TF1  | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page | requireprevious |
      | Info     | 1    | 0               |
      | TF1      | 1    | 0               |
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    Then "be attempted" "link" should not exist
