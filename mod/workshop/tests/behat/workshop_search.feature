@mod @mod_workshop
Feature: Workshop search task
  Search is a facility for teachers/admin to search for students by idnumber, firstname, lastname
  as well as a list from a dropdown menu list "Sumbission found for this user",  "No sumbission found for this user", ...
  Background:
    Given the following "users" exist:
      | username | idnumber | firstname | lastname     | email                |
      | student1 | A1234567 | Dave      | Good-st1     | student1@example.com |
      | student2 | A1235678 | David     | Goodly-st2   | student2@example.com |
      | student3 | A2345678 | Daniel    | Goodwood-st3 | student3@example.com |
      | student4 | B1234567 | Dan       | Goodman-st4  | student4@example.com |
      | student5 | B1235671 | Carmen    | Woodly-st5   | student5@example.com |
      | student6 | C1234567 | Chris     | Woodhead-st6 | student6@example.com |
      | student7 | C2345678 | Christine | Woodlock-st7 | student7@example.com |
      | teacher1 | T0001111 | Terry1    | Teacher1     | teacher1@example.com |
    And the following "courses" exist:
      | fullname  | shortname |
      | Course1   | c1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | c1     | student        |
      | student2 | c1     | student        |
      | student3 | c1     | student        |
      | student4 | c1     | student        |
      | student5 | c1     | student        |
      | student6 | c1     | student        |
      | student7 | c1     | student        |
      | teacher1 | c1     | editingteacher |
    And the following "activities" exist:
      | activity | name          | intro                                              | course | idnumber  | latesubmissions | submisstionstart | submissionend |
      | workshop | TestWorkshop1 | TW3 with Submission deadline in future (1 Jan 2030)| c1     | workshop1 | 1               | 1514904308       | 1893369600    |
    # Teacher sets up assessment form and changes the phase to submission.
    And I log in as "teacher1"
    And I am on "Course1" course homepage
    And I follow "TestWorkshop1"
    And I edit assessment form in workshop "TestWorkshop1" as:"
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor | Aspect2 |
      | id_description__idx_2_editor | Aspect3 |
    And I change phase in workshop "TestWorkshop1" to "Submission phase"
    And I log out
    # Some students add their submission to TestWorkshop1.
    # student1 submits.
    Given I log in as "student1"
    And I am on "Course1" course homepage
    When I follow "TestWorkshop1"
    And I add a submission in workshop "TestWorkshop1" as:"
      | Title              | Submission from s1  |
      | Submission content | Some content from student1 |
    And I log out
    # student3 submits.
    Given I log in as "student3"
    And I am on "Course1" course homepage
    When I follow "TestWorkshop1"
    And I add a submission in workshop "TestWorkshop1" as:"
      | Title              | Submission from s3  |
      | Submission content | Some content from student3 |
    And I log out
    # student5 submits.
    Given I log in as "student5"
    And I am on "Course1" course homepage
    When I follow "TestWorkshop1"
    And I add a submission in workshop "TestWorkshop1" as:"
      | Title              | Submission from s5  |
      | Submission content | Some content from student5 |
    And I log out
    # student7 submits.
    Given I log in as "student7"
    And I am on "Course1" course homepage
    When I follow "TestWorkshop1"
    And I add a submission in workshop "TestWorkshop1" as:"
      | Title              | Submission from s7  |
      | Submission content | Some content from student7 |
    And I log out

  @javascript
  Scenario: Teacher uses the search and filter options facility searching by ID number.
    Given I log in as "teacher1"
    And I am on "Course1" course homepage
    When I follow "TestWorkshop1"
    Then I should see "Good-st1"
    And I should see "Goodly-st2"
    And I should see "Goodwood-st3"
    And I should see "Goodman-st4"
    And I should see "Woodly-st5"
    And I should see "Woodhead-st6"
    And I should see "Woodlock-st7"
    And I should see "Search and filter options"
    # Invalid ID number.
    Given I set the field "ID number" to "xyz"
    When I click on "Search and update table" "button"
    Then I should see "User with id number starting with or equal to \"xyz\" was not found"
    # Valid ID numbers starting with A.
    Given I set the field "ID number" to "A"
    When I click on "Search and update table" "button"
    Then I should see "Good-st1"
    And I should see "Goodly-st2"
    And I should see "Goodwood-st3"
    But I should not see "Goodman-st4"
    And I should not see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid ID numbers starting with A123.
    Given I set the field "ID number" to "A123"
    When I click on "Search and update table" "button"
    Then I should see "Good-st1"
    And I should see "Goodly-st2"
    But I should not see "Goodwood-st3"
    But I should not see "Goodman-st4"
    And I should not see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid ID numbers starting with A234.
    Given I set the field "ID number" to "A234"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    And I should not see "Goodly-st2"
    But I should see "Goodwood-st3"
    And I should not see "Goodman-st4"
    And I should not see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid ID numbers starting with B123.
    Given I set the field "ID number" to "B123"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    And I should not see "Goodly-st2"
    And I should not see "Goodwood-st3"
    But I should see "Goodman-st4"
    And I should see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid ID numbers starting with B1234.
    Given I set the field "ID number" to "B1234"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    And I should not see "Goodly-st2"
    And I should not see "Goodwood-st3"
    But I should see "Goodman-st4"
    And I should not see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid ID numbers is exactly C1234567.
    Given I set the field "ID number" to "C1234567"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    And I should not see "Goodly-st2"
    And I should not see "Goodwood-st3"
    And I should not see "Goodman-st4"
    And I should not see "Woodly-st5"
    But I should see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid ID numbers starting with A and user must have submitted a submission.
    Given I set the field "ID number" to "A"
    And I set the field "Filter" to "Submission submitted"
    When I click on "Search and update table" "button"
    Then I should see "Good-st1"
    And I should see "Goodwood-st3"
    And I should not see "Goodly-st2"
    And I should not see "Goodman-st4"
    And I should not see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    And I log out

  @javascript
  Scenario: Teacher uses the search and filter options facility searching by fist and surename.
    Given I log in as "teacher1"
    And I am on "Course1" course homepage
    When I follow "TestWorkshop1"
    Then I should see "Dave"
    And I should see "David"
    And I should see "Daniel"
    And I should see "Dan"
    And I should see "Carmen"
    And I should see "Chris"
    And I should see "Christine"

    And I should see "Good-st1"
    And I should see "Goodly-st2"
    And I should see "Goodwood-st3"
    And I should see "Goodman-st4"
    And I should see "Woodly-st5"
    And I should see "Woodhead-st6"
    And I should see "Woodlock-st7"
    And I should see "Search and filter options"

    # Invalid First number.
    Given I set the field "id_fname" to "Manolo"
    When I click on "Search and update table" "button"
    Then I should see "User with first name starting with or equal to \"Manolo\" was not found"
    # Valid first name starting with D.
    Given I set the field "id_fname" to "D"
    When I click on "Search and update table" "button"
    Then I should see "Dave"
    And I should see "David"
    And I should see "Daniel"
    And I should see "Dan"
    But I should not see "Carmen"
    And I should not see "Chris"
    And I should not see "Christine"
    # Valid first name starting with Chr.
    Given I set the field "fname" to "Dav"
    When I click on "Search and update table" "button"
    Then I should see "Dave"
    And I should see "David"
    And I should not see "Daniel"
    And I should not see "Dan"
    But I should not see "Carmen"
    And I should not see "Chris"
    And I should not see "Christine"
    # Valid first name starting with Chr.
    Given I set the field "id_fname" to "Chr"
    When I click on "Search and update table" "button"
    Then I should not see "Dave"
    And I should not see "David"
    And I should not see "Daniel"
    And I should not see "Dan"
    And I should not see "Carmen"
    But I should see "Chris"
    And I should see "Christine"
    # Invalid Surname number.
    Given I set the field "id_lname" to "Goodman-st1"
    When I click on "Search and update table" "button"
    Then I should see "User with surname starting with or equal to \"Goodman-st1\" was not found"
    # Valid surname starting with gooD (case insensitive).
    Given I set the field "id_lname" to "gooD"
    And I set the field "id_fname" to ""
    When I click on "Search and update table" "button"
    Then I should see "Good-st1"
    And I should see "Goodly-st2"
    And I should see "Goodwood-st3"
    And I should see "Goodman-st4"
    But I should not see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid surname starting with wood.
    Given I set the field "id_lname" to "wood"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    And I should not see "Goodly-st2"
    And I should not see "Goodwood-st3"
    And I should not see "Goodman-st4"
    But I should see "Woodly-st5"
    And I should see "Woodhead-st6"
    And I should see "Woodlock-st7"
    # Valid surname starting with woodl.
    Given I set the field "id_lname" to "woodl"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    And I should not see "Goodly-st2"
    And I should not see "Goodwood-st3"
    And I should not see "Goodman-st4"
    But I should see "Woodly-st5"
    And I should not see "Woodhead-st6"
    And I should see "Woodlock-st7"
    # Valid surname equal to Goodly-st2.
    Given I set the field "id_lname" to "Goodly-st2"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    But I should see "Goodly-st2"
    And I should not see "Goodwood-st3"
    And I should not see "Goodman-st4"
    And I should not see "Woodly-st5"
    But I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    # Valid surname starting with Good and user must have submitted a submission.
    Given I set the field "id_lname" to "Good"
    And I set the field "Filter" to "Submission submitted"
    When I click on "Search and update table" "button"
    Then I should see "Good-st1"
    But I should not see "Goodly-st2"
    And I should see "Goodwood-st3"
    And I should not see "Goodman-st4"
    And I should not see "Woodly-st5"
    But I should not see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    And I log out

  @javascript
  Scenario: Teacher uses the search and filter options facility searching by filter options.
    Given I log in as "teacher1"
    And I am on "Course1" course homepage
    When I follow "TestWorkshop1"
    Then I should see "Dave"
    And I should see "David"
    And I should see "Daniel"
    And I should see "Dan"
    And I should see "Carmen"
    And I should see "Chris"
    And I should see "Christine"

    And I should see "Good-st1"
    And I should see "Goodly-st2"
    And I should see "Goodwood-st3"
    And I should see "Goodman-st4"
    And I should see "Woodly-st5"
    And I should see "Woodhead-st6"
    And I should see "Woodlock-st7"
    And I should see "Search and filter options"

    # List of susers who have submitted a submission.
    Given I set the field "Filter" to "Submission submitted"
    When I click on "Search and update table" "button"
    Then I should see "Good-st1"
    But I should not see "Goodly-st2"
    And I should see "Goodwood-st3"
    And I should not see "Goodman-st4"
    And I should see "Woodly-st5"
    But I should not see "Woodhead-st6"
    And I should see "Woodlock-st7"
    # List of susers who have not submitted a submission.
    Given I set the field "Filter" to "Submission not submitted"
    When I click on "Search and update table" "button"
    Then I should not see "Good-st1"
    But I should see "Goodly-st2"
    And I should not see "Goodwood-st3"
    And I should see "Goodman-st4"
    And I should not see "Woodly-st5"
    But I should see "Woodhead-st6"
    And I should not see "Woodlock-st7"
    And I log out
