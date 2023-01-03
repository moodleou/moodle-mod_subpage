@mod @mod_subpage @ou @ou_vle
Feature: Moving items to and from subpages
  In order to use a subpage
  As a teacher
  I probably want to move things onto or off it sometimes

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format    |
      | Course 1 | C1        | studyplan |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I am using the OU theme
    And the following "activities" exist:
      | course | section | activity | name     | idnumber |
      | C1     | 1       | subpage  | Subpage1 | S1       |
    And I am on the "Course 1" course page logged in as teacher1
    And I follow "Turn editing on"

  @javascript
  Scenario: Add subpage and move some items onto it
    # Add a subpage.
    And I am on the "Subpage1" "subpage activity" page

    # Check the 'Move to' box shows nothing.
    When I press "Move items to this page"
    Then I should see "No modules were found"

    # Continue should go back to subpage.
    When I press "Continue"
    Then I should see "Subpage1" in the "#region-main h2" "css_element"

    # Go to course page and add a couple more subpages.
    When the following "activities" exist:
      | course | section | activity | name     | idnumber |
      | C1     | 1       | subpage  | Subpage2 | S2       |
      | C1     | 2       | subpage  | Subpage3 | S3       |

    # Should now be possible to add these (but not itself) to Subpage1.
    When I am on the "Subpage1" "subpage activity" page
    And I press "Move items to this page"
    Then I should see "Subpage2" in the ".mform" "css_element"
    And I should see "Subpage3" in the ".mform" "css_element"
    And I should not see "Subpage1" in the ".mform" "css_element"

    # Move Subpage2 to this page.
    When I click on "Subpage2" "checkbox"
    And I set the field "Move to" to "Section 1"
    And I press "Move selected items"

    # Should get to subpage1, with subpage2 on it
    Then I should see "Subpage1" in the "#region-main h2" "css_element"
    And I should see "Subpage2" in the "#region-main" "css_element"

    # Now go to subpage2. It shouldn't let you move subpage1 onto it...
    When I am on the "Subpage2" "subpage activity" page
    And I press "Move items to this page"
    And I should see "Subpage3" in the ".mform" "css_element"
    And I should not see "Subpage1" in the ".mform" "css_element"

  @javascript
  Scenario: Add subpage and move some items from it
    # Add subpages.
    Given the following "activities" exist:
      | course | section| activity | name     | idnumber |
      | C1     | 1      | subpage  | Subpage2 | S2       |
      | C1     | 2      | subpage  | Subpage3 | S3       |
    And I am on the "Subpage1" "subpage activity" page

    # Check the 'Move from' button is not present.
    Then I should not see "Move items from this page"

    # Put items on the page.
    When I press "Move items to this page"
    And I click on "Subpage2" "checkbox"
    And I click on "Subpage3" "checkbox"
    And I press "Move selected items"

    # Button appears. Press it...
    When I press "Move items from this page"
    Then I should see "Subpage3" in the ".mform" "css_element"
    And I should see "Subpage2" in the ".mform" "css_element"

    # Check the 'Move to' values for subpage and course page are there.
    Then the "Move to" select box should contain "Section 1"
    And the "Move to" select box should contain "Section 2"
    And the "Move to" select box should contain "Resources & forums"
    And the "Move to" select box should contain "Week 1"

    # Move them to a particular week
    When I click on "Subpage3" "checkbox"
    And I set the field "Move to" to "Week 3"
    And I press "Move selected items"

    # Should be on home page, with Subpage3 there.
    Then I should see "Subpage3" in the "#section-3" "css_element"

    # The subpage still has Subpage2.
    When I am on the "Subpage1" "subpage activity" page
    Then I should see "Subpage2" in the "#region-main" "css_element"
