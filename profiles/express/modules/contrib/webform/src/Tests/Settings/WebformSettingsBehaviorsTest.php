<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform settings behaviors.
 *
 * @group Webform
 */
class WebformSettingsBehaviorsTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_form_submit_once',
    'test_form_disable_back',
    'test_form_unsaved',
    'test_form_disable_autocomplete',
    'test_form_novalidate',
    'test_form_autofocus',
    'test_form_details_toggle',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Disable description help icon.
    $this->config('webform.settings')->set('ui.description_help', FALSE)->save();
  }

  /**
   * Tests webform setting including confirmation.
   */
  public function testSettings() {
    $this->drupalLogin($this->rootUser);

    /**************************************************************************/
    /* Test webform submit once (form_submit_once) */
    /**************************************************************************/

    $webform_form_submit_once = Webform::load('test_form_submit_once');

    // Check webform has webform.form.submit_once.js.
    $this->drupalGet('webform/test_form_submit_once');
    $this->assertRaw('webform.form.submit_once.js');

    // Disable YAML specific form_submit_once setting.
    $webform_form_submit_once->setSetting('form_submit_once', FALSE);
    $webform_form_submit_once->save();

    // Check submit once checkbox is enabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_submit_once/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-submit-once" aria-describedby="edit-form-submit-once--description" type="checkbox" id="edit-form-submit-once" name="form_submit_once" value class="form-checkbox" />');

    // Check webform no longer has webform.form.submit_once.js.
    $this->drupalGet('webform/test_form_submit_once');
    $this->assertNoRaw('webform.form.submit_once.js');

    // Enable default (global) submit_once on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_submit_once', TRUE)
      ->save();

    // Check submit_once checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_submit_once/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-submit-once-disabled" aria-describedby="edit-form-submit-once-disabled--description" disabled="disabled" type="checkbox" id="edit-form-submit-once-disabled" name="form_submit_once_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Submit button is disabled immediately after it is clicked for all forms.');

    // Check webform has webform.form.submit_once.js.
    $this->drupalGet('webform/test_form_submit_once');
    $this->assertRaw('webform.form.submit_once.js');

    /**************************************************************************/
    /* Test webform disable back button (form_disable_back) */
    /**************************************************************************/

    $webform_form_disable_back = Webform::load('test_form_disable_back');

    // Check webform has webform.form.disable_back.js.
    $this->drupalGet('webform/test_form_disable_back');
    $this->assertRaw('webform.form.disable_back.js');

    // Disable YAML specific form_disable_back setting.
    $webform_form_disable_back->setSetting('form_disable_back', FALSE);
    $webform_form_disable_back->save();

    // Check disable_back checkbox is enabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_disable_back/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-disable-back" aria-describedby="edit-form-disable-back--description" type="checkbox" id="edit-form-disable-back" name="form_disable_back" value class="form-checkbox" />');

    // Check webform no longer has webform.form.disable_back.js.
    $this->drupalGet('webform/test_form_disable_back');
    $this->assertNoRaw('webform.form.disable_back.js');

    // Enable default (global) disable_back on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_disable_back', TRUE)
      ->save();

    // Check disable_back checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_disable_back/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-disable-back-disabled" aria-describedby="edit-form-disable-back-disabled--description" disabled="disabled" type="checkbox" id="edit-form-disable-back-disabled" name="form_disable_back_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Back button is disabled for all forms.');

    // Check webform has webform.form.disable_back.js.
    $this->drupalGet('webform/test_form_disable_back');
    $this->assertRaw('webform.form.disable_back.js');

    /**************************************************************************/
    /* Test webform (client-side) unsaved (form_unsaved) */
    /**************************************************************************/

    $webform_form_unsaved = Webform::load('test_form_unsaved');

    // Check webform has .js-webform-unsaved class.
    $this->drupalGet('webform/test_form_unsaved');
    $this->assertCssSelect('form.js-webform-unsaved', t('Form has .js-webform-unsaved class.'));

    // Disable YAML specific webform unsaved setting.
    $webform_form_unsaved->setSetting('form_unsaved', FALSE);
    $webform_form_unsaved->save();

    // Check novalidate checkbox is enabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_unsaved/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-unsaved" aria-describedby="edit-form-unsaved--description" type="checkbox" id="edit-form-unsaved" name="form_unsaved" value class="form-checkbox" />');

    // Check webform no longer has .js-webform-unsaved class.
    $this->drupalGet('webform/test_form_novalidate');
    $this->assertNoCssSelect('webform.js-webform-unsaved', t('Webform does not have .js-webform-unsaved class.'));

    // Enable default (global) unsaved on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_unsaved', TRUE)
      ->save();

    // Check unsaved checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_unsaved/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-unsaved-disabled" aria-describedby="edit-form-unsaved-disabled--description" disabled="disabled" type="checkbox" id="edit-form-unsaved-disabled" name="form_unsaved_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Unsaved warning is enabled for all forms.');

    // Check unsaved attribute added to webform.
    $this->drupalGet('webform/test_form_unsaved');
    $this->assertCssSelect('form.js-webform-unsaved', t('Form has .js-webform-unsaved class.'));

    /**************************************************************************/
    /* Test webform disable autocomplete (form_disable_autocomplete) */
    /**************************************************************************/

    // Check webform has autocomplete=off attribute.
    $this->drupalGet('webform/test_form_disable_autocomplete');
    $this->assertCssSelect('form[autocomplete="off"]', t('Form has autocomplete=off attribute.'));

    /**************************************************************************/
    /* Test webform (client-side) novalidate (form_novalidate) */
    /**************************************************************************/

    $webform_form_novalidate = Webform::load('test_form_novalidate');

    // Check webform has novalidate attribute.
    $this->drupalGet('webform/test_form_novalidate');
    $this->assertCssSelect('form[novalidate="novalidate"]', t('Form has the proper novalidate attribute.'));

    // Disable YAML specific webform client-side validation setting.
    $webform_form_novalidate->setSetting('form_novalidate', FALSE);
    $webform_form_novalidate->save();

    // Check novalidate checkbox is enabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_novalidate/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-novalidate" aria-describedby="edit-form-novalidate--description" type="checkbox" id="edit-form-novalidate" name="form_novalidate" value class="form-checkbox" />');
    $this->assertRaw('If checked, the <a href="http://www.w3schools.com/tags/att_form_novalidate.asp">novalidate</a> attribute, which disables client-side validation, will be added to this form.');

    // Check webform no longer has novalidate attribute.
    $this->drupalGet('webform/test_form_novalidate');
    $this->assertNoCssSelect('form[novalidate="novalidate"]', t('Webform have client-side validation enabled.'));

    // Enable default (global) disable client-side validation on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_novalidate', TRUE)
      ->save();

    // Check novalidate checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_novalidate/settings/form');
    $this->assertNoRaw('If checked, the <a href="http://www.w3schools.com/tags/att_form_novalidate.asp">novalidate</a> attribute, which disables client-side validation, will be added to this form.');
    $this->assertRaw('<input data-drupal-selector="edit-form-novalidate-disabled" aria-describedby="edit-form-novalidate-disabled--description" disabled="disabled" type="checkbox" id="edit-form-novalidate-disabled" name="form_novalidate_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Client-side validation is disabled for all forms.');

    // Check novalidate attribute added to webform.
    $this->drupalGet('webform/test_form_novalidate');
    $this->assertCssSelect('form[novalidate="novalidate"]', t('Form has the proper novalidate attribute.'));

    /**************************************************************************/
    /* Test autofocus (form_autofocus) */
    /**************************************************************************/

    // Check webform has autofocus class.
    $this->drupalGet('webform/test_form_autofocus');
    $this->assertCssSelect('.js-webform-autofocus');

    /**************************************************************************/
    /* Test webform details toggle (form_details_toggle) */
    /**************************************************************************/

    $webform_form_details_toggle = Webform::load('test_form_details_toggle');

    // Check webform has .webform-details-toggle class.
    $this->drupalGet('webform/test_form_details_toggle');
    $this->assertCssSelect('form.webform-details-toggle', t('Form has the .webform-details-toggle class.'));

    // Check details toggle checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_details_toggle/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-details-toggle-disabled" aria-describedby="edit-form-details-toggle-disabled--description" disabled="disabled" type="checkbox" id="edit-form-details-toggle-disabled" name="form_details_toggle_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Expand/collapse all (details) link is automatically added to all forms.');

    // Disable default (global) details toggle on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_details_toggle', FALSE)
      ->save();

    // Check .webform-details-toggle class still added to webform.
    $this->drupalGet('webform/test_form_details_toggle');
    $this->assertCssSelect('form.webform-details-toggle', t('Form has the .webform-details-toggle class.'));

    // Check details toggle checkbox is enabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_details_toggle/settings/form');
    $this->assertRaw('<input data-drupal-selector="edit-form-details-toggle" aria-describedby="edit-form-details-toggle--description" type="checkbox" id="edit-form-details-toggle" name="form_details_toggle" value checked="checked" class="form-checkbox" />');
    $this->assertRaw('If checked, an expand/collapse all (details) link will be added to this webform when there are two or more details elements available on the webform.');

    // Disable YAML specific webform details toggle setting.
    $webform_form_details_toggle->setSetting('form_details_toggle', FALSE);
    $webform_form_details_toggle->save();

    // Check webform does not hav .webform-details-toggle class.
    $this->drupalGet('webform/test_form_details_toggle');
    $this->assertNoCssSelect('webform.webform-details-toggle', t('Webform does not have the .webform-details-toggle class.'));
  }

}
