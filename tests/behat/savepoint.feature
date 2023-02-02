@tool @tool_coursearchiver
Feature: An admin can create a savepoint
  In order to create a savepoint using the course archiver
  As an admin
  I need to be able to search and go save the process and restore search.

  Background:
    Given the following "courses" exist:
    | fullname | shortname | category |
    | First course | C1 | 0 |
    | Second course | C11 | 0 |
    | Third course | C2 | 0 |
    | Fourth course | C22 | 0 |
    And I log in as "admin"
    And I navigate to "Courses > Course Archiver" in site administration

  @javascript
  Scenario: Search and add and remove a new save point
    When I set the field "searches[short]" to "C1"
    And I click on "Search for courses" "button"
    Then I should see "Courses listed: 2"
    When I click on "Select All" "button"
    And I set the field "save_title" to "savepoint"
    And I click on "Create Save Point" "button"
    Then I should see "Save point has been made"
    When I click on "Start Over" "link"
    And I set the field "savestates" to "savepoint"
    And I click on "Search for courses" "button"
    And I should see "C1"
    And I should not see "C2"
    And I click on "Start Over" "link"
    When I click on "Manage Save Point List" "button"
    Then I should see "savepoint"
    When I click on "Remove" "link"
    And I switch to "removesave" window
    Then I should see "Save point has been removed"
    When I switch to the main window
    And I click on "Back" "link"
    And I click on "Manage Save Point List" "button"
    Then I should not see "savepoint"
