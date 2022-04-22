@tool @tool_coursearchiver @archiveroptout @_switch_window
Feature: An admin can optout courses
  In order to optout courses using the course archiver
  As an admin
  I need to be able to search and go through the optout process.

  Background:
    Given the following "courses" exist:
      | id | fullname | shortname | category |
      | 2 | First course | C1 | 0 |
      | 3 | Second course | C2 | 0 |
    And I log in as "admin"
    And I navigate to "Courses > Course Archiver" in site administration

  @javascript
  Scenario: Search and then add and remove course from optoutlist
    When I set the field "searches[short]" to "C1"
    And I click on "Search for courses" "button"
    Then I should see "Courses listed: 1"
    When I click on "Select All" "button"
    And I click on "Optout Courses" "button"
    Then I should see "Are you sure you want to optout these 1 courses?"
    When I click on "Continue" "button"
    Then I should see "100%"
    And I should see "Optout courses: 1"
    And I should see "Notices: 0"
    And I should see "Errors: 0"
    And I click on "Start Over" "link"
    When I click on "Manage Optout List" "button"
    Then I should see "First course"
    When I click on "Remove" "link"
    And I switch to "optbackin" window
    Then I should see "First course has been removed"
    When I switch to the main window
    And I click on "Back" "link"
    And I click on "Manage Optout List" "button"
    Then I should not see "First course"
