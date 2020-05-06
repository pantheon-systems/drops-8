<?php

namespace Drupal\Tests\webform\Functional;

/**
 * Tests for webform libraries.
 *
 * @group Webform
 */
class WebformLibrariesTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_libraries_optional'];

  /**
   * Tests webform libraries.
   */
  public function testLibraries() {
    $optional_properties = [
      'input_mask' => 'properties[input_mask][select]',
      'international_telephone' => 'properties[international]',
      'international_telephone_composite' => 'properties[phone__international]',
      'word_counter' => 'properties[counter_type]',
      'select2' => 'properties[select2]',
    ];

    $this->drupalLogin($this->rootUser);

    // Enable choices and jquery.chosen
    $edit = [
      'excluded_libraries[choices]' => TRUE,
      'excluded_libraries[jquery.chosen]' => TRUE,
    ];
    $this->drupalPostForm('/admin/structure/webform/config/libraries', $edit, t('Save configuration'));

    // Check optional libraries are included.
    $this->drupalGet('/webform/test_libraries_optional');
    $this->assertRaw('/select2.min.js');
    $this->assertRaw('/choices.min.js');
    $this->assertRaw('/chosen.jquery.min.js');
    $this->assertRaw('/textcounter.min.js');
    $this->assertRaw('/intlTelInput.min.js');
    $this->assertRaw('/jquery.inputmask.min.js');
    $this->assertRaw('/codemirror.js');
    $this->assertRaw('/jquery.timepicker.min.js');

    // Check optional libraries are properties accessible (#access = TRUE).
    foreach ($optional_properties as $element_name => $input_name) {
      $this->drupalGet("/admin/structure/webform/manage/test_libraries_optional/element/$element_name/edit");
      $this->assertFieldByName($input_name);
    }

    // Exclude optional libraries.
    $edit = [
      'excluded_libraries[ckeditor.fakeobjects]' => FALSE,
      'excluded_libraries[ckeditor.image]' => FALSE,
      'excluded_libraries[ckeditor.link]' => FALSE,
      'excluded_libraries[codemirror]' => FALSE,
      'excluded_libraries[choices]' => FALSE,
      'excluded_libraries[jquery.inputmask]' => FALSE,
      'excluded_libraries[jquery.intl-tel-input]' => FALSE,
      'excluded_libraries[jquery.select2]' => FALSE,
      'excluded_libraries[jquery.chosen]' => FALSE,
      'excluded_libraries[jquery.timepicker]' => FALSE,
      'excluded_libraries[jquery.textcounter]' => FALSE,
    ];
    $this->drupalPostForm('/admin/structure/webform/config/libraries', $edit, t('Save configuration'));

    // Check optional libraries are excluded.
    $this->drupalGet('/webform/test_libraries_optional');
    $this->assertNoRaw('/select2.min.js');
    $this->assertNoRaw('/choices.min.js');
    $this->assertNoRaw('/chosen.jquery.min.js');
    $this->assertNoRaw('/textcounter.min.js');
    $this->assertNoRaw('/intlTelInput.min.js');
    $this->assertNoRaw('/jquery.inputmask.min.js');
    $this->assertNoRaw('/codemirror.js');
    $this->assertNoRaw('/jquery.timepicker.min.js');

    // Check optional libraries are properties hidden (#access = FALSE).
    foreach ($optional_properties as $element_name => $input_name) {
      $this->drupalGet("admin/structure/webform/manage/test_libraries_optional/element/$element_name/edit");
      $this->assertNoFieldByName($input_name);
    }

    // Check that status report excludes optional libraries.
    $this->drupalGet('/admin/reports/status');
    $this->assertNoText('CKEditor: Fakeobjects library ');
    $this->assertNoText('CKEditor: Image library ');
    $this->assertNoText('CKEditor: Link library ');
    $this->assertNoText('Code Mirror library ');
    $this->assertNoText('jQuery: iCheck library ');
    $this->assertNoText('jQuery: Input Mask library ');
    $this->assertNoText('jQuery: Select2 library ');
    $this->assertNoText('jQuery: Choices library ');
    $this->assertNoText('jQuery: Chosen library ');
    $this->assertNoText('jQuery: Timepicker library ');
    $this->assertNoText('jQuery: Text Counter library ');

    // Issue #2934542: Fix broken Webform.Drupal\webform\Tests\WebformLibrariesTest
    // @see https://www.drupal.org/project/webform/issues/2934542
    /*
    // Exclude element types that require libraries.
    $edit = [
      'excluded_elements[webform_rating]' => FALSE,
      'excluded_elements[webform_signature]' => FALSE,
    ];
    $this->drupalPostForm('/admin/structure/webform/config/elements', $edit, t('Save configuration'));

    // Check that status report excludes libraries required by element types.
    $this->drupalGet('/admin/reports/status');
    $this->assertNoText('jQuery: Image Picker library');
    $this->assertNoText('jQuery: RateIt library');
    $this->assertNoText('Signature Pad library');
    */

    // Check that choices, chosen, and select2 using webform's CDN URLs.
    $edit = [
      'excluded_libraries[jquery.select2]' => TRUE,
      'excluded_libraries[jquery.chosen]' => TRUE,
    ];
    $this->drupalPostForm('/admin/structure/webform/config/libraries', $edit, t('Save configuration'));
    $this->drupalGet('/webform/test_libraries_optional');
    $this->assertRaw('https://cdnjs.cloudflare.com/ajax/libs/chosen');
    $this->assertRaw('https://cdnjs.cloudflare.com/ajax/libs/select2');

    // Install chosen and select2 modules.
    \Drupal::service('module_installer')->install(['chosen', 'chosen_lib', 'select2']);
    drupal_flush_all_caches();

    // Check that chosen and select2 using module's path and not CDN.
    $this->drupalGet('/webform/test_libraries_optional');
    $this->assertNoRaw('https://cdnjs.cloudflare.com/ajax/libs/chosen');
    $this->assertNoRaw('https://cdnjs.cloudflare.com/ajax/libs/select2');
    $this->assertRaw('/modules/contrib/chosen/css/chosen-drupal.css');
    // @todo Fix once Drupal 8.9.x is only supported.
    if (floatval(\Drupal::VERSION) <= 8.8) {
      $this->assertRaw('/libraries/select2/dist/css/select2.min.css');
    }
  }

}
