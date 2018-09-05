Feature: Performance Settings
  In order to know that the Pantheon Service Manager has been installed
  As a website user
  I want Pantheon to set up a reasonable default value for Page cache maximum age

  @api
  Scenario: Check to see that clearing the cache prints the initial message
    Given I am logged in as a user with the "administrator" role
    Given I am on "/admin/config/development/performance"
    And I press "Clear all caches"
    Then I should not see "Set the Page cache maximum age to 15 minutes"
