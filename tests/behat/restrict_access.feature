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
      | manager  | Manager   | 1        | Timbuktu      | ML      |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | manager  | C1     | manager        |
    And I am on the "Course 1" course page logged in as manager

  @javascript
  Scenario: Add subpage with restricted access, then view it
    # Turn availability off.
    Given the following config values are set as admin:
      | enableavailability | 0 |

    # Add a subpage
    Given I turn editing mode on
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name        | Subpage 001                 |
      | Description | Description for Subpage 001 |

    Given I turn editing mode off
    Then I am on the "Subpage 001" "subpage activity" page
    And I should see "Subpage 001" in the "h2" "css_element"
    And I should see "Description for Subpage 001"

    And I am on "Course 1" course homepage
    Given I turn editing mode on
    And I add a "Page" to section "0" and I fill the form with:
      | Name                   | Page 001                 |
      | Description            | Description for Page 001 |
      | Page content           | Content for Page 001     |

    Given I turn editing mode off
    When I am on the "Subpage 001" "subpage activity" page
    And I should see "Subpage 001" in the "h2" "css_element"
    And I should see "Description for Subpage 001"
    And I am on the "Page 001" "page activity" page
    Then I should see "Page 001"
    And I should see "Content for Page 001"

  @javascript
  Scenario: Add a new subpage with access restrictions to a Page in a subpage and a section in a subpage
    And I am on "Course 1" course homepage
    Given I turn editing mode on
    And I add a "Subpage" to section "0" and I fill the form with:
      | Name        | Subpage 002                 |
      | Description | Description for Subpage 002 |

    When I am on the "Subpage 002" "subpage activity" page
    Then I should see "Subpage 002" in the "h2" "css_element"
    And I should see "Description for Subpage 002"

    And I press "Add section"
    When I click on "Edit summary" "link" in the "//li[contains(@class, 'section')][1]" "xpath_element"
    And I set the following fields to these values:
      | id_name_customize | 1                      |
      | id_name_value     | Section 001            |
      | Summary           | Summary of Section 001 |

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

    When I am on the "Subpage 002" "subpage activity" page logged in as student1
    Then I should see "Subpage 002"
    And I should see "Section 001"
    And I should see "Summary of Section 001"
    And I should see "Not available unless: Your City/town is Milton Keynes"
    And I log out

    When I am on the "Subpage 002" "subpage activity" page logged in as student2
    Then I should see "Subpage 002"
    And "Section 001" "text" in the "content" "region" should not be visible
    And I should not see "Summary of Section 001"
    And I should not see "Not available unless: Your City/town is Milton Keynes"
