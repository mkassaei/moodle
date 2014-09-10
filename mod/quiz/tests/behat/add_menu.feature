@mod @mod_quiz @addmenu
Feature: Edit quiz page
  This feature test the add menu (adding new questions, adding questions from
  question bank, adding random questions) of the quiz editing page.

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
 
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Quiz 1 |
      | Description | This quiz is used to test all options of the add menu on the quiz editing page. |
   When I add a "Essay" question to the "Quiz 1" quiz with:
     | Question name | Essay new 01                       |
     | Question text | This is an essay in the background |
#    Then I wait "10" seconds

#  @javascript @addmenu_s1
#  Scenario: Add a new question to the quiz.
#    And I follow "Quiz 1"
#    And I follow "Edit quiz"
#    Then I should see "Editing quiz: Quiz 1"

#    And I follow "Add"
#    And I follow "a new question"
#    And I set the field "qtype_qtype_essay" to "1"
#    And I press "Add"
#    Then I should see "Adding an Essay question"
#    And I set the field "Question name" to "Essay 01"
#    And I set the field "Question text" to "Please write 200 words about Essay 01"
#    And I press "id_submitbutton"
#    Then I should see "Editing quiz: Quiz 1"
#    And I should see "Essay 01"

#    And I should see "Course 1"
#    And I follow "Course 1"
#    And I follow "Quiz 1"
#    And I follow "Edit quiz"
#    And I follow "Add"
#    And I follow "a new question"
#    And I set the field "qtype_qtype_essay" to "1"
#    And I press "Add"
#    Then I should see "Adding an Essay question"
#    And I set the field "Question name" to "Essay 03"
#    And I set the field "Question text" to "Please write 300 words about Essay 03"
#    And I press "id_submitbutton"
#    And I follow "Quiz 1"
#    And I follow "Edit quiz"
#    Then I should see "Editing quiz: Quiz 1"

  @javascript @addmenu_s2
  Scenario: Add questions from question bank to the quiz.
    # In order to be able to add questions from question bank to the quiz,
    # first we create some new questions in various ctegories.
    And I follow "Course 1"
    And I navigate to "Questions" node in "Course administration > Question bank"
    Then I should see "Question bank"
    And I should see "Select a category"

    # Create the Essay 01 question.
    When I press "Create a new question ..."
    And I set the field "qtype_qtype_essay" to "1"
    And I press "Add"
    Then I should see "Adding an Essay question"
    And I set the field "Question name" to "Essay 01"
    And I set the field "Question text" to "Please write 100 words about Essay 01"
    And I press "id_submitbutton"
    Then I should see "Question bank"
    And I should see "Essay 01"

    # Create the Essay 02 question.
    When I press "Create a new question ..."
    And I set the field "qtype_qtype_essay" to "1"
    And I press "Add"
    Then I should see "Adding an Essay question"
    And I set the field "Question name" to "Essay 02"
    And I set the field "Question text" to "Please write 200 words about Essay 02"
    And I press "id_submitbutton"
    Then I should see "Question bank"
    And I should see "Essay 02"

    # Create the Essay 03 question.
    And I set the field "Select a category" to "Default for C1"
    When I press "Create a new question ..."
    And I set the field "qtype_qtype_essay" to "1"
    And I press "Add"
    Then I should see "Adding an Essay question"
    And I set the field "Question name" to "Essay 03"
    And I set the field "Question text" to "Please write 300 words about Essay 03"
    And I press "id_submitbutton"
    Then I should see "Question bank"
    And I should see "Essay 03"

    # Create the TF 01 question.
    When I press "Create a new question ..."
    And I set the field "qtype_qtype_truefalse" to "1"
    And I press "Add"
    Then I should see "Adding a True/False question"
    And I set the field "Question name" to "TF 01"
    And I set the field "Question text" to "The correct answer is true"
    And I set the field "Correct answer" to "True"
    And I press "id_submitbutton"
    Then I should see "Question bank"
    And I should see "TF 01"

    # Create the TF 02 question.
    When I press "Create a new question ..."
    And I set the field "qtype_qtype_truefalse" to "1"
    And I press "Add"
    Then I should see "Adding a True/False question"
    And I set the field "Question name" to "TF 02"
    And I set the field "Question text" to "The correct answer is false"
    And I set the field "Correct answer" to "False"
    And I press "id_submitbutton"
    Then I should see "Question bank"
    And I should see "TF 02"

    # Add questions from question bank using the Add menu.
    # Add Essay 03 from question bank.
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I follow "Edit quiz"
    And I follow "Add"
    And I follow "from question bank"
    And I click on "Add to quiz" "link" in the "Essay 03" "table_row"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Essay 03"

    # Add Essay 01 from question bank.
    And I follow "Add"
    And I follow "from question bank"
    And I click on "Add to quiz" "link" in the "Essay 01" "table_row"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Essay 01"

    # Add Esay 02 from question bank.
    And I follow "Add"
    And I follow "from question bank"
    And I click on "Add to quiz" "link" in the "Essay 02" "table_row"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Essay 02"

    # Add a random question.
    And I follow "Add"
    And I follow "a random question"
    And I press "Add random question"
    Then I should see "Editing quiz: Quiz 1"
    And I should see "Random"
