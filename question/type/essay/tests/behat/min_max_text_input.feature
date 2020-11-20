@qtype @qtype_essay
Feature: In an essay question, let the question author choose the min/max number of words for input text
In order to constrain student submissions for marking
As a teacher
I need to choose the appropriate minimum and/or maximum number of words for input text
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
    And the following "questions" exist:
      | questioncategory | qtype | name               | template         | minwordlimit | maxwordlimit |
      | Test questions   | essay | essay-min10-max15  | editor           | 10           | 15           |
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript @_switch_window
  Scenario: Preview an Essay question and see the input text being validated with regards to min/max word count.
    Given I choose "Preview" action for "essay-min10-max15" in the question bank
    When I switch to "questionpreview" window
    Then I should see "Please write a story about a frog."

    # Input word count(11) is valid, more than the required minimum limit(10 words) and less then required maximum limit(15 words).
    Given I set the field with xpath "//div[@class='ablock']//div[contains(@id, '1_answer')]" to "I saw a little yellow frog when I was on holiday."
    When I click on "Save" "button"
    Then I should not see "The required minimum word limit (10 words) has not been reached for this essay. Please amend your response and try again."
    And I should not see "The required maximum word limit (15 words) has been exceeded for this essay. Please amend your response and try again."
    And I click on "Start again" "button"

     # Input word count (17) in not valid (exceeds the maximum (10) requirement).
    Given I set the field with xpath "//div[@class='ablock']//div[contains(@id, '1_answer')]" to "I saw a little yellow frog when I was on holiday in one of the Caribbean islands."
    And I click on "Save" "button"
    Then I should see "The required maximum word limit (15 words) has been exceeded for this essay. Please amend your response and try again."
    And I switch to the main window

  @javascript
  Scenario: Modify the question to allow variety of settings for Minimum/Maximum word limit settings.
    When I choose "Edit question" action for "essay-min10-max15" in the question bank
    Then I should see "Minimum word limit"
    And I should see "Maximum word limit"

    # Min/Max word limits are not visible when 'Require text' field is set to 'Text input is optional'.
    When I set the field "Require text" to "Text input is optional"
    Then I should not see "Minimum word limit"
    And I should not see "Minimum word limit"

    # Min/Max word limits are visible when 'Require text' field is set to 'Require the student to enter text'.
    Given I set the field "Require text" to "Require the student to enter text"
    Then I should see "Minimum word limit"
    And I should see "Maximum word limit"

    # Sanity checks for question  authors/editors.
    Given I set the field "id_minwordlimit" to "25"
    When I click on "Save changes" "button"
    Then I should see "Maximum world limit must be greater than minimum word limit"

    When I set the field "id_minwordlimit" to "-10"
    And I click on "Save changes and continue editing" "button"
    Then I should see "Minimum word limit cannot be a negative number"

    # Disable the Minimum word limit.
    Given I set the field "minwordenabled" to "0"
    Then I click on "Save changes and continue editing" "button"

    # Enable the Minimum word limit, but don't set
    Given I set the field "minwordenabled" to "1"
    When I click on "Save changes" "button"
    Then I should see "Minimum word limit is enabled but is not set"
