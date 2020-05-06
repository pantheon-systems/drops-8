<?php

namespace Drupal\Tests\webform\Functional\Wizard;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform wizard progress and preview links.
 *
 * @group Webform
 */
class WebformWizardLinksTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_links'];

  /**
   * Test webform wizard progress and preview links.
   */
  public function testWizardLinks() {
    $this->drupalLogin($this->rootUser);

    $wizard_webform = Webform::load('test_form_wizard_links');

    // Check that first page has no links.
    $this->drupalGet('/webform/test_form_wizard_links');
    $this->assertCssSelect('.webform-wizard-pages-links');
    $this->assertNoFieldByName('webform_wizard_page-page_1', t('Edit'));
    $this->assertNoFieldByName('webform_wizard_page-page_2', t('Edit'));

    // Check that second page links to first page.
    $this->drupalPostForm('/webform/test_form_wizard_links', [], t('Next Page >'));
    $this->assertCssSelect('.webform-wizard-pages-links');
    $this->assertFieldByName('webform_wizard_page-page_1', t('Edit'));
    $this->assertNoFieldByName('webform_wizard_page-page_2', t('Edit'));

    // Check that preview links to first and second page.
    $this->drupalPostForm('/webform/test_form_wizard_links', [], t('Preview'));
    $this->assertCssSelect('.webform-wizard-pages-links');
    $this->assertFieldByName('webform_wizard_page-page_1', t('Edit'));
    $this->assertFieldByName('webform_wizard_page-page_2', t('Edit'));

    // Check that preview links are not wrapper in .form-actions.
    $this->assertNoCssSelect('.webform-wizard-pages-links.form-actions');

    // Check 'wizard_progress_link' setting.
    $this->assertCssSelect('.webform-wizard-pages-links[data-wizard-progress-link="true"]');

    // Check 'wizard_preview_link' setting.
    $this->assertCssSelect('.webform-wizard-pages-links[data-wizard-preview-link="true"]');

    // Set preview links to FALSE.
    $wizard_webform->setSetting('wizard_preview_link', FALSE)->save();

    // Check preview page is not linked.
    $this->drupalGet('/webform/test_form_wizard_links');
    $this->assertCssSelect('.webform-wizard-pages-links[data-wizard-progress-link="true"]');
    $this->assertNoCssSelect('.webform-wizard-pages-links[data-wizard-preview-link="true"]');

    // Set progress bar links to FALSE.
    $wizard_webform
      ->setSetting('wizard_progress_link', FALSE)
      ->setSetting('wizard_preview_link', TRUE)
      ->save();

    // Check progress bar is not linked.
    $this->drupalGet('/webform/test_form_wizard_links');
    $this->assertNoCssSelect('.webform-wizard-pages-links[data-wizard-progress-link="true"]');
    $this->assertCssSelect('.webform-wizard-pages-links[data-wizard-preview-link="true"]');

    // Set progress bar links and preview page to FALSE.
    $wizard_webform
      ->setSetting('wizard_progress_link', FALSE)
      ->setSetting('wizard_preview_link', FALSE)
      ->save();

    $this->drupalGet('/webform/test_form_wizard_links');
    $this->assertNoCssSelect('.webform-wizard-pages-links');
  }

}
