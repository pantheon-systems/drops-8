<?php

namespace Drupal\webform_node\Tests;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform node access rules.
 *
 * @group WebformNode
 */
class WebformNodeAccessTest extends WebformNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform node access perimissions.
   *
   * @see \Drupal\webform\Tests\WebformSubmissionAccessTest::testWebformSubmissionAccessPermissions
   */
  public function testAccessPermissions() {
    global $base_path;

    // Create webform node that references the contact webform.
    $node = $this->createWebformNode('contact');
    $nid = $node->id();

    /**************************************************************************/
    // Own submission permissions (authenticated).
    /**************************************************************************/

    $this->drupalLogin($this->ownWebformSubmissionUser);

    $edit = ['subject' => '{subject}', 'message' => '{message}'];
    $sid_1 = $this->postNodeSubmission($node, $edit);

    // Check view own previous submission message.
    $this->drupalGet("node/{$nid}");
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}node/{$nid}/webform/submissions/{$sid_1}\">View your previous submission</a>.");

    // Check 'view own submission' permission.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_1}");
    $this->assertResponse(200);

    // Check 'edit own submission' permission.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_1}/edit");
    $this->assertResponse(200);

    // Check 'delete own submission' permission.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_1}/delete");
    $this->assertResponse(200);

    $sid_2 = $this->postNodeSubmission($node, $edit);

    // Check view own previous submissions message.
    $this->drupalGet("node/{$nid}");
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}node/{$nid}/webform/submissions\">View your previous submissions</a>");

    // Check view own previous submissions.
    $this->drupalGet("node/{$nid}/webform/submissions");
    $this->assertResponse(200);
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submissions/{$sid_1}");
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submissions/{$sid_2}");

    // Check webform results access denied.
    $this->drupalGet("node/{$nid}/webform/results/submissions");
    $this->assertResponse(403);

    /**************************************************************************/
    // Any submission permissions.
    /**************************************************************************/

    // Login as any user.
    $this->drupalLogin($this->anyWebformSubmissionUser);

    // Check webform results access allowed.
    $this->drupalGet("node/{$nid}/webform/results/submissions");
    $this->assertResponse(200);
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submission/{$sid_1}");
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submission/{$sid_2}");

    // Check webform submission access allowed.
    $this->drupalGet("node/{$nid}/webform/submission/{$sid_1}");
    $this->assertResponse(200);
  }

  /**
   * Tests webform node access rules.
   *
   * @see \Drupal\webform\Tests\WebformAccessTest::testAccessRules
   */
  public function testAccessRules() {
    $webform = Webform::load('contact');
    $node = $this->createWebformNode('contact');
    $nid = $node->id();

    // Log in normal user and get their rid.
    $this->drupalLogin($this->normalUser);
    $roles = $this->normalUser->getRoles(TRUE);
    $rid = reset($roles);
    $uid = $this->normalUser->id();

    // Add one submission to the Webform node.
    $edit = [
      'name' => '{name}',
      'email' => 'example@example.com',
      'subject' => '{subject}',
      'message' => '{message',
    ];
    $sid = $this->postNodeSubmission($node, $edit);

    // Check create authenticated/anonymous access.
    $webform->setAccessRules(Webform::getDefaultAccessRules())->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertFieldByName('name', $this->normalUser->getAccountName());
    $this->assertFieldByName('email', $this->normalUser->getEmail());

    $access_rules = [
      'create' => [
        'roles' => [],
        'users' => [],
      ],
    ] + Webform::getDefaultAccessRules();
    $webform->setAccessRules($access_rules)->save();

    // Check no access.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoFieldByName('name', $this->normalUser->getAccountName());
    $this->assertNoFieldByName('email', $this->normalUser->getEmail());

    $any_tests = [
      'node/{node}/webform/results/submissions' => 'view_any',
      'node/{node}/webform/results/download' => 'view_any',
      'node/{node}/webform/results/clear' => 'purge_any',
      'node/{node}/webform/submission/{webform_submission}' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/text' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/yaml' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/edit' => 'update_any',
      'node/{node}/webform/submission/{webform_submission}/delete' => 'delete_any',
    ];

    // Check that all the test paths are access denied for authenticated.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{node}', $nid, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      $this->drupalGet($path);
      $this->assertResponse(403, 'Webform returns access denied');
    }

    // Check access rules by role and user id.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{node}', $nid, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      // Check access rule via role.
      $access_rules = [
        $permission => [
          'roles' => [$rid],
          'users' => [],
        ],
      ] + Webform::getDefaultAccessRules();
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $this->assertResponse(200, 'Webform allows access via role access rules');

      // Check access rule via role.
      $access_rules = [
        $permission => [
          'roles' => [],
          'users' => [$uid],
        ],
      ] + Webform::getDefaultAccessRules();
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $this->assertResponse(200, 'Webform allows access via user access rules');
    }
  }

}
