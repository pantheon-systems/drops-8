<?php

namespace Drupal\Tests\webform_group\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests webform group form access.
 *
 * @group webform_group
 */
class WebformGroupFormAccessTest extends WebformGroupBrowserTestBase {

  /**
   * Tests webform group form access.
   */
  public function testGroupFormAccess() {
    // Webform.
    $webform = Webform::load('contact');

    // Default group.
    $group = $this->createGroup(['type' => 'default']);

    // Webform node.
    $node = $this->createWebformNode('contact');
    $nid = $node->id();

    // Users.
    $outsider_user = $this->createUser();

    $member_user = $this->createUser();
    $group->addMember($member_user);

    $custom_user = $this->createUser();
    $group->addMember($custom_user, ['group_roles' => ['default-custom']]);

    $group->save();

    /**************************************************************************/
    // Create access.
    /**************************************************************************/

    // Logout.
    $this->drupalLogout();

    // Check that the form is displayed to anonymous user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertFieldByName('message');

    // Login as an outsider user.
    $this->drupalLogin($outsider_user);

    // Check that the form is displayed to outsider user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertFieldByName('message');

    // Login as a member user.
    $this->drupalLogin($member_user);

    // Check that the form is displayed to member user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertFieldByName('message');

    // Add webform node to group.
    $group->addContent($node, 'group_node:webform');
    $group->save();

    // Remove anonymous and authenticated user roles.
    $access = $webform->getAccessRules();
    $access['create']['roles'] = [];
    $access['create']['group_roles'] = [];
    $webform->setAccessRules($access);
    $webform->save();

    // Logout.
    $this->drupalLogout();

    // Check that the form is NOT displayed to anonymous user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertNoFieldByName('message');

    // Login as an outsider user.
    $this->drupalLogin($outsider_user);

    // Check that the form is NOT displayed to outsider user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertNoFieldByName('message');

    // Allow outsider to access webform.
    $access['create']['group_roles'][] = 'outsider';
    $webform->setAccessRules($access);
    $webform->save();

    // Check that the form is displayed to outsider user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertFieldByName('message');

    // Login as an member user.
    $this->drupalLogin($member_user);

    // Check that the form is NOT displayed to member user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertNoFieldByName('message');

    // Allow member to access webform.
    $access['create']['group_roles'][] = 'member';
    $webform->setAccessRules($access);
    $webform->save();

    // Check that the form is displayed to member user.
    $this->drupalGet('/node/' . $node->id());
    $this->assertFieldByName('message');

    /**************************************************************************/
    // Update any access.
    /**************************************************************************/

    $this->drupalLogout();

    // Check that anonymous can't access the submission page.
    $this->drupalGet("/node/$nid/webform/results/submissions");
    $this->assertResponse(403);

    // Login as an member user.
    $this->drupalLogin($member_user);

    // Check that member can't access the submission page.
    $this->drupalGet("/node/$nid/webform/results/submissions");
    $this->assertResponse(403);

    // Allow member to access submissions.
    $access['view_any']['group_roles'][] = 'member';
    $webform->setAccessRules($access);
    $webform->save();

    // Check that member can access the submission page.
    $this->drupalGet("/node/$nid/webform/results/submissions");
    $this->assertResponse(200);
  }

}
