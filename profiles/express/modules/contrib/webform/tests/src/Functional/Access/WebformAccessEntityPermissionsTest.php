<?php

namespace Drupal\Tests\webform\Functional\Access;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform entity permissions.
 *
 * @group Webform
 */
class WebformAccessEntityPermissionsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * Tests webform entity access controls.
   */
  public function testAccessControlHandler() {
    $own_account = $this->drupalCreateUser([
      'access webform overview',
      'create webform',
      'edit own webform',
      'delete own webform',
    ]);
    $any_account = $this->drupalCreateUser([
      'access webform overview',
      'create webform',
      'edit any webform',
      'delete any webform',
    ]);

    /**************************************************************************/

    // Login as user who can access own webform.
    $this->drupalLogin($own_account);

    // Check create own webform.
    $this->drupalPostForm('/admin/structure/webform/add', ['id' => 'test_own', 'title' => 'test_own'], t('Save'));

    // Check webform submission overview contains own webform.
    $this->drupalGet('/admin/structure/webform');
    $this->assertRaw('test_own');

    // Add test element to own webform.
    $this->drupalPostForm('/admin/structure/webform/manage/test_own', ['elements' => "test:\n  '#markup': 'test'"], t('Save'));

    // Check duplicate own webform.
    $this->drupalGet('/admin/structure/webform/manage/test_own/duplicate');
    $this->assertResponse(200);

    // Check delete own webform.
    $this->drupalGet('/admin/structure/webform/manage/test_own/delete');
    $this->assertResponse(200);

    // Check access own webform submissions.
    $this->drupalGet('/admin/structure/webform/manage/test_own/results/submissions');
    $this->assertResponse(200);

    // Login as user who can access any webform.
    $this->drupalLogin($any_account);

    // Check duplicate any webform.
    $this->drupalGet('/admin/structure/webform/manage/test_own/duplicate');
    $this->assertResponse(200);

    // Check delete any webform.
    $this->drupalGet('/admin/structure/webform/manage/test_own/delete');
    $this->assertResponse(200);

    // Check access any webform submissions.
    $this->drupalGet('/admin/structure/webform/manage/test_own/results/submissions');
    $this->assertResponse(200);

    // Change the owner of the webform to 'any' user.
    $own_webform = Webform::load('test_own');
    $own_webform->setOwner($any_account)->save();

    // Login as user who can access own webform.
    $this->drupalLogin($own_account);

    // Check webform submission overview does not contains any webform.
    $this->drupalGet('/admin/structure/webform');
    $this->assertNoRaw('test_own');

    // Check duplicate denied any webform.
    $this->drupalGet('/admin/structure/webform/manage/test_own/duplicate');
    $this->assertResponse(403);

    // Check delete denied any webform.
    $this->drupalGet('/admin/structure/webform/manage/test_own/delete');
    $this->assertResponse(403);

    // Check access denied any webform submissions.
    $this->drupalGet('/admin/structure/webform/manage/test_own/results/submissions');
    $this->assertResponse(403);
  }

}
