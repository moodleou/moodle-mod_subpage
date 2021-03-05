@mod @mod_subpage @ou @ou_vle
Feature: Basic usage of subpage
  In order to use a subpage
  As a teacher
  I need to add a subpage and edit it

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | maxsections | 200 | moodlecourse |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage

  @javascript
  Scenario: Add subpage and items, then view it
    # Add a subpage
    Given I turn editing mode on
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Test subpage |
    And I follow "Test subpage"
    Then I should see "Test subpage" in the "h2" "css_element"
    And I should see "Add an activity or resource"

    # Add a Label (need to do this manually because not on course page).
    When I add a "Label" to section "110" and I fill the form with:
      | Label text | Frog! |

    # I think this part might only work in OU Moodle; in core, you might get
    # dumped back to the course page at this point.
    Then I should see "Test subpage" in the "h2" "css_element"
    And I should see "Frog!"

    # Add a Page.
    When I add a "Page" to section "110" and I fill the form with:
      | Name         | My page  |
      | Description  | Mine     |
      | Page content | All mine |
    Then I should see "Test subpage" in the "h2" "css_element"
    And I should see "My page"

    # Turn editing off.
    When I turn editing mode off
    Then I should see "Frog!"
    And I should see "My page"

    # Click on the Page link just in case.
    When I follow "My page"
    Then I should see "All mine"

  @javascript
  Scenario: Edit and delete an item
    # Add item and check it has edit menu.
    Given I turn editing mode on
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Test subpage |
    And I follow "Test subpage"
    And I add a "Page" to section "110" and I fill the form with:
      | Name         | My page  |
      | Description  | Mine     |
      | Page content | All mine |
    Then I should see "Edit" in the "li.activity" "css_element"

    # Check basic edit settings feature.
    When I open the action menu in "My page" "list_item"
    And I click on "Edit settings" "link" in the "My page" "list_item"
    Then I should see "Updating Page"

    # Check there isn't an indent option.
    Given I follow "C1"
    And I follow "Test subpage"
    When I open the action menu in "My page" "list_item"

    # Try the Hide feature.
    When I click on "Hide" "link" in the "My page" "list_item"
    Then ".activityinstance > span > a.dimmed" "css_element" should exist

    When I open the action menu in "My page" "list_item"
    Then "Hide" "link" should not exist in the "My page" "list_item"

    When I click on "Show" "link" in the "My page" "list_item"
    Then ".activityinstance > span > a.dimmed" "css_element" should not exist

    # Delete the item
    When I open the action menu in "My page" "list_item"
    And I click on "Delete" "link" in the "My page" "list_item"
    Then I should see "Are you sure"

    When I press "Yes"
    # If it's broken there is sometimes a JS window that appears here.
    Then I should not see "Error"
    And I should not see "My page" in the ".content" "css_element"

  @javascript
  Scenario: Hide sections
    # Set up subpage and add a page.
    Given I turn editing mode on
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Test subpage |
    And I follow "Test subpage"
    And I add a "Page" to section "110" and I fill the form with:
      | Name         | My page  |
      | Description  | Mine     |
      | Page content | All mine |
    Then "Hide" "link" should exist in the ".section" "css_element"

    # Hide the section.
    When I click on "Hide" "link" in the ".section" "css_element"
    Then "Show" "link" should exist in the ".section" "css_element"

    # As student, page should be hidden.
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test subpage"
    Then I should not see "My page"

    # Back in as teacher, can see it still.
    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test subpage"
    Then I should see "My page"

    # Show it again.
    And I turn editing mode on
    When I click on "Show" "link" in the ".section" "css_element"
    Then "Hide" "link" should exist in the ".section" "css_element"

    # Stealth it.
    When I click on "//input[@title='Stealth']" "xpath_element" in the ".section" "css_element"
    Then "//input[@title='Un-stealth']" "xpath_element" should exist in the ".section" "css_element"

    # Back as student - should be hidden again.
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test subpage"
    Then I should not see "My page" in the ".content" "css_element"

  @javascript
  Scenario: Edit and move sections
    # Set up subpage and add a page.
    Given I turn editing mode on
    And I add a "Subpage" to section "1" and I fill the form with:
      | Name | Test subpage |
    And I follow "Test subpage"
    And I add a "Page" to section "110" and I fill the form with:
      | Name         | My page  |
      | Description  | Mine     |
      | Page content | All mine |

    # Edit settings.
    When I click on "Edit summary" "link" in the "//li[contains(@class, 'section')][1]" "xpath_element"
    And I set the field "id_name_customize" to "1"
    And I set the following fields to these values:
    | name[value]              | SECTION1      |
    | Summary                  | SUMMARY1      |
    And I press "Save changes"
    Then I should see "SECTION1"
    And I should see "SUMMARY1"

    # Add a section.
    When I set the field "sectiontitle" to "Section title for section 2"
    And I press "Add section"
    Then "//li[contains(@class, 'section')][2]" "xpath_element" should exist
    And I should see "Section title for section 2"

    # Check the title and edit settings.
    When I click on "Edit summary" "link" in the "//li[contains(@class, 'section')][2]" "xpath_element"
    Then I should see "Section title for section 2"
    Given I set the following fields to these values:
    | name[value]              | Section 2  |
    | Summary                  | Summary 2  |
    And I press "Save changes"
    Then I should not see "Section title for section 2"
    But I should see "Section 2"
    And I should see "Summary 2"

    # Move the section up and then down (flips them).
    When I click on "Move up" "link" in the "//li[contains(@class, 'section')][2]" "xpath_element"
    Then I should see "SECTION1" in the "//li[contains(@class, 'section')][2]" "xpath_element"
    And I should see "My page" in the "//li[contains(@class, 'section')][2]" "xpath_element"
    When I click on "Move down" "link" in the "//li[contains(@class, 'section')][1]" "xpath_element"
    Then I should see "SECTION1" in the "//li[contains(@class, 'section')][1]" "xpath_element"
    And I should see "My page" in the "//li[contains(@class, 'section')][1]" "xpath_element"
