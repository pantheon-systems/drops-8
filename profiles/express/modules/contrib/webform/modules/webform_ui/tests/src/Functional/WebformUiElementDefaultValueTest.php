<?php

namespace Drupal\Tests\webform_ui\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform UI element.
 *
 * @group WebformUi
 */
class WebformUiElementDefaultValueTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_ui'];

  /**
   * Tests element.
   */
  public function testElementDefaultValue() {

    $this->drupalLogin($this->rootUser);

    /**************************************************************************/
    // Single text field.
    /**************************************************************************/

    // Check validation when trying to set default value.
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/textfield', [], t('Set default value'));
    $this->assertRaw('Key field is required.');
    $this->assertRaw('Title field is required.');

    // Check set default value generates a single textfield element.
    $edit = [
      'key' => 'textfield',
      'properties[title]' => 'textfield',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/textfield', $edit, t('Set default value'));
    $this->assertRaw('<label for="edit-default-value">textfield</label>');
    $this->assertFieldByName('default_value', '');

    // Check setting the text field's default value.
    $this->drupalPostForm(NULL, ['default_value' => '{default value}'], t('Update default value'));
    $this->assertFieldByName('properties[default_value]', '{default value}');

    /**************************************************************************/
    // Multiple text field.
    /**************************************************************************/

    // Check set default value generates a multiple textfield element.
    $edit = [
      'key' => 'textfield',
      'properties[title]' => 'textfield',
      'properties[multiple][container][cardinality]' => '-1',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/textfield', $edit, t('Set default value'));
    $this->assertFieldByName('default_value[items][0][_item_]', '');

    // Check setting the text field's default value.
    $this->drupalPostForm(NULL, ['default_value[items][0][_item_]' => '{default value}'], t('Update default value'));
    $this->assertFieldByName('properties[default_value]', '{default value}');

    /**************************************************************************/
    // Single address (composite) field.
    /**************************************************************************/

    // Check set default value generates a single address element.
    $edit = [
      'key' => 'address',
      'properties[title]' => 'address',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/element/add/webform_address', $edit, t('Set default value'));
    $this->assertFieldByName('default_value[address]', '');
    $this->assertFieldByName('default_value[address_2]', '');

    // Check setting the address' default value.
    $edit = [
      'default_value[address]' => '{address}',
      'default_value[address_2]' => '{address_2}',
    ];
    $this->drupalPostForm(NULL, $edit, t('Update default value'));
    $this->assertRaw('address: &#039;{address}&#039;
address_2: &#039;{address_2}&#039;
city: &#039;&#039;
state_province: &#039;&#039;
postal_code: &#039;&#039;
country: &#039;&#039;');

    // Check default value is passed set default value form.
    $this->drupalPostForm(NULL, [], t('Set default value'));
    $this->assertFieldByName('default_value[address]', '{address}');
    $this->assertFieldByName('default_value[address_2]', '{address_2}');
  }

}
