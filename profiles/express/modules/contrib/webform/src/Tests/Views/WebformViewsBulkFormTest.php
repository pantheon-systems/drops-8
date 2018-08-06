<?php

namespace Drupal\webform\Tests\Views;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests a webform submission bulk form.
 *
 * @group webform
 * @see \Drupal\webform\Plugin\views\field\WebformSubmissionBulkForm
 */
class WebformViewsBulkFormTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_views'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests the webform views bulk form.
   */
  public function testViewsBulkForm() {
    $this->drupalLogin($this->adminSubmissionUser);

    // Check no submissions.
    $this->drupalGet('admin/structure/webform/test/views_bulk_form');
    $this->assertRaw('No submissions available.');

    // Create a test submission.
    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('contact');
    $sid = $this->postSubmissionTest($webform);
    $webform_submission = $this->loadSubmission($sid);

    $this->drupalLogin($this->adminSubmissionUser);

    // Check make sticky action.
    $this->assertFalse($webform_submission->isSticky(), 'Webform submission is not sticky');
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_make_sticky_action',
    ];
    $this->drupalPostForm('admin/structure/webform/test/views_bulk_form', $edit, t('Apply to selected items'));
    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertTrue($webform_submission->isSticky(), 'Webform submission has been made sticky');

    // Check make unsticky action.
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_make_unsticky_action',
    ];
    $this->drupalPostForm('admin/structure/webform/test/views_bulk_form', $edit, t('Apply to selected items'));
    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertFalse($webform_submission->isSticky(), 'Webform submission is not sticky anymore');

    // Check delete action.
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_delete_action',
    ];
    $this->drupalPostForm('admin/structure/webform/test/views_bulk_form', $edit, t('Apply to selected items'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertNull($webform_submission, '1: Webform submission has been deleted');

    // Check no submissions.
    $this->drupalGet('admin/structure/webform/test/views_bulk_form');
    $this->assertRaw('No submissions available.');
  }

}
