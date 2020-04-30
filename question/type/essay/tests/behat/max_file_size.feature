@qtype @qtype_essay
Feature: In a essay question, let the question author choose the maxbytes for attachments
In order to constrain student submissions for marking
As a teacher
I need to choose the appropriate maxbytes for attachments

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student0@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext    | defaultmark |
      | Test questions   | essay       | TF1   | First question  | 20          |
    And the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | grade |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | 20    |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Edit quiz" in current page administration
    And I click on "Edit question TF1" "link"
    And I set the field "Allow attachments" to "1"
    And I set the field "Response format" to "No online text"
    And I set the field "Require attachments" to "1"
    And I set the field "maxbytes" to "10KB"
    And I press "Save changes"
    Then I log out

  @javascript
  Scenario: Preview an Essay question and see the message for maximum file size before uploading a file.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt quiz now"
    And I should see "First question"
    And I should see "You can drag and drop files here to add them."
    And I should see "Maximum file size: 10KB, maximum number of files: 1, maximum total size: 10KB"

  @javascript
  Scenario: Teacher modifies the max file size and set Allow attachments to 3, then student preview an Essay question and see the message for maximum file size before uploading any files.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Edit quiz" in current page administration
    And I click on "Edit question TF1" "link"
    And I set the field "Allow attachments" to "3"
    And I set the field "maxbytes" to "50KB"
    And I press "Save changes"
    Then I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt quiz now"
    And I should see "First question"
    And I should see "You can drag and drop files here to add them."
    And I should see "Maximum file size: 50KB, maximum number of files: 3, maximum total size: 150KB"

  @javascript
  Scenario: Teacher set the Allow attachments to Unlimited, then student preview an Essay question and see the message for maximum file size before uploading any files.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Edit quiz" in current page administration
    And I click on "Edit question TF1" "link"
    And I set the field "Allow attachments" to "Unlimited"
    And I press "Save changes"
    Then I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt quiz now"
    And I should see "First question"
    And I should see "You can drag and drop files here to add them."
    And I should see "Maximum size for new files: 10KB"
