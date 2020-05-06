<?php

namespace Drupal\Tests\webform_group\Functional;

use Drupal\Tests\webform\Traits\WebformSubmissionViewAccessTrait;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform group access submission views.
 *
 * @group webform_group
 */
class WebformGroupSubmissionAccessTest extends WebformGroupBrowserTestBase {

  use WebformSubmissionViewAccessTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_group', 'webform_group_test', 'views', 'webform_test_views'];

  /**
   * Tests webform group access submission views.
   */
  public function testWebformGroupAccessSubmissionViewsTest() {
    // Webform.
    $webform = Webform::load('contact');

    // Set access rules.
    $access = $webform->getAccessRules();
    $access['create']['roles'] = [];
    $access['view_own']['group_roles'] = ['member'];
    $access['view_any']['group_roles'] = ['custom'];
    $webform->setAccessRules($access);
    $webform->save();

    // Default group.
    $group = $this->createGroup(['type' => 'default']);

    // Webform node.
    $node = $this->createWebformNode('contact');

    // Add webform node to group.
    $group->addContent($node, 'group_node:webform');
    $group->save();

    // Users.
    $users = [];

    $users['outsider'] = $this->createUser();

    $users['member'] = $this->createUser();
    $group->addMember($users['member']);

    $users['custom'] = $this->createUser();
    $group->addMember($users['custom'], ['group_roles' => ['default-custom']]);

    $group->save();

    /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
    $submission_generate = \Drupal::service('webform_submission.generate');
    foreach ($users as $user) {
      WebformSubmission::create([
        'webform_id' => 'contact',
        'entity_type' => 'node',
        'entity_id' => $node->id(),
        'uid' => $user->id(),
        'data' => $submission_generate->getData($webform),
      ])->save();
    }

    $this->checkUserSubmissionAccess($webform, $users);
  }

}
