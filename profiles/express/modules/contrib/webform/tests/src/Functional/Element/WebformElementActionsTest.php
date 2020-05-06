<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform actions element.
 *
 * @group Webform
 */
class WebformElementActionsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_actions', 'test_element_actions_buttons'];

  /**
   * Tests actions element.
   */
  public function testActions() {
    $webform = Webform::load('test_element_actions');

    /**************************************************************************/
    /* Test webform actions */
    /**************************************************************************/

    // Get form.
    $this->drupalGet('/webform/test_element_actions');

    // Check custom actions.
    $this->assertRaw('<div style="border: 2px solid red; padding: 10px" data-drupal-selector="edit-actions-custom" class="form-actions webform-actions js-form-wrapper form-wrapper" id="edit-actions-custom">');
    $this->assertRaw('<input formnovalidate="formnovalidate" class="webform-button--draft custom-draft button js-form-submit form-submit" style="font-weight: bold" data-custom-draft data-drupal-selector="edit-actions-custom-draft" type="submit" id="edit-actions-custom-draft" name="op" value="{Custom draft}" />');
    $this->assertRaw('<input class="webform-button--next custom-wizard-next button js-form-submit form-submit" style="font-weight: bold" data-custom-wizard-next data-drupal-selector="edit-actions-custom-wizard-next" type="submit" id="edit-actions-custom-wizard-next" name="op" value="{Custom wizard next}" />');
    $this->assertRaw('<input formnovalidate="formnovalidate" class="webform-button--reset custom-reet button js-form-submit form-submit" style="font-weight: bold" data-custom-reset data-drupal-selector="edit-actions-custom-reset" type="submit" id="edit-actions-custom-reset" name="op" value="{Custom reset}" />');

    // Check wizard next.
    $this->assertRaw('id="edit-actions-wizard-next-wizard-next"');
    $this->assertNoRaw('id="edit-actions-wizard-prev-wizard-prev"');

    // Move to next page.
    $this->drupalPostForm(NULL, [], t('Next Page >'));

    // Check no wizard next.
    $this->assertNoRaw('id="edit-actions-wizard-next-wizard-next"');
    $this->assertRaw('id="edit-actions-wizard-prev-wizard-prev"');

    // Move to preview.
    $this->drupalPostForm(NULL, [], t('Preview'));

    // Check submit button.
    $this->assertRaw('id="edit-actions-submit-submit"');

    // Check reset button.
    $this->assertRaw('id="edit-actions-reset-reset"');

    // Submit form.
    $this->drupalPostForm(NULL, [], t('Submit'));
    $sid = $this->getLastSubmissionId($webform);

    // Check no actions.
    $this->assertNoRaw('form-actions');

    // Check custom update action.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet("/admin/structure/webform/manage/test_element_actions/submission/$sid/edit");
    $this->drupalPostForm(NULL, [], t('Next Page >'));
    $this->assertRaw('<input class="webform-button--submit custom-update button button--primary js-form-submit form-submit" style="font-weight: bold" data-custom-update data-drupal-selector="edit-actions-custom-submit" type="submit" id="edit-actions-custom-submit" name="op" value="{Custom update}" />');

    /**************************************************************************/
    /* Test actions buttons */
    /**************************************************************************/

    $webform = Webform::load('test_element_actions_buttons');

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/webform/test_element_actions_buttons');

    // Check draft button.
    $this->assertRaw('<input formnovalidate="formnovalidate" class="webform-button--draft draft_button_attributes button js-form-submit form-submit" style="color: blue" data-drupal-selector="edit-actions-draft" type="submit" id="edit-actions-draft" name="op" value="Save Draft" />');
    // Check next button.
    $this->assertRaw('<input class="webform-button--next wizard_next_button_attributes button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-actions-wizard-next" type="submit" id="edit-actions-wizard-next" name="op" value="Next Page &gt;" />');

    $this->drupalPostForm('/webform/test_element_actions_buttons', [], t('Next Page >'));

    // Check previous button.
    $this->assertRaw('<input formnovalidate="formnovalidate" class="webform-button--previous wizard_prev_button_attributes button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-actions-wizard-prev" type="submit" id="edit-actions-wizard-prev" name="op" value="&lt; Previous Page" />');
    // Check preview button.
    $this->assertRaw('<input class="webform-button--preview preview_next_button_attributes button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-actions-preview-next" type="submit" id="edit-actions-preview-next" name="op" value="Preview" />');

    $this->drupalPostForm(NULL, [], t('Preview'));

    // Check previous button.
    $this->assertRaw('<input formnovalidate="formnovalidate" class="webform-button--previous preview_prev_button_attributes button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-actions-preview-prev" type="submit" id="edit-actions-preview-prev" name="op" value="&lt; Previous" />');
    // Check submit button.
    $this->assertRaw('<input class="webform-button--submit form_submit_attributes button button--primary js-form-submit form-submit" style="color: green" data-drupal-selector="edit-actions-submit" type="submit" id="edit-actions-submit" name="op" value="Submit" />');

    $this->drupalPostForm(NULL, [], t('Submit'));
    $sid = $this->getLastSubmissionId($webform);

    // Check update button.
    $this->drupalGet("/admin/structure/webform/manage/test_element_actions_buttons/submission/$sid/edit");
    $this->drupalPostForm(NULL, [], t('Next Page >'));
    $this->assertRaw('<input class="webform-button--submit form_update_attributes button button--primary js-form-submit form-submit" style="color: purple" data-drupal-selector="edit-actions-submit" type="submit" id="edit-actions-submit" name="op" value="Save" />');
  }

}
