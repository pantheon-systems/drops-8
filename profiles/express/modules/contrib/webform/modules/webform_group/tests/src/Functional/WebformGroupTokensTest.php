<?php

namespace Drupal\Tests\webform_group\Functional;

use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform tokens access.
 *
 * @group webform_group
 */
class WebformGroupTokensTest extends WebformGroupBrowserTestBase {

  /**
   * Tests webform access tokens.
   */
  public function testWebformAccessTokens() {
    // Default group.
    $group = $this->createGroup(['type' => 'default']);

    // Webform node.
    $node = $this->createWebformNode('contact');

    // Add webform node to default group.
    $group->addContent($node, 'group_node:webform');

    // Users.
    $outsider_user = $this->createUser([], 'outsider', FALSE, ['mail' => 'outsider@example.com']);

    $member_user = $this->createUser([], 'member', FALSE, ['mail' => 'member@example.com']);
    $group->addMember($member_user);

    $custom_user = $this->createUser([], 'custom', FALSE, ['mail' => 'custom@example.com']);
    $group->addMember($custom_user, ['group_roles' => ['default-custom']]);

    $owner_user = $group->getOwner();

    $group->save();

    // Create a test submission.
    $edit = [
      'name' => 'name',
      'email' => 'name@example.com',
      'subject' => 'subject',
      'message' => 'message',
    ];
    $sid = $this->postNodeSubmission($node, $edit);
    $webform_submission = WebformSubmission::load($sid);

    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    $token_data['webform_group'] = $webform_submission;

    /**************************************************************************/
    // [webform_group:role:GROUP_ROLE] tokens.
    /**************************************************************************/

    // Enable group roles and owner.
    \Drupal::configFactory()->getEditable('webform_group.settings')
      ->set('mail.group_roles', ['member', 'custom', 'outsider'])
      ->set('mail.group_owner', TRUE)
      ->save();
    \Drupal::token()->resetInfo();

    // Check [webform_group:role:member] token.
    $result = $token_manager->replace('[webform_group:role:member]', $webform_submission, $token_data);
    $this->assertEqual(implode(',', [
      $owner_user->getEmail(),
      $member_user->getEmail(),
      $custom_user->getEmail(),
    ]), $result);

    // Check [webform_group:role:custom] token.
    $result = $token_manager->replace('[webform_group:role:custom]', $webform_submission, $token_data);
    $this->assertEqual(implode(',', [
      $custom_user->getEmail(),
    ]), $result);

    // Check [webform_group:role:outsider] token returns nothing.
    $result = $token_manager->replace('[webform_group:role:outsider]', $webform_submission, $token_data);
    $this->assertEqual('', $result);

    // Check [webform_group:owner:mail] token returns the group's owner email.
    $result = $token_manager->replace('[webform_group:owner:mail]', $webform_submission, $token_data);
    $this->assertEqual($owner_user->getEmail(), $result);

  }

}
