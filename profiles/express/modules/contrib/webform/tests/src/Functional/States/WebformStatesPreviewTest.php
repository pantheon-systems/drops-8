<?php

namespace Drupal\Tests\webform\Functional\States;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform states preview.
 *
 * @group Webform
 */
class WebformStatesPreviewTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_states_server_preview',
    'test_states_server_save',
    'test_states_server_clear',
  ];

  /**
   * Tests visible conditions (#states) validator for elements .
   */
  public function testStatesValidatorElementVisible() {
    $webform_preview = Webform::load('test_states_server_preview');

    // Check trigger unchecked and elements are conditionally hidden.
    $this->postSubmission($webform_preview, [], t('Preview'));
    $this->assertRaw('trigger_checkbox');
    $this->assertNoRaw('dependent_checkbox');
    $this->assertNoRaw('dependent_markup');
    $this->assertNoRaw('dependent_message');
    $this->assertNoRaw('dependent_fieldset');
    $this->assertNoRaw('nested_textfield');

    // Check trigger checked and elements are conditionally visible.
    $this->postSubmission($webform_preview, ['trigger_checkbox' => TRUE], t('Preview'));
    $this->assertRaw('trigger_checkbox');
    $this->assertRaw('dependent_checkbox');
    $this->assertRaw('dependent_markup');
    $this->assertRaw('dependent_message');
    $this->assertRaw('dependent_fieldset');
    $this->assertRaw('nested_textfield');

    $webform_save = Webform::load('test_states_server_save');

    // Check trigger unchecked and saved.
    $this->postSubmission($webform_save, ['trigger_checkbox' => FALSE], t('Submit'));
    $this->assertRaw("trigger_checkbox: 0
dependent_hidden: ''
dependent_checkbox: ''
dependent_value: ''
dependent_textfield: ''
dependent_textfield_multiple: {  }
dependent_details_textfield: ''");

    // Check trigger checked and saved.
    $this->postSubmission($webform_save, ['trigger_checkbox' => TRUE], t('Submit'));
    $this->assertRaw("trigger_checkbox: 1
dependent_hidden: '{dependent_hidden}'
dependent_checkbox: 0
dependent_value: '{value}'
dependent_textfield: '{dependent_textfield}'
dependent_textfield_multiple:
  - '{dependent_textfield}'
dependent_details_textfield: '{dependent_details_textfield}'");

    $webform_clear = Webform::load('test_states_server_clear');

    // Check trigger unchecked and not cleared.
    $this->postSubmission($webform_clear, ['trigger_checkbox' => FALSE], t('Submit'));
    $this->assertRaw("trigger_checkbox: 0
dependent_hidden: '{dependent_hidden}'
dependent_checkbox: 1
dependent_radios: One
dependent_value: '{value}'
dependent_textfield: '{dependent_textfield}'
dependent_textfield_multiple:
  - '{dependent_textfield}'
dependent_webform_name:
  - title: ''
    first: John
    middle: ''
    last: Smith
    suffix: ''
    degree: ''
dependent_details_textfield: '{dependent_details_textfield}'");
  }

}
