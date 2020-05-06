<?php

namespace Drupal\Tests\webform\Functional\Wizard;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform basic wizard.
 *
 * @group Webform
 */
class WebformWizardBasicTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_basic'];

  /**
   * Test webform basic wizard.
   */
  public function testBasicWizard() {
    $this->drupalLogin($this->rootUser);

    // Create a wizard submission.
    $wizard_webform = Webform::load('test_form_wizard_basic');
    $this->drupalPostForm('/webform/test_form_wizard_basic', [], t('Next Page >'));
    $this->drupalPostForm(NULL, [], t('Submit'));
    $sid = $this->getLastSubmissionId($wizard_webform);

    // Check confirmation message for wizard form.
    $this->drupalGet("admin/structure/webform/manage/test_form_wizard_basic/submission/$sid/edit");
    $this->assertCurrentPage('Page 1', 'page_1');
    $this->drupalPostForm(NULL, [], t('Next Page >'));
    $this->assertCurrentPage('Page 2', 'page_2');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertRaw('Submission updated in <em class="placeholder">Test: Webform: Wizard basic</em>.');
    $this->assertCurrentPage('Page 1', 'page_1');

    // Check access to 'Edit: All' tab for wizard.
    $this->drupalGet("admin/structure/webform/manage/test_form_wizard_basic/submission/$sid/edit/all");
    $this->assertResponse(200);

    // Check that page 1 and 2 are displayed.
    $this->assertRaw('<summary role="button" aria-controls="edit-page-1" aria-expanded="false" aria-pressed="false">Page 1</summary>');
    $this->assertRaw('<summary role="button" aria-controls="edit-page-2" aria-expanded="false" aria-pressed="false">Page 2</summary>');

    // Create a contact form submission.
    $contact_webform = Webform::load('contact');
    $sid = $this->postSubmissionTest($contact_webform);

    // Check access denied to 'Edit: All' tab for simple form.
    $this->drupalGet("admin/structure/webform/manage/contact/submission/$sid/edit/all");
    $this->assertResponse(403);

    // Enable tracking by name.
    $wizard_webform
      ->setSetting('wizard_track', 'name')
      ->setSetting('confirmation_type', 'inline')
      ->save();

    // Check next page.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $this->assertNoRaw('data-webform-wizard-current-page');
    $this->assertRaw('data-webform-wizard-page="page_2" data-drupal-selector="edit-wizard-next"');

    // Check next and previous page.
    $this->drupalPostForm('/webform/test_form_wizard_basic', [], t('Next Page >'));
    $this->assertRaw('data-webform-wizard-current-page="page_2"');
    $this->assertRaw('data-webform-wizard-page="page_1" data-drupal-selector="edit-wizard-prev"');
    $this->assertRaw('data-webform-wizard-page="webform_preview" data-drupal-selector="edit-preview-next"');

    $this->drupalPostForm(NULL, [], t('Preview'));
    $this->assertRaw('data-webform-wizard-current-page="webform_preview"');
    $this->assertRaw('data-webform-wizard-page="page_2" data-drupal-selector="edit-preview-prev"');
    $this->assertRaw('data-webform-wizard-page="webform_confirmation" data-drupal-selector="edit-submit"');

    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertRaw('data-webform-wizard-current-page="webform_confirmation"');

    // Enable tracking by index.
    $wizard_webform->setSetting('wizard_track', 'index')->save();

    // Check next page.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $this->assertRaw('data-webform-wizard-page="2" data-drupal-selector="edit-wizard-next"');

    // Check next and previous page.
    $this->drupalPostForm('/webform/test_form_wizard_basic', [], t('Next Page >'));
    $this->assertRaw('data-webform-wizard-current-page="2"');
    $this->assertRaw('data-webform-wizard-page="1" data-drupal-selector="edit-wizard-prev"');
    $this->assertRaw('data-webform-wizard-page="3" data-drupal-selector="edit-preview-next"');

    $this->drupalPostForm(NULL, [], t('Preview'));
    $this->assertRaw('data-webform-wizard-current-page="3"');
    $this->assertRaw('data-webform-wizard-page="2" data-drupal-selector="edit-preview-prev"');
    $this->assertRaw('data-webform-wizard-page="4" data-drupal-selector="edit-submit"');

    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertRaw('data-webform-wizard-current-page="4"');
  }

}
