@mod @mod_subpage @ou @ou_vle @javascript
Feature: Check that user is not able to
  move subpage or repeat of subpage into itself.

  Background:
    Given I am using the OSEP theme
    And the following "courses" exist:
      | fullname | shortname | format      |
      | Course 1 | C1        | oustudyplan |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on site homepage
    And I follow "Course 1"

  Scenario: Check that moving subpage are disabled, other activity is normal.
    When I follow "Turn editing on"
    And the following "activities" exist:
      | activity | name      | intro                  | course | idnumber |
      | forumng  | ForumNG 1 | ForumNG1's description | C1     | forumng1 |
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 1 |
    And I press "Select and move items..."
    And I set the field "ForumNG 1" to "1"
    And I press "Move selected"
    # Moving ForumNG, activity will not be disabled.
    Then "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(1)" "css_element"
    And "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(2)" "css_element"
    # Click cancel button, There are 2 cancel button so we use css selector to pick it.
    When I click on ".course-content > .oustudyplan-cancelbar button" "css_element"
    And I press "Select and move items..."
    And I set the field "Subpage 1" to "1"
    And I press "Move selected"
    # Moving subpage 1, subpage 1 will be disabled.
    Then "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(1)" "css_element"
    And "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(2)" "css_element"

  Scenario: Check that moving child subpage are disabled, parent subpage is normal.
    When I follow "Turn editing on"
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 1 |
    And I follow "Subpage 1"
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Subpage 1.1 |
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Subpage 1.2 |
    And I press "Select and move items..."
    And I set the field "Subpage 1.1" to "1"
    And I press "Move selected"
    And I follow "C1"
    # "Subpage 1" is not disabled.
    And "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(1)" "css_element"
    When I follow "Subpage 1"
    # "Subpage 1.1" is disabled.
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(1)" "css_element"
    # "Subpage 1.2" is not disabled.
    And "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(2)" "css_element"

  Scenario: Repeat document is disabled when moving repeated.
    When I follow "Turn editing on"
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 1 |
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 2 |
    # Repeat "Subpage 1"
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    Then I press "Save and return to course"
    And I press "Select and move items..."
    And I set the field "Subpage 1" to "1"
    And I press "Move selected"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(1)" "css_element"
    Then "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(2)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(3)" "css_element"

  Scenario: Repeat document is disabled when moving repeat.
    When I follow "Turn editing on"
    And I add a section to the end of the OU study planner
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Subpage 1 |
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 2 |
    # Repeat "Subpage 1"
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    Then I press "Save and return to course"
    And I press "Select and move items..."
    And I set the field "Subpage 1" to "1"
    And I press "Move selected"
    Then "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(1)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(2)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "#section-2 li.activity:nth-of-type(1)" "css_element"

  Scenario: Moving multiple subpage.
    When I follow "Turn editing on"
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 1 |
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 2 |
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 3 |
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 4 |
    And I press "Select and move items..."
    And I set the field "Subpage 1" to "1"
    And I set the field "Subpage 2" to "1"
    And I set the field "Subpage 4" to "1"
    And I press "Move selected"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(1)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(2)" "css_element"
    Then "a[href='#']" "css_element" should not exist in the "li.activity:nth-of-type(3)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(4)" "css_element"

  Scenario: Move subpage also disabled all others subpage to prevent infinite loop
    When I follow "Turn editing on"
    And I add a section to the end of the OU study planner
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Subpage 1 |
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Subpage 2 |
    # Repeat "Subpage 1"
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    Then I press "Save and return to course"
    # Repeat "Subpage 1"
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    Then I press "Save and return to course"
    # Repeat "Subpage 1"
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    Then I press "Save and return to course"
    And I press "Select and move items..."
    And I set the field "Subpage 1" to "1"
    And I press "Move selected"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(1)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(2)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "li.activity:nth-of-type(3)" "css_element"
    Then "a[href='#']" "css_element" should exist in the "#section-1 li.activity:nth-of-type(1)" "css_element"
    Then "a[href='#']" "css_element" should not exist in the "#section-1 li.activity:nth-of-type(2)" "css_element"

  Scenario: Check moving subpage is also disabled in deleted section.
    When I follow "Turn editing on"
    And I add a section to the end of the OU study planner
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Subpage 1 |
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    Then I press "Save and return to course"
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    Then I press "Save and return to course"
    And I click on "Edit" "link" in the ".repeatactivity .menubar" "css_element"
    And I click on "Delete" "link" in the ".repeatactivity" "css_element"
    And I press "Select and move items..."
    And I set the field "Subpage 1" to "1"
    And I press "Move selected"
    When I follow "Deleted items"
    Then ".oustudyplan-deleteditems .cell a[href='#']" "css_element" should exist

  Scenario: Check notification is show when moving subpage/repeat activity into itself.
    When I follow "Turn editing on"
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name | Subpage 1 |
    # Repeat "Subpage 1"
    When I follow "Add an activity or resource"
    And I click on "Repeat" "radio"
    And I click on "#chooserform input.submitbutton" "css_element"
    And I set the field "Source activity type" to "Subpage"
    And I set the field "originalcmid" to "Subpage 1"
    And I press "Save and return to course"
    And I press "Select and move items..."
    And I set the field "Subpage 1" to "1"
    And I press "Move selected"
    And I click on "li.activity:nth-of-type(1) a[href='#']" "css_element"
    Then I should see "It is not possible to move a subpage/repeat activity inside itself"
    Given I follow "Hide this notification"
    And I click on "li.activity:nth-of-type(2) a[href='#']" "css_element"
    Then I should see "It is not possible to move a subpage/repeat activity inside itself"
