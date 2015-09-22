@mod @mod_subpage @ou @ou_vle
Feature: Basic usage of subpage
  In order to copy a subpage
  As a teacher
  I need to use the copy subpage page

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
      | Course 3 | C3        |
      | Site 1   | S1        |
      | Site 2   | S2        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | NOCOPY! | C1     | GI1      |
      | EXISTS! | C2     | GI2      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
      | teacher1 | S1     | editingteacher |
      | teacher1 | S2     | editingteacher |
      | student1 | C1     | student        |
    And the following "permission overrides" exist:
      | capability                     | permission | role           | contextlevel | reference |
      | moodle/backup:backupsection    | Prevent    | editingteacher | Course       | C2        |
      | moodle/restore:restoreactivity | Prevent    | editingteacher | Course       | S2        |
    And the following "activities" exist:
      | activity | name         | addsection  | course | idnumber |
      | subpage  | Test subpage | 1           | C1     | 123456   |
      | subpage  | Test sp2     | 1           | C2     | 234567   |

  Scenario: Check access and course search
    Given I log in as "student1"
    And I follow "Course 1"
    When I follow "Test subpage"
    Then I should not see "Copy subpage"
    And I log out
    Given I log in as "teacher1"
    And I follow "Course 1"
    When I follow "Test subpage"
    Then I should see "Copy subpage"
    # Check course search.
    Given I follow "Copy subpage"
    Then I should see "C1"
    And I should see "Course 2"
    And I should see "Site 1"
    And I should not see "Course 3"
    And I should not see "Site 2"
    Given I set the field "search" to "C"
    When I press "Search"
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should not see "Site 1"
    # Test link cap access
    Given I am on homepage
    And I follow "Course 2"
    When I follow "Test sp2"
    Then I should not see "Copy subpage"

  Scenario: Check copy
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I follow "Test subpage"
    # Add Label in 2 sections (need to do this manually because not on course page).
    When I set the field with xpath "//div[@class='urlselect'][1]//select[@name='jump']" to "Label"
    And I click on "ul.topics li:nth-of-type(1) div.urlselect:nth-of-type(1) input[type=submit]" "css_element"
    And I set the field "Label text" to "Frog!"
    And I press "Save and return to course"
    And I press "Add section"
    And I click on "ul.topics li:nth-of-type(2) .summary a" "css_element"
    And I set the field "usedefaultname" to "0"
    And I set the field "name" to "TEST2"
    And I press "Save changes"
    When I set the field "Add a resource to section 'TEST2'" to "Label"
    And I click on "ul.topics li:nth-of-type(2) div.urlselect:nth-of-type(1) input[type=submit]" "css_element"
    And I set the field "Label text" to "Toad!"
    And I press "Save and return to course"
    Then I should see "Frog!"
    And I should see "Toad!"
    # Add another subpage + label in that.
    Given I set the field "Add a resource to section 'TEST2'" to "Subpage"
    And I click on "ul.topics li:nth-of-type(2) div.urlselect:nth-of-type(1) input[type=submit]" "css_element"
    And I set the field "name" to "Sub Subpage"
    And I press "Save and display"
    When I set the field with xpath "//div[@class='urlselect'][1]//select[@name='jump']" to "Label"
    And I click on "ul.topics li:nth-of-type(1) div.urlselect:nth-of-type(1) input[type=submit]" "css_element"
    And I set the field "Label text" to "Zombie!"
    When I press "Save and return to course"
    Then I should see "Zombie!"
    # Start copy to C2.
    Given I follow "C1"
    And I follow "Test subpage"
    And I follow "Copy subpage"
    And I set the field with xpath "//tbody//tr[2]//input[@type='radio']" to "1"
    When I press "Continue"
    Then I should see "Target: C2"
    Given I press "Continue"
    When I press "Continue"
    Then I should see "Course 2"
    And I should see "Test subpage"
    And ".activityinstance .dimmed" "css_element" should exist
    # Check contents.
    Given I follow "Test subpage"
    Then I should see "TEST2"
    And I should see "Frog!"
    And I should see "Toad!"
    Given I follow "Sub Subpage"
    Then I should see "Zombie!"
    # Check groups were not copied to course.
    Given I navigate to "Groups" node in "Course administration > Users"
    Then I should see "EXISTS!"
    And I should not see "NOCOPY!"
