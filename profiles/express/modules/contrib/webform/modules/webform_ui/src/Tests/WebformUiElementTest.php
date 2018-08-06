<?php

namespace Drupal\webform_ui\Tests;

use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform UI element.
 *
 * @group WebformUi
 */
class WebformUiElementTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform', 'webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_date'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Disable description help icon.
    $this->config('webform.settings')->set('ui.description_help', FALSE)->save();
  }

  /**
   * Tests element.
   */
  public function testElements() {
    global $base_path;

    $this->drupalLogin($this->rootUser);

    $webform_contact = Webform::load('contact');

    /**************************************************************************/
    // Multiple
    /**************************************************************************/

    // Check multiple enabled before submission.
    $this->drupalGet('admin/structure/webform/manage/contact/element/name/edit');
    $this->assertRaw('<select data-drupal-selector="edit-properties-multiple-container-cardinality" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $this->assertNoRaw('<em>There is data for this element in the database. This setting can no longer be changed.</em>');

    // Check multiple disabled after submission.
    $this->postSubmissionTest($webform_contact);
    $this->drupalGet('admin/structure/webform/manage/contact/element/name/edit');
    $this->assertNoRaw('<select data-drupal-selector="edit-properties-multiple-container-cardinality" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $this->assertRaw('<select data-drupal-selector="edit-properties-multiple-container-cardinality" disabled="disabled" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $this->assertRaw('<em>There is data for this element in the database. This setting can no longer be changed.</em>');

    /**************************************************************************/
    // Reordering
    /**************************************************************************/

    // Check original contact element order.
    $this->assertEqual(['name', 'email', 'subject', 'message', 'actions'], array_keys($webform_contact->getElementsDecodedAndFlattened()));

    // Check updated (reverse) contact element order.
    /** @var \Drupal\webform\WebformInterface $webform_contact */
    $edit = [
      'webform_ui_elements[message][weight]' => 0,
      'webform_ui_elements[subject][weight]' => 1,
      'webform_ui_elements[email][weight]' => 2,
      'webform_ui_elements[name][weight]' => 3,
    ];
    $this->drupalPostForm('admin/structure/webform/manage/contact', $edit, t('Save elements'));

    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    \Drupal::entityTypeManager()->getStorage('webform')->resetCache();
    $webform_contact = Webform::load('contact');
    $this->assertEqual(['message', 'subject', 'email', 'name', 'actions'], array_keys($webform_contact->getElementsDecodedAndFlattened()));

    /**************************************************************************/
    // Required.
    /**************************************************************************/

    // Check name is required.
    $this->drupalGet('admin/structure/webform/manage/contact');
    $this->assertFieldChecked('edit-webform-ui-elements-name-required');

    // Check name is not required.
    $edit = [
      'webform_ui_elements[name][required]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/webform/manage/contact', $edit, t('Save elements'));
    $this->assertNoFieldChecked('edit-webform-ui-elements-name-required');

    /**************************************************************************/
    // CRUD
    /**************************************************************************/

    // Create element.
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/add/textfield', ['key' => 'test', 'properties[title]' => 'Test'], t('Save'));

    // Check elements URL contains ?update query string parameter.
    $this->assertUrl('admin/structure/webform/manage/contact', ['query' => ['update' => 'test']]);

    // Check that save elements removes ?update query string parameter.
    $this->drupalPostForm(NULL, [], t('Save elements'));

    // Check that save elements removes ?update query string parameter.
    $this->assertUrl('admin/structure/webform/manage/contact', ['query' => ['update' => 'test']]);

    // Create validate unique element.
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/add/textfield', ['key' => 'test', 'properties[title]' => 'Test'], t('Save'));
    $this->assertRaw('The machine-readable name is already in use. It must be unique.');

    // Check read element.
    $this->drupalGet('webform/contact');
    $this->assertRaw('<label for="edit-test">Test</label>');
    $this->assertRaw('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="" size="60" maxlength="255" class="form-text" />');

    // Update element.
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/test/edit', ['properties[title]' => 'Test 123', 'properties[default_value]' => 'This is a default value'], t('Save'));

    // Check elements URL contains ?update query string parameter.
    $this->assertUrl('admin/structure/webform/manage/contact', ['query' => ['update' => 'test']]);

    // Check element updated.
    $this->drupalGet('webform/contact');
    $this->assertRaw('<label for="edit-test">Test 123</label>');
    $this->assertRaw('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="This is a default value" size="60" maxlength="255" class="form-text" />');

    // Check that 'test' element is being added to the webform_submission_data table.
    $this->drupalPostForm('webform/contact/test', [], t('Send message'));
    $this->assertEqual(1, \Drupal::database()->query("SELECT COUNT(sid) FROM {webform_submission_data} WHERE webform_id='contact' AND name='test'")->fetchField());

    // Check delete element.
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/test/delete', [], t('Delete'));
    $this->drupalGet('webform/contact');
    $this->assertNoRaw('<label for="edit-test">Test 123</label>');
    $this->assertNoRaw('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="This is a default value" size="60" maxlength="255" class="form-text" />');

    // Check that 'test' element values were deleted from the webform_submission_data table.
    $this->assertEqual(0, \Drupal::database()->query("SELECT COUNT(sid) FROM {webform_submission_data} WHERE webform_id='contact' AND name='test'")->fetchField());

    /**************************************************************************/
    // Change type
    /**************************************************************************/

    // Check create element.
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/add/textfield', ['key' => 'test', 'properties[title]' => 'Test'], t('Save'));

    // Check element type.
    $this->drupalGet('admin/structure/webform/manage/contact/element/test/edit');
    // Check change element type link.
    $this->assertRaw('Text field<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/change" class="button button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-change-type" id="edit-change-type">Change</a>');
    // Check text field has description.
    $this->assertRaw(t('A short description of the element used as help for the user when he/she uses the webform.'));

    // Check change element types.
    $this->drupalGet('admin/structure/webform/manage/contact/element/test/change');
    $this->assertRaw(t('Hidden'));
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit?type=hidden" class="button button--primary button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-elements-hidden-operation" id="edit-elements-hidden-operation">Change</a>');
    $this->assertRaw(t('value'));
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit?type=value" class="button button--primary button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-elements-value-operation" id="edit-elements-value-operation">Change</a>');
    $this->assertRaw(t('Search'));
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit?type=search" class="button button--primary button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-elements-search-operation" id="edit-elements-search-operation">Change</a>');
    $this->assertRaw(t('Telephone'));
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit?type=tel" class="button button--primary button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-elements-tel-operation" id="edit-elements-tel-operation">Change</a>');
    $this->assertRaw(t('URL'));
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit?type=url" class="button button--primary button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-elements-url-operation" id="edit-elements-url-operation">Change</a>');

    // Check change element type.
    $this->drupalGet('admin/structure/webform/manage/contact/element/test/edit', ['query' => ['type' => 'value']]);
    // Check value has no description.
    $this->assertNoRaw(t('A short description of the element used as help for the user when he/she uses the webform.'));
    $this->assertRaw('Value<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit" class="button button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-cancel" id="edit-cancel">Cancel</a>');
    $this->assertRaw('(Changing from <em class="placeholder">Text field</em>)');

    // Change the element type.
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/test/edit', [], t('Save'), ['query' => ['type' => 'value']]);

    // Change the element type from 'textfield' to 'value'.
    $this->drupalGet('admin/structure/webform/manage/contact/element/test/edit');

    // Check change element type link.
    $this->assertRaw('Value<a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/change" class="button button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-modal&quot;}" data-drupal-selector="edit-change-type" id="edit-change-type">Change</a>');

    // Check color element that does not have related type and return 404.
    $this->drupalPostForm('admin/structure/webform/manage/contact/element/add/color', ['key' => 'test_color', 'properties[title]' => 'Test color'], t('Save'));
    $this->drupalGet('admin/structure/webform/manage/contact/element/test_color/change');
    $this->assertResponse(404);

    /**************************************************************************/
    // Date
    /**************************************************************************/

    // Check GNU Date Input Format validation.
    $edit = [
      'properties[default_value]' => 'not a valid date',
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_element_date/element/date_min_max_dynamic/edit', $edit, t('Save'));
    $this->assertRaw('The Default value could not be interpreted in <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>.');
  }

  /**
   * Tests permissions.
   */
  public function testPermissions() {
    $webform = Webform::load('contact');

    // Check source page access not visible to user with 'administer webform'
    // permission.
    $account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/source');
    $this->assertResponse(403);
    $this->drupalLogout();

    // Check source page access not visible to user with 'edit webform source'
    // without 'administer webform' permission.
    $account = $this->drupalCreateUser(['edit webform source']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/source');
    $this->assertResponse(403);
    $this->drupalLogout();

    // Check source page access visible to user with 'edit webform source'
    // and 'administer webform' permission.
    $account = $this->drupalCreateUser(['administer webform', 'edit webform source']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/source');
    $this->assertResponse(200);
    $this->drupalLogout();
  }

}
