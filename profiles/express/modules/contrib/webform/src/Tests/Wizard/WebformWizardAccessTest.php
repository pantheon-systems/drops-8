<?php

namespace Drupal\webform\Tests\Wizard;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform wizard with access controls for pages.
 *
 * @group Webform
 */
class WebformWizardAccessTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_access'];

  /**
   * Test webform custom wizard.
   */
  public function testConditionalWizard() {
    $webform = Webform::load('test_form_wizard_access');

    // Check anonymous user can access 'All' and 'Anonymous' form pages.
    $this->drupalGet('webform/test_form_wizard_access');
    $this->assertRaw('<b>All</b>');
    $this->assertRaw('<b>Anonymous</b>');
    $this->assertNoRaw('<b>Authenticated</b>');
    $this->assertNoRaw('<b>Private</b>');

    // Generate an anonymous submission.
    $this->drupalPostForm('webform/test_form_wizard_access', [], t('Next Page >'));
    $this->drupalPostForm(NULL, [], t('Submit'));
    $sid = $this->getLastSubmissionId($webform);

    // Check anonymous user can only view 'All' and 'Anonymous' submission data.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid");
    $this->assertRaw('test_form_wizard_access--page_all');
    $this->assertRaw('test_form_wizard_access--page_anonymous');
    $this->assertNoRaw('test_form_wizard_access--page_authenticated');
    $this->assertNoRaw('test_form_wizard_access--page_private');

    // Check anonymous user can only update 'All' and 'Anonymous' submission data.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid/edit");
    $this->assertRaw('<b>All</b>');
    $this->assertRaw('<b>Anonymous</b>');
    $this->assertNoRaw('<b>Authenticated</b>');
    $this->assertNoRaw('<b>Private</b>');

    // Login authenticated user.
    $this->drupalLogin($this->rootUser);

    // Check authenticated user can access 'All', 'Authenticated', and 'Private' form pages.
    $this->drupalGet('webform/test_form_wizard_access');
    $this->assertRaw('<b>All</b>');
    $this->assertNoRaw('<b>Anonymous</b>');
    $this->assertRaw('<b>Authenticated</b>');
    $this->assertRaw('<b>Private</b>');

    // Generate an authenticated submission.
    $this->drupalPostForm('webform/test_form_wizard_access', [], t('Next Page >'));
    $this->drupalPostForm(NULL, [], t('Next Page >'));
    $this->drupalPostForm(NULL, [], t('Submit'));
    $sid = $this->getLastSubmissionId($webform);

    // Check authenticated user can view 'All', 'Authenticated', and 'Private' form pages.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid");
    $this->assertRaw('test_form_wizard_access--page_all');
    $this->assertNoRaw('test_form_wizard_access--page_anonymous');
    $this->assertRaw('test_form_wizard_access--page_authenticated');
    $this->assertRaw('test_form_wizard_access--page_private');

    // Check anonymous user can only update 'All' and 'Anonymous' submission data.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid/edit");
    $this->assertRaw('<b>All</b>');
    $this->assertNoRaw('<b>Anonymous</b>');
    $this->assertRaw('<b>Authenticated</b>');
    $this->assertRaw('<b>Private</b>');
  }

}
