<?php

namespace Drupal\Tests\webform_access\Functional;

use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform tokens access.
 *
 * @group WebformAccess
 */
class WebformAccessTokensTest extends WebformAccessBrowserTestBase {

  /**
   * Tests webform access tokens.
   */
  public function testWebformAccessTokens() {
    // Add both users to employee group.
    foreach ($this->users as $account) {
      $this->groups['employee']->addUserId($account->id());
    }
    $employee_admin_user = $this->drupalCreateUser([], 'employee_admin_user');
    $this->groups['employee']->addAdminId($employee_admin_user->id());
    $this->groups['employee']->addEmail('employee_admin_custom@test.com');
    $this->groups['employee']->save();

    // Add other user to manager group.
    $other_user = $this->drupalCreateUser([], 'other_user');
    $this->groups['manager']->addUserId($other_user->id());
    $manager_admin_user = $this->drupalCreateUser([], 'manager_admin_user');
    $this->groups['manager']->addAdminId($manager_admin_user->id());
    $this->groups['manager']->addEmail('manager_admin_custom@test.com');
    $this->groups['manager']->save();

    // Create a submission.
    $edit = [
      'name' => 'name',
      'email' => 'name@example.com',
      'subject' => 'subject',
      'message' => 'message',
    ];
    $sid = $this->postNodeSubmission($this->nodes['contact_01'], $edit);
    $webform_submission = WebformSubmission::load($sid);

    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    $token_data['webform_access'] = $webform_submission;

    /**************************************************************************/
    // [webform_access:type:TYPE] tokens.
    /**************************************************************************/

    // Check [webform_access:type:employee] token.
    $result = $token_manager->replace('[webform_access:type:employee]', $webform_submission, $token_data);
    $this->assertEqual('customer_user@example.com,employee_user@example.com,manager_user@example.com,employee_admin_custom@test.com', $result);

    // Check [webform_access:type:employee:users] token.
    $result = $token_manager->replace('[webform_access:type:employee:users]', $webform_submission, $token_data);
    $this->assertEqual('customer_user@example.com,employee_user@example.com,manager_user@example.com', $result);

    // Check [webform_access:type:employee:emails] token.
    $result = $token_manager->replace('[webform_access:type:employee:emails]', $webform_submission, $token_data);
    $this->assertEqual('employee_admin_custom@test.com', $result);

    // Check [webform_access:type:employee:admins] token.
    $result = $token_manager->replace('[webform_access:type:employee:admins]', $webform_submission, $token_data);
    $this->assertEqual('employee_admin_user@example.com', $result);

    // Check [webform_access:type:employee:all] token.
    $result = $token_manager->replace('[webform_access:type:employee:all]', $webform_submission, $token_data);
    $this->assertEqual('employee_admin_user@example.com,customer_user@example.com,employee_user@example.com,manager_user@example.com,employee_admin_custom@test.com', $result);

    // Check [webform_access:type:manager] token.
    $result = $token_manager->replace('[webform_access:type:manager]', $webform_submission, $token_data);
    $this->assertEqual('other_user@example.com,manager_admin_custom@test.com', $result);

    /**************************************************************************/
    // [webform_access:type] tokens.
    /**************************************************************************/

    // Check [webform_access:type] token.
    $result = $token_manager->replace('[webform_access:type]', $webform_submission, $token_data);
    $this->assertEqual('customer_user@example.com,employee_user@example.com,manager_user@example.com,other_user@example.com,employee_admin_custom@test.com,manager_admin_custom@test.com', $result);

    // Check [webform_access:type:admins] token.
    $result = $token_manager->replace('[webform_access:admins]', $webform_submission, $token_data);
    $this->assertEqual('employee_admin_user@example.com,manager_admin_user@example.com', $result);

    // Check [webform_access:type:users] token.
    $result = $token_manager->replace('[webform_access:users]', $webform_submission, $token_data);
    $this->assertEqual('customer_user@example.com,employee_user@example.com,manager_user@example.com,other_user@example.com', $result);

    // Check [webform_access:type:emails] token.
    $result = $token_manager->replace('[webform_access:emails]', $webform_submission, $token_data);
    $this->assertEqual('employee_admin_custom@test.com,manager_admin_custom@test.com', $result);

    // Check [webform_access:type:all] token.
    $result = $token_manager->replace('[webform_access:all]', $webform_submission, $token_data);
    $this->assertEqual('employee_admin_user@example.com,manager_admin_user@example.com,customer_user@example.com,employee_user@example.com,manager_user@example.com,other_user@example.com,employee_admin_custom@test.com,manager_admin_custom@test.com', $result);
  }

}
