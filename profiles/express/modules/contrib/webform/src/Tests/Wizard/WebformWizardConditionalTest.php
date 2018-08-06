<?php

namespace Drupal\webform\Tests\Wizard;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform conditional wizard.
 *
 * @group Webform
 */
class WebformWizardConditionalTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_conditional'];

  /**
   * Test webform custom wizard.
   */
  public function testConditionalWizard() {
    $webform = Webform::load('test_form_wizard_conditional');
    $this->drupalGet('webform/test_form_wizard_conditional');

    // Check hiding page 1, 3, and 5.
    $edit = [
      'trigger_pages[page_1]' => FALSE,
      'trigger_pages[page_3]' => FALSE,
      'trigger_pages[page_5]' => FALSE,
    ];
    $this->drupalPostForm('webform/test_form_wizard_conditional', $edit, 'Next Page >');
    $this->assertCurrentPage('Page 2');
    $this->drupalPostForm(NULL, [], t('Next Page >'));
    $this->assertCurrentPage('Page 4');
    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertCurrentPage('Complete');
    $sid = $this->getLastSubmissionId($webform);
    $this->assert(!empty($sid));

    // Check hiding all pages and skipping to complete.
    $edit = [
      'trigger_none' => TRUE,
    ];
    $this->drupalPostForm('webform/test_form_wizard_conditional', $edit, 'Next Page >');
    $this->assertCurrentPage('Complete');
    $sid = $this->getLastSubmissionId($webform);
    $this->assert(!empty($sid));

    // Enable preview.
    $webform->setSetting('preview', 1);
    $webform->save();

    // Check hiding all pages and skipping to preview.
    $edit = [
      'trigger_none' => TRUE,
    ];
    $this->drupalPostForm('webform/test_form_wizard_conditional', $edit, 'Next Page >');
    $this->assertCurrentPage('Preview');
  }

}
