@tool @tool_coursearchiver @brokencoursearchivertest
Feature: An admin can archive courses
  In order to archive courses using the course archiver
  As an admin
  I need to be able to search and go through the archive process.

  Background:
    Given the following "courses" exist:
    | fullname | shortname | category |
    | First course | C1 | 0 |
    | Second course | C2 | 0 |
    And I log in as "admin"
    And I navigate to "Courses > Course Archiver" in site administration

  @javascript
  Scenario: Search and archive one course then confirm archive file.
    When I set the field "searches[short]" to "C1"
    And I click on "Search for courses" "button"
    Then I should see "Courses listed: 1"
    When I click on "Select All" "button"
    And I click on "Archive Courses" "button"
    Then I should see "Are you sure you want to archive and remove these 1 courses?"
    When I set the field "folder" to "testarchives"
    When I click on "Continue" "button"
    Then I should see "100%"
    And I should see "Archived courses: 1"
    And I should see "Notices: 0"
    And I should see "Errors: 0"
    And I click on "Start Over" "link"
    When I click on "Course Archives" "button"
    And I should see "testarchives"
    Then I should see "-C1.mbz"
