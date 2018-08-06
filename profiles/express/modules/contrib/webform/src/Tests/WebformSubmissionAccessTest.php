<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission access.
 *
 * @group Webform
 */
class WebformSubmissionAccessTest extends WebformTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test webform submission access permissions.
   */
  public function testPermissions() {
    global $base_path;

    $webform_id = 'contact';
    $webform = Webform::load('contact');

    /**************************************************************************/
    // Own submission permissions (authenticated).
    /**************************************************************************/

    $this->drupalLogin($this->ownWebformSubmissionUser);

    $edit = ['subject' => '{subject}', 'message' => '{message}'];
    $sid_1 = $this->postSubmission($webform, $edit);

    // Check view own previous submission message.
    $this->drupalGet('webform/' . $webform->id());
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}webform/{$webform_id}/submissions/{$sid_1}\">View your previous submission</a>.");

    // Check 'view own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_1}");
    $this->assertResponse(200);

    // Check 'edit own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_1}/edit");
    $this->assertResponse(200);

    // Check 'delete own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_1}/delete");
    $this->assertResponse(200);

    $sid_2 = $this->postSubmission($webform, $edit);

    // Check view own previous submissions message.
    $this->drupalGet('webform/' . $webform->id());
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}webform/{$webform_id}/submissions\">View your previous submissions</a>");

    // Check view own previous submissions.
    $this->drupalGet("webform/{$webform_id}/submissions");
    $this->assertResponse(200);
    $this->assertLinkByHref("{$base_path}webform/{$webform_id}/submissions/{$sid_1}");
    $this->assertLinkByHref("{$base_path}webform/{$webform_id}/submissions/{$sid_2}");

    // Check webform submission allowed.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/submission/{$sid_1}");
    $this->assertResponse(200);

    // Check webform results access denied.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/results/submissions");
    $this->assertResponse(403);

    // Check all results access denied.
    $this->drupalGet('/admin/structure/webform/submissions/manage');
    $this->assertResponse(403);

    /**************************************************************************/
    // Any submission permissions.
    /**************************************************************************/

    // Login as any user.
    $this->drupalLogin($this->anyWebformSubmissionUser);

    // Check webform results access allowed.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/results/submissions");
    $this->assertResponse(200);
    $this->assertLinkByHref("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_1}");
    $this->assertLinkByHref("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_2}");

    // Check webform submission access allowed.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/submission/{$sid_1}");
    $this->assertResponse(200);

    // Check all results access allowed.
    $this->drupalGet('/admin/structure/webform/submissions/manage');
    $this->assertResponse(200);

    /**************************************************************************/
    // Own submission permissions (anonymous).
    /**************************************************************************/

    $this->addWebformSubmissionOwnPermissionsToAnonymous();
    $this->drupalLogout();

    $edit = ['name' => '{name}', 'email' => 'example@example.com', 'subject' => '{subject}', 'message' => '{message}'];
    $sid_1 = $this->postSubmission($webform, $edit);

    // Check view own previous submission message.
    $this->drupalGet('webform/' . $webform->id());
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}webform/{$webform_id}/submissions/{$sid_1}\">View your previous submission</a>.");

    // Check 'view own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_1}");
    $this->assertResponse(200);

    // Check 'edit own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_1}/edit");
    $this->assertResponse(200);

    // Check 'delete own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_1}/delete");
    $this->assertResponse(200);

    $sid_2 = $this->postSubmission($webform, $edit);

    // Check view own previous submissions message.
    $this->drupalGet('webform/' . $webform->id());
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}webform/{$webform_id}/submissions\">View your previous submissions</a>");

    // Check view own previous submissions.
    $this->drupalGet("webform/{$webform_id}/submissions");
    $this->assertResponse(200);
    $this->assertLinkByHref("{$base_path}webform/{$webform_id}/submissions/{$sid_1}");
    $this->assertLinkByHref("{$base_path}webform/{$webform_id}/submissions/{$sid_2}");
  }

}
