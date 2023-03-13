@mod @mod_subpage @ou @ou_vle
Feature: Copy a subpage
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
    And the following config values are set as admin:
      | maxsections | 200 | moodlecourse |

  Scenario: Check access and course search
    When I am on the "Test subpage" "subpage activity" page logged in as student1
    Then I should not see "Copy subpage"
    And I log out
    When I am on the "Test subpage" "subpage activity" page logged in as teacher1
    Then I should see "Copy subpage"
    # Check course search.
    When I follow "Copy subpage"
    Then I should see "C1"
    And I should see "Course 2"
    And I should see "Site 1"
    And I should not see "Course 3"
    And I should not see "Site 2"
    And I set the field "search" to "C"
    When I press "Search"
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should not see "Site 1"
    # Test link cap access
    And I am on homepage
    When I am on the "Test sp2" "subpage activity" page
    Then I should not see "Copy subpage"

  @javascript
  Scenario: Check copy
    When I am on the "Course 1" course page logged in as teacher1
    And I turn editing mode on
    And I am on the "Test subpage" "subpage activity" page
    # Add Label in 2 sections (need to do this manually because not on course page).
    And I add a "Text and media area" to section "110" and I fill the form with:
      | Text | Frog! |
    And I press "Add section"
    And I click on "ul.topics li:nth-of-type(2) .summary a" "css_element"
    And I set the field "Custom" to "1"
    And I set the field "New value for Section name" to "TEST2"
    And I press "Save changes"
    # Add a label to TEST2 section
    And I add a "Text and media area" to section "111" and I fill the form with:
      | Text | Toad! |
    Then I should see "Frog!"
    And I should see "Toad!"
    # Add another subpage + label in that.
    And I add a "Subpage" to section "111"
    And I set the field "name" to "Sub Subpage"
    And I press "Save and display"
    And I add a "Text and media area" to section "112" and I fill the form with:
      | Text | Zombie! |
    Then I should see "Zombie!"
    # Start copy to C2.
    Given I am on the "Test subpage" "subpage activity" page
    And I click on "Copy subpage" "link"
    And I set the field with xpath "//tbody//tr[2]//input[@type='radio']" to "1"
    When I press "Continue"
    Then I should see "Target: C2"
    Given I press "Continue"
    When I press "Continue"
    Then I should see "Course 2"
    And I should see "Test subpage"
    And I should see "Hidden from students"
    # Check contents.
    When I click on "Open course index" "button"
    And I follow "Test subpage"
    Then I should see "TEST2"
    And I should see "Frog!"
    And I should see "Toad!"
    When I click on "Open course index" "button"
    And I follow "Sub Subpage"
    Then I should see "Zombie!"
    # Check groups were not copied to course.
    Given I am on the "Course 2" "groups" page
    Then I should see "EXISTS!"
    And I should not see "NOCOPY!"
