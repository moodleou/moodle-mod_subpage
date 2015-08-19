@mod @mod_subpage @ou @ou_vle
Feature: Restrict access of subpage
  In order to use restrict access in a subpage section
  As a teacher
  I need to add a subpage and edit it, add restict access to a section of a subpage

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | city          | country |
      | teacher1 | Teacher   | 1        | Milton Keynes | GB      |
      | student1 | Student   | 1        | Bedford       | GB      |
      | student2 | Student   | 2        | Milton Keynes | GB      |
      | student3 | Student   | 3        | Berlin        | DE      |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"

  @javascript
  Scenario: Add subpage and items, then view it
    # Add a subpage
    Given I turn editing mode on
    Then I follow "Add an activity or resource"
    And I set the field "Subpage" to "1"
    And I press "Add"
    Then I should see "Adding a new Subpage" in the "h2" "css_element"
    And I set the field "Name" to "Subpage 001"
    And I set the field "Description" to "Description for Subpage 001"
    And I should not see "Restrict access"
    Then I press "Save and return to course"

    Given I turn editing mode off
    Then I follow "Subpage 001"
    And I should see "Subpage 001" in the "h2" "css_element"
    And I should see "Description for Subpage 001"

    And I follow "Course 1"
    Given I turn editing mode on
    Then I follow "Add an activity or resource"
    And I set the field "Page" to "1"
    And I press "Add"
    Then I should see "Adding a new Page" in the "h2" "css_element"
    And I set the following fields to these values:
      | Name                   | Page 001                 !
      | Description            | Description for Page 001 |
      | Page content           | Content for Page 001     |
    And I should not see "Restrict access"
    Then I press "Save and return to course"

    Given I turn editing mode off
    Then I follow "Subpage 001"
    And I should see "Subpage 001" in the "h2" "css_element"
    And I should see "Description for Subpage 001"
    Then I follow "Page 001"
    And I should see "Page 001" in the "h2" "css_element"
    And I should see "Content for Page 001"

  @javascript
  Scenario: Add a new subpage with access restrictions to a Page in a subpage and a section in a subpage
    # Update advanced settings by enabling conditional access.
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And I follow "Course 1"
    Given I turn editing mode on
    Then I follow "Add an activity or resource"
    And I set the field "Subpage" to "1"
    And I press "Add"
    Then I should see "Adding a new Subpage" in the "h2" "css_element"
    And I set the field "Name" to "Subpage 002"
    And I set the field "Description" to "Description for Subpage 002"
    Then I press "Save and return to course"

    When I follow "Subpage 002"
    Then I should see "Subpage 002" in the "h2" "css_element"
    And I should see "Description for Subpage 002"

    And I press "Add section"
    When I click on "Edit summary" "link" in the "//li[contains(@class, 'section')][1]" "xpath_element"
    And I set the following fields to these values:
      | Use default section name | 0                      |
      | id_name                  | Section 001            |
      | Summary                  | Summary of Section 001 |

    And I press "Add restriction..."
    Then I press "Date"

    And I press "Add restriction..."
    And I press "User profile"
    And I set the following fields to these values:
      | User profile field       | City/town     |
      | Method of comparison     | is equal to   |
      | Value to compare against | Milton Keynes |

    Then I press "Save changes"
    And I should see "Subpage 002" in the "h2" "css_element"
    And I should see "Summary of Section 001"
    And I should see "Not available unless:"

    # The second part of this string below is the current date, eg. 22 Dec 2014
    And I should see "It is on or after"
    And I should see "Your City/town is Milton Keynes"
    And I log out

    Given I log in as "student1"
    And I follow "Course 1"
    And I follow "Subpage 002"
    Then I should see "Subpage 002"
    And I should see "Section 001"
    And I should see "Summary of Section 001"
    And I should see "Not available unless: Your City/town is Milton Keynes"
    And I log out

    Given I log in as "student2"
    And I follow "Course 1"
    And I follow "Subpage 002"
    Then I should see "Subpage 002"
    And I should not see "Section 001"
    And I should not see "Summary of Section 001"
    And I should not see "Not available unless: Your City/town is Milton Keynes"