<?php

namespace Drupal\Tests\webform\Functional\Composite;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for composite plugin.
 *
 * @group Webform
 */
class WebformCompositePluginTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_test_element'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_composite_plugin'];

  /**
   * Test composite plugin.
   */
  public function testPlugin() {

    /* Display */

    $this->drupalGet('/webform/test_element_composite_plugin');

    // Check fieldset with nested elements is rendered.
    $this->assertRaw('<fieldset data-drupal-selector="edit-webform-test-composite-fieldset" id="edit-webform-test-composite-fieldset" class="js-webform-type-fieldset webform-type-fieldset js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="fieldset-legend">fieldset</span>');

    /* Processing */

    // Check processing simple composite.
    $edit = [
      'webform_test_composite[textfield]' => '{textfield}',
      'webform_test_composite[email]' => 'email@email.com',
      'webform_test_composite[webform_email_confirm][mail_1]' => 'email@email.com',
      'webform_test_composite[webform_email_confirm][mail_2]' => 'email@email.com',
      'webform_test_composite[tel]' => '123-456-7890',
      'webform_test_composite[select]' => 'one',
      'webform_test_composite[radios]' => 'one',
      'webform_test_composite[date]' => '2018-01-01',
      'webform_test_composite[webform_entity_select]' => '',
      'webform_test_composite[entity_autocomplete]' => '',
      'webform_test_composite[datelist][year]' => '2018',
      'webform_test_composite[datelist][month]' => '1',
      'webform_test_composite[datelist][day]' => '1',
      'webform_test_composite[datelist][hour]' => '1',
      'webform_test_composite[datelist][minute]' => '1',
      'webform_test_composite[datetime][date]' => '2018-03-21',
      'webform_test_composite[datetime][time]' => '23:19:25',
      'webform_test_composite[nested_tel]' => '123-456-7890',
      'webform_test_composite[nested_select]' => 'Monday',
      'webform_test_composite[nested_radios]' => 'Monday',
    ];
    $this->drupalPostForm('/webform/test_element_composite_plugin', $edit, t('Submit'));
    $this->assertRaw("webform_test_composite:
  textfield: '{textfield}'
  email: email@email.com
  webform_email_confirm: email@email.com
  tel: 123-456-7890
  select: one
  radios: one
  date: '2018-01-01'
  webform_entity_select: ''
  entity_autocomplete: null
  datelist: '2018-01-01T01:01:00+1100'
  datetime: '2018-03-21T23:19:25+1100'
  nested_tel: 123-456-7890
  nested_select: Monday
  nested_radios: Monday");
  }

}
