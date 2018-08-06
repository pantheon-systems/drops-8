<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform submission form settings.
 *
 * @group Webform
 */
class WebformSubmissionFormSettingsTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'node', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_form_assets',
    'test_form_opening',
    'test_form_closed',
    'test_form_prepopulate',
    'test_form_submit_once',
    'test_form_disable_back',
    'test_form_unsaved',
    'test_form_disable_autocomplete',
    'test_form_novalidate',
    'test_form_details_toggle',
    'test_form_autofocus',
    'test_form_preview',
    'test_form_results_disabled',
    'test_token_update',
    'test_form_limit',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform setting including confirmation.
   */
  public function testSettings() {
    // Login the admin user.
    $this->drupalLogin($this->adminWebformUser);

    /**************************************************************************/
    /* Test assets (CSS / JS) */
    /**************************************************************************/

    $webform_assets = Webform::load('test_form_assets');

    // Check has CSS and JavaScript.
    $this->drupalGet('webform/test_form_assets');
    $this->assertRaw('<link rel="stylesheet" href="' . base_path() . 'webform/test_form_assets/assets/css?v=');
    $this->assertRaw('<script src="' . base_path() . 'webform/test_form_assets/assets/javascript?v=');

    // Clear CSS and JavaScript.
    $webform_assets->setCss('')->setJavaScript('')->save();

    // Check has no CSS or JavaScript.
    $this->drupalGet('webform/test_form_assets');
    $this->assertNoRaw('<link rel="stylesheet" href="' . base_path() . 'webform/test_form_assets/assets/css?v=');
    $this->assertNoRaw('<script src="' . base_path() . 'webform/test_form_assets/assets/javascript?v=');

    // Add global CSS and JS on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('assets.css', '/**/')
      ->set('assets.javascript', '/**/')
      ->save();

    // Check has global CSS and JavaScript.
    $this->drupalGet('webform/test_form_assets');
    $this->assertRaw('<link rel="stylesheet" href="' . base_path() . 'webform/test_form_assets/assets/css?v=');
    $this->assertRaw('<script src="' . base_path() . 'webform/test_form_assets/assets/javascript?v=');

    /**************************************************************************/
    /* Test next_serial */
    /**************************************************************************/

    $webform_contact = Webform::load('contact');

    // Set next serial to 99.
    $this->drupalPostForm('admin/structure/webform/manage/contact/settings', ['next_serial' => 99], t('Save'));

    // Check next serial is 99.
    $sid = $this->postSubmissionTest($webform_contact);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->serial(), 99);

    // Check that next serial is set to max serial.
    $this->drupalPostForm('admin/structure/webform/manage/contact/settings', ['next_serial' => 1], t('Save'));
    $this->assertRaw('The next submission number was increased to 100 to make it higher than existing submissions.');

    /**************************************************************************/
    /* Test webform opening (status=scheduled) */
    /**************************************************************************/

    $webform_opening = Webform::load('test_form_opening');

    $this->drupalLogout();

    // Check webform open message is displayed.
    $this->assertTrue($webform_opening->isClosed());
    $this->assertTrue($webform_opening->isOpening());
    $this->drupalGet('webform/test_form_opening');
    $this->assertNoRaw('This message should not be displayed)');
    $this->assertRaw('This form is opening soon.');

    // Check webform closed message is displayed.
    $webform_opening->setSetting('form_open_message', '');
    $webform_opening->save();
    $this->drupalGet('webform/test_form_opening');
    $this->assertNoRaw('This form is opening soon.');
    $this->assertRaw('This form has not yet been opened to submissions.');

    $this->drupalLogin($this->adminWebformUser);

    // Check webform is not closed for admins and warning is displayed.
    $this->drupalGet('webform/test_form_opening');
    $this->assertRaw('This message should not be displayed');
    $this->assertNoRaw('This form has not yet been opened to submissions.');
    $this->assertRaw('Only submission administrators are allowed to access this webform and create new submissions.');

    // Check webform opening message is not displayed.
    $webform_opening->set('status', WebformInterface::STATUS_OPEN);
    $webform_opening->save();
    $this->assertFalse($webform_opening->isClosed());
    $this->assertTrue($webform_opening->isOpen());
    $this->drupalGet('webform/test_form_opening');
    $this->assertRaw('This message should not be displayed');
    $this->assertNoRaw('This form has not yet been opened to submissions.');
    $this->assertNoRaw('Only submission administrators are allowed to access this webform and create new submissions.');

    /**************************************************************************/
    /* Test webform closed (status=closed) */
    /**************************************************************************/

    $webform_closed = Webform::load('test_form_closed');

    $this->drupalLogout();

    // Check webform closed message is displayed.
    $this->assertTrue($webform_closed->isClosed());
    $this->assertFalse($webform_closed->isOpen());
    $this->drupalGet('webform/test_form_closed');
    $this->assertNoRaw('This message should not be displayed)');
    $this->assertRaw('This form is closed.');

    // Check webform closed message is displayed.
    $webform_closed->setSetting('form_close_message', '');
    $webform_closed->save();
    $this->drupalGet('webform/test_form_closed');
    $this->assertNoRaw('This form is closed.');
    $this->assertRaw('Sorry...This form is closed to new submissions.');

    $this->drupalLogin($this->adminWebformUser);

    // Check webform is not closed for admins and warning is displayed.
    $this->drupalGet('webform/test_form_closed');
    $this->assertRaw('This message should not be displayed');
    $this->assertNoRaw('This form is closed.');
    $this->assertRaw('Only submission administrators are allowed to access this webform and create new submissions.');

    // Check webform closed message is not displayed.
    $webform_closed->set('status', WebformInterface::STATUS_OPEN);
    $webform_closed->save();
    $this->assertFalse($webform_closed->isClosed());
    $this->assertTrue($webform_closed->isOpen());
    $this->drupalGet('webform/test_form_closed');
    $this->assertRaw('This message should not be displayed');
    $this->assertNoRaw('This form is closed.');
    $this->assertNoRaw('Only submission administrators are allowed to access this webform and create new submissions.');

    /**************************************************************************/
    /* Test webform prepopulate (form_prepopulate) */
    /**************************************************************************/

    $webform_prepopulate = Webform::load('test_form_prepopulate');

    // Check prepopulation of an element.
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['name' => 'John', 'colors' => ['red', 'white']]]);
    $this->assertFieldByName('name', 'John');
    $this->assertFieldChecked('edit-colors-red');
    $this->assertFieldChecked('edit-colors-white');
    $this->assertNoFieldChecked('edit-colors-blue');

    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['name' => 'John', 'colors' => 'red']]);
    $this->assertFieldByName('name', 'John');
    $this->assertFieldChecked('edit-colors-red');
    $this->assertNoFieldChecked('edit-colors-white');
    $this->assertNoFieldChecked('edit-colors-blue');

    // Check disabling prepopulation of an element.
    $webform_prepopulate->setSetting('form_prepopulate', FALSE);
    $webform_prepopulate->save();
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['name' => 'John']]);
    $this->assertFieldByName('name', '');

    /**************************************************************************/
    /* Test webform prepopulate source entity (form_prepopulate_source_entity) */
    /**************************************************************************/

    // Check prepopulating source entity.
    $this->drupalPostForm('webform/test_form_prepopulate', [], t('Submit'), ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'contact']]);
    $sid = $this->getLastSubmissionId($webform_prepopulate);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertNotNull($webform_submission->getSourceEntity());
    if ($webform_submission->getSourceEntity()) {
      $this->assertEqual($webform_submission->getSourceEntity()->getEntityTypeId(), 'webform');
      $this->assertEqual($webform_submission->getSourceEntity()->id(), 'contact');
    }

    // Check disabling prepopulation source entity.
    $webform_prepopulate->setSetting('form_prepopulate_source_entity', FALSE);
    $webform_prepopulate->save();
    $this->drupalPostForm('webform/test_form_prepopulate', [], t('Submit'), ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'contact']]);
    $sid = $this->getLastSubmissionId($webform_prepopulate);
    $webform_submission = WebformSubmission::load($sid);
    $this->assert(!$webform_submission->getSourceEntity());

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
    $this->drupalGet('admin/structure/webform/manage/test_form_submit_once/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-submit-once" aria-describedby="edit-form-submit-once--description" type="checkbox" id="edit-form-submit-once" name="form_submit_once" value class="form-checkbox" />');

    // Check webform no longer has webform.form.submit_once.js.
    $this->drupalGet('webform/test_form_submit_once');
    $this->assertNoRaw('webform.form.submit_once.js');

    // Enable default (global) submit_once on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_submit_once', TRUE)
      ->save();

    // Check submit_once checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_submit_once/settings');
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
    $this->drupalGet('admin/structure/webform/manage/test_form_disable_back/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-disable-back" aria-describedby="edit-form-disable-back--description" type="checkbox" id="edit-form-disable-back" name="form_disable_back" value class="form-checkbox" />');

    // Check webform no longer has webform.form.disable_back.js.
    $this->drupalGet('webform/test_form_disable_back');
    $this->assertNoRaw('webform.form.disable_back.js');

    // Enable default (global) disable_back on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_disable_back', TRUE)
      ->save();

    // Check disable_back checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_disable_back/settings');
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
    $this->drupalGet('admin/structure/webform/manage/test_form_unsaved/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-unsaved" aria-describedby="edit-form-unsaved--description" type="checkbox" id="edit-form-unsaved" name="form_unsaved" value class="form-checkbox" />');

    // Check webform no longer has .js-webform-unsaved class.
    $this->drupalGet('webform/test_form_novalidate');
    $this->assertNoCssSelect('webform.js-webform-unsaved', t('Webform does not have .js-webform-unsaved class.'));

    // Enable default (global) unsaved on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_unsaved', TRUE)
      ->save();

    // Check unsaved checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_unsaved/settings');
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
    $this->drupalGet('admin/structure/webform/manage/test_form_novalidate/settings');
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
    $this->drupalGet('admin/structure/webform/manage/test_form_novalidate/settings');
    $this->assertNoRaw('If checked, the <a href="http://www.w3schools.com/tags/att_form_novalidate.asp">novalidate</a> attribute, which disables client-side validation, will be added to this form.');
    $this->assertRaw('<input data-drupal-selector="edit-form-novalidate-disabled" aria-describedby="edit-form-novalidate-disabled--description" disabled="disabled" type="checkbox" id="edit-form-novalidate-disabled" name="form_novalidate_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Client-side validation is disabled for all forms.');

    // Check novalidate attribute added to webform.
    $this->drupalGet('webform/test_form_novalidate');
    $this->assertCssSelect('form[novalidate="novalidate"]', t('Form has the proper novalidate attribute.'));

    /**************************************************************************/
    /* Test webform details toggle (form_details_toggle) */
    /**************************************************************************/

    $webform_form_details_toggle = Webform::load('test_form_details_toggle');

    // Check webform has .webform-details-toggle class.
    $this->drupalGet('webform/test_form_details_toggle');
    $this->assertCssSelect('form.webform-details-toggle', t('Form has the .webform-details-toggle class.'));

    // Check details toggle checkbox is disabled.
    $this->drupalGet('admin/structure/webform/manage/test_form_details_toggle/settings');
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
    $this->drupalGet('admin/structure/webform/manage/test_form_details_toggle/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-details-toggle" aria-describedby="edit-form-details-toggle--description" type="checkbox" id="edit-form-details-toggle" name="form_details_toggle" value checked="checked" class="form-checkbox" />');
    $this->assertRaw('If checked, an expand/collapse all (details) link will be added to this webform when there are two or more details elements available on the webform.');

    // Disable YAML specific webform details toggle setting.
    $webform_form_details_toggle->setSetting('form_details_toggle', FALSE);
    $webform_form_details_toggle->save();

    // Check webform does not hav .webform-details-toggle class.
    $this->drupalGet('webform/test_form_details_toggle');
    $this->assertNoCssSelect('webform.webform-details-toggle', t('Webform does not have the .webform-details-toggle class.'));

    /**************************************************************************/
    /* Test autofocus (form_autofocus) */
    /**************************************************************************/

    // Check webform has autofocus class.
    $this->drupalGet('webform/test_form_autofocus');
    $this->assertCssSelect('.js-webform-autofocus');

    /**************************************************************************/
    /* Test webform preview (form_preview) */
    /**************************************************************************/

    $this->drupalLogin($this->adminWebformUser);

    $webform_preview = Webform::load('test_form_preview');

    // Check webform with optional preview.
    $this->drupalGet('webform/test_form_preview');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', 'Preview');

    // Check default preview.
    $this->drupalPostForm('webform/test_form_preview', ['name' => 'test'], t('Preview'));

    $this->assertRaw('Please review your submission. Your submission is not complete until you press the "Submit" button!');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '< Previous');
    $this->assertRaw('<b>Name</b><br/>test');

    // Check required preview with custom settings.
    $webform_preview->setSettings([
      'preview' => DRUPAL_REQUIRED,
      'preview_next_button_label' => '{Preview}',
      'preview_prev_button_label' => '{Back}',
      'preview_message' => '{Message}',
    ]);
    $webform_preview->save();

    // Check custom preview.
    $this->drupalPostForm('webform/test_form_preview', ['name' => 'test'], t('{Preview}'));
    $this->assertRaw('{Message}');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Back}');
    $this->assertRaw('<b>Name</b><br/>test');

    $this->drupalGet('webform/test_form_preview');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Preview}');

    /**************************************************************************/
    /* Test results disabled (results_disabled=true) */
    /**************************************************************************/

    // Check results disabled.
    $webform_results_disabled = Webform::load('test_form_results_disabled');
    $webform_submission = $this->postSubmission($webform_results_disabled);
    $this->assertFalse($webform_submission, 'Submission not saved to the database.');

    // Check that error message is displayed and form is available for admins.
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertFieldByName('op', 'Submit');
    $this->assertNoRaw(t('Unable to display this webform. Please contact the site administrator.'));

    // Check that error message not displayed and form is disabled for everyone.
    $this->drupalLogout();
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertNoRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw(t('Unable to display this webform. Please contact the site administrator.'));

    // Enabled ignore disabled results.
    $webform_results_disabled->setSetting('results_disabled_ignore', TRUE);
    $webform_results_disabled->save();
    $this->drupalLogin($this->adminWebformUser);

    // Check that no error message is displayed and form is available for admins.
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertNoRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertNoRaw(t('Unable to display this webform. Please contact the site administrator.'));
    $this->assertFieldByName('op', 'Submit');

    // Check that results tab is not accessible.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertResponse(403);

    // Check that error message not displayed and form is enabled for everyone.
    $this->drupalLogout();
    $this->drupalGet('webform/test_form_results_disabled');
    $this->assertNoRaw(t('This webform is currently not saving any submitted data.'));
    $this->assertNoRaw(t('Unable to display this webform. Please contact the site administrator.'));
    $this->assertFieldByName('op', 'Submit');

    // Unset disabled results.
    $webform_results_disabled->setSetting('results_disabled', FALSE);
    $webform_results_disabled->save();

    // Login admin.
    $this->drupalLogin($this->adminWebformUser);

    // Check that results tab is accessible.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertResponse(200);

    // Post a submission.
    $sid = $this->postSubmissionTest($webform_results_disabled);
    $webform_submission = WebformSubmission::load($sid);

    // Check that submission is available.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertNoRaw('This webform is currently not saving any submitted data');
    $this->assertRaw('>' . $webform_submission->serial() . '<');

    // Set disabled results.
    $webform_results_disabled->setSetting('results_disabled', TRUE);
    $webform_results_disabled->save();

    // Check that submission is still available with warning.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertRaw('This webform is currently not saving any submitted data');
    $this->assertRaw('>' . $webform_submission->serial() . '<');

    // Delete the submission.
    $webform_submission->delete();

    // Check that results tab is not accessible.
    $this->drupalGet('admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $this->assertResponse(403);

    /**************************************************************************/
    /* Test token update (form_token_update) */
    /**************************************************************************/

    // Post test submission.
    $this->drupalLogin($this->adminWebformUser);
    $webform_token_update = Webform::load('test_token_update');
    $sid = $this->postSubmissionTest($webform_token_update);
    $webform_submission = WebformSubmission::load($sid);

    // Check token update access allowed.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertRaw('Submission information');
    $this->assertFieldByName('textfield', $webform_submission->getData('textfield'));

    // Check token update access denied.
    $webform_token_update->setSetting('token_update', FALSE)->save();
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertNoRaw('Submission information');
    $this->assertNoFieldByName('textfield', $webform_submission->getData('textfield'));

    /**************************************************************************/
    /* Test limits (test_form_limit) */
    /**************************************************************************/

    $webform_limit = Webform::load('test_form_limit');

    // Check webform available.
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');

    $this->drupalLogin($this->normalUser);

    // Check that draft does not count toward limit.
    $this->postSubmission($webform_limit, [], t('Save Draft'));
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('A partially-completed form was found. Please complete the remaining portions.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Check limit reached and webform not available for authenticated user.
    $this->postSubmission($webform_limit);
    $this->drupalGet('webform/test_form_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this webform.');

    $this->drupalLogout();

    // Check admin can still edit their submission.
    $this->drupalLogin($this->adminWebformUser);
    $sid = $this->postSubmission($webform_limit);
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $this->assertFieldByName('op', 'Save');
    $this->assertNoRaw('No more submissions are permitted.');
    $this->drupalLogout();

    // Check webform is still available for anonymous users.
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Add 1 more submissions making the total number of submissions equal to 3.
    $this->postSubmission($webform_limit);

    // Check total limit.
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('Only 3 submissions are allowed.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Check admin can still post submissions.
    $this->drupalLogin($this->adminWebformUser);
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('Only 3 submissions are allowed.');
    $this->assertRaw('Only submission administrators are allowed to access this webform and create new submissions.');
  }

}
