@tool @tool_coursearchiver
Feature: An admin can delete courses
  In order to delete courses using the course archiver
  As an admin
  I need to be able to search and go through the delete process.

  Background:
    Given the following "courses" exist:
    | fullname | shortname | category |
    | First course | C1 | 0 |
    | Second course | C2 | 0 |
    And I log in as "admin"
    And I navigate to "Courses > Course Archiver" in site administration

  @javascript
  Scenario: Search and delete one course
    When I set the field "searches[short]" to "C1"
    And I click on "Search for courses" "button"
    Then I should see "Courses listed: 1"
    When I click on "Select All" "button"
    And I click on "Delete Courses" "button"
    Then I should see "Are you sure you want to completely remove these 1 courses?"
    When I click on "Continue" "button"
    Then I should see "100%"
    And I should see "Deleted courses: 1"
    And I should see "Notices: 0"
    And I should see "Errors: 0"

  @javascript
  Scenario: Search and delete all courses
    When I set the field "searches[short]" to "C"
    And I click on "Search for courses" "button"
    Then I should see "Courses listed: 2"
    When I click on "Select All" "button"
    And I click on "Delete Courses" "button"
    Then I should see "Are you sure you want to completely remove these 2 courses?"
    When I click on "Continue" "button"
    Then I should see "100%"
    And I should see "Deleted courses: 2"
    And I should see "Notices: 0"
    And I should see "Errors: 0"
