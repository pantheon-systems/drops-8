<?php

namespace Drupal\Tests\webform_ui\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform UI element.
 *
 * @group WebformUi
 */
class WebformUiElementTest extends WebformBrowserTestBase {

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
    // Multiple.
    /**************************************************************************/

    // Check multiple enabled before submission.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $this->assertRaw('<select data-drupal-selector="edit-properties-multiple-container-cardinality" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $this->assertNoRaw('<em>There is data for this element in the database. This setting can no longer be changed.</em>');

    // Check multiple disabled after submission.
    $this->postSubmissionTest($webform_contact);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $this->assertNoRaw('<select data-drupal-selector="edit-properties-multiple-container-cardinality" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $this->assertRaw('<select data-drupal-selector="edit-properties-multiple-container-cardinality" disabled="disabled" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $this->assertRaw('<em>There is data for this element in the database. This setting can no longer be changed.</em>');

    /**************************************************************************/
    // Reordering.
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
    $this->drupalPostForm('/admin/structure/webform/manage/contact', $edit, t('Save elements'));

    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    \Drupal::entityTypeManager()->getStorage('webform')->resetCache();
    $webform_contact = Webform::load('contact');
    $this->assertEqual(['message', 'subject', 'email', 'name', 'actions'], array_keys($webform_contact->getElementsDecodedAndFlattened()));

    /**************************************************************************/
    // Hierarchy.
    /**************************************************************************/

    // Create a simple test form.
    $values = ['id' => 'test'];
    $elements = [
      'details_01' => [
        '#type' => 'details',
        '#title' => 'details_01',
        'text_field_01' => [
          '#type' => 'textfield',
          '#title' => 'textfield_01',
        ],
      ],
      'details_02' => [
        '#type' => 'details',
        '#title' => 'details_02',
        'text_field_02' => [
          '#type' => 'textfield',
          '#title' => 'textfield_02',
        ],
      ],
    ];
    $this->createWebform($values, $elements);
    $this->drupalGet('/admin/structure/webform/manage/test');

    // Check setting container to itself displays an error.
    $edit = [
      'webform_ui_elements[details_01][parent_key]' => 'details_01',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test', $edit, t('Save elements'));
    $this->assertRaw('Parent <em class="placeholder">details_01</em> key is not valid.');

    // Check setting containers to one another displays an error.
    $edit = [
      'webform_ui_elements[details_01][parent_key]' => 'details_02',
      'webform_ui_elements[details_02][parent_key]' => 'details_01',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test', $edit, t('Save elements'));
    $this->assertRaw('Parent <em class="placeholder">details_01</em> key is not valid.');
    $this->assertRaw('Parent <em class="placeholder">details_02</em> key is not valid.');

    /**************************************************************************/
    // Required.
    /**************************************************************************/

    // Check name is required.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertFieldChecked('edit-webform-ui-elements-name-required');

    // Check name is not required.
    $edit = [
      'webform_ui_elements[name][required]' => FALSE,
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact', $edit, t('Save elements'));
    $this->assertNoFieldChecked('edit-webform-ui-elements-name-required');

    /**************************************************************************/
    // CRUD
    /**************************************************************************/

    // Check that 'Save + Add element' is only visible in dialogs.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $this->assertNoRaw('Save + Add element');
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield', ['query' => ['_wrapper_format' => 'drupal_dialog']]);
    $this->assertRaw('Save + Add element');

    // Create element.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/textfield', ['key' => 'test', 'properties[title]' => 'Test'], t('Save'));

    // Check elements URL contains ?update query string parameter.
    $this->assertUrl('admin/structure/webform/manage/contact', ['query' => ['update' => 'test']]);

    // Check that save elements removes ?update query string parameter.
    $this->drupalPostForm(NULL, [], t('Save elements'));

    // Check that save elements removes ?update query string parameter.
    $this->assertUrl('admin/structure/webform/manage/contact', ['query' => ['update' => 'test']]);

    // Create validate unique element.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/textfield', ['key' => 'test', 'properties[title]' => 'Test'], t('Save'));
    $this->assertRaw('The machine-readable name is already in use. It must be unique.');

    // Check read element.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('<label for="edit-test">Test</label>');
    $this->assertRaw('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="" size="60" maxlength="255" class="form-text" />');

    // Update element.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/test/edit', ['properties[title]' => 'Test 123', 'properties[default_value]' => 'This is a default value'], t('Save'));

    // Check elements URL contains ?update query string parameter.
    $this->assertUrl('admin/structure/webform/manage/contact', ['query' => ['update' => 'test']]);

    // Check element updated.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('<label for="edit-test">Test 123</label>');
    $this->assertRaw('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="This is a default value" size="60" maxlength="255" class="form-text" />');

    // Check that 'test' element is being added to the webform_submission_data table.
    $this->drupalPostForm('/webform/contact/test', [], t('Send message'));
    $this->assertEqual(1, \Drupal::database()->query("SELECT COUNT(sid) FROM {webform_submission_data} WHERE webform_id='contact' AND name='test'")->fetchField());

    // Check delete element.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/test/delete', [], t('Delete'));
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('<label for="edit-test">Test 123</label>');
    $this->assertNoRaw('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="This is a default value" size="60" maxlength="255" class="form-text" />');

    // Check that 'test' element values were deleted from the webform_submission_data table.
    $this->assertEqual(0, \Drupal::database()->query("SELECT COUNT(sid) FROM {webform_submission_data} WHERE webform_id='contact' AND name='test'")->fetchField());

    // Check access allowed to textfield element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $this->assertResponse(200);

    // Check access denied to password element, which is disabled by default.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/password');
    $this->assertResponse(403);

    /**************************************************************************/
    // Change type
    /**************************************************************************/

    // Check create element.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/textfield', ['key' => 'test', 'properties[title]' => 'Test'], t('Save'));

    // Check element type.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit');
    // Check change element type link.
    $this->assertRaw('Text field <a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/change" class="button button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-selector="edit-change-type" id="edit-change-type">Change</a>');
    // Check text field has description.
    $this->assertRaw(t('A short description of the element used as help for the user when he/she uses the webform.'));

    // Check change element types.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/change');
    $this->assertRaw(t('Hidden'));
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=hidden"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-hidden-operation"]');
    $this->assertRaw(t('value'));
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=value"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-value-operation"]');
    $this->assertRaw(t('Search'));
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=search"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-search-operation"]');
    $this->assertRaw(t('Telephone'));
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=tel"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-tel-operation"]');
    $this->assertRaw(t('URL'));
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=url"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-url-operation"]');

    // Check change element type.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit', ['query' => ['type' => 'value']]);
    // Check value has no description.
    $this->assertNoRaw(t('A short description of the element used as help for the user when he/she uses the webform.'));
    $this->assertRaw('Value <a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit" class="button button--small webform-ajax-link" data-dialog-type="dialog" data-dialog-renderer="off_canvas" data-dialog-options="{&quot;width&quot;:600,&quot;dialogClass&quot;:&quot;ui-dialog-off-canvas webform-off-canvas&quot;}" data-drupal-selector="edit-cancel" id="edit-cancel">Cancel</a>');
    $this->assertRaw('(Changing from <em class="placeholder">Text field</em>)');

    // Change the element type.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/test/edit', [], t('Save'), ['query' => ['type' => 'value']]);

    // Change the element type from 'textfield' to 'value'.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit');

    // Check change element type link.
    $this->assertRaw('Value <a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/change" class="button button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-selector="edit-change-type" id="edit-change-type">Change</a>');

    // Check color element that does not have related type and return 404.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/color', ['key' => 'test_color', 'properties[title]' => 'Test color'], t('Save'));
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test_color/change');
    $this->assertResponse(404);

    /**************************************************************************/
    // Date
    /**************************************************************************/

    // Check GNU Date Input Format validation.
    $edit = [
      'properties[default_value]' => 'not a valid date',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_date/element/date_min_max_dynamic/edit', $edit, t('Save'));
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
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/source');
    $this->assertResponse(403);
    $this->drupalLogout();

    // Check source page access not visible to user with 'edit webform source'
    // without 'administer webform' permission.
    $account = $this->drupalCreateUser(['edit webform source']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/source');
    $this->assertResponse(403);
    $this->drupalLogout();

    // Check source page access visible to user with 'edit webform source'
    // and 'administer webform' permission.
    $account = $this->drupalCreateUser(['administer webform', 'edit webform source']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/source');
    $this->assertResponse(200);
    $this->drupalLogout();
  }

}
