<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform options limit test.
 *
 * @group webform_browser
 */
class WebformOptionsLimitTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'webform',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit.
   */
  public function testOptionsLimit() {
    $webform = Webform::load('test_handler_options_limit');

    $this->drupalGet('/webform/test_handler_options_limit');

    // Check that option A is available.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-default-a" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" checked="checked" class="form-checkbox" />');
    $this->assertRaw('A [1 remaining]');

    // Check that option D is available.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" checked="checked" class="form-checkbox" />');
    $this->assertRaw('1 option remaining / 1 limit / 0 total');

    // Check that option H is available.
    $this->assertRaw('<option value="H" selected="selected">H [1 remaining]</option>');

    // Check that option K is available.
    $this->assertRaw('<option value="K" selected="selected">K [1 remaining]</option>');

    // Check that option O is available.
    $this->assertRaw('<option value="O" selected="selected">O [1 remaining]</option>');

    // Check that table select multiple is available.
    $this->assertFieldById('edit-options-limit-tableselect-multiple-u', 'U');
    $this->assertRaw('<input class="tableselect form-checkbox" data-drupal-selector="edit-options-limit-tableselect-multiple-u" type="checkbox" id="edit-options-limit-tableselect-multiple-u" name="options_limit_tableselect_multiple[U]" value="U" checked="checked" />');
    $this->assertRaw('<td>U [1 remaining]</td>');

    // Check that table select single is available.
    $this->assertFieldById('edit-options-limit-tableselect-single-x', 'X');
    $this->assertRaw('<input class="tableselect form-radio" data-drupal-selector="edit-options-limit-tableselect-single-x" type="radio" id="edit-options-limit-tableselect-single-x" name="options_limit_tableselect_single" value="X" checked="checked" />');
    $this->assertPattern('#<th>options_limit_tableselect_single</th>\s+<th>Limits</th>#');
    $this->assertRaw('<td>X</td>');
    $this->assertRaw('<td> [1 remaining]</td>');

    // Post first submission.
    $sid_1 = $this->postSubmission($webform);

    // Check that option A is disabled with 0 remaining.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-default-a" disabled="disabled" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" class="form-checkbox" />');
    $this->assertRaw('A [0 remaining]');

    // Check that option B is disabled with custom remaining message.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" disabled="disabled" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" class="form-checkbox" />');
    $this->assertRaw('No options remaining / 1 limit / 1 total');

    // Check that option H is no longer selected and disabled via JavaScript.
    $this->assertRaw('<option value="H">H [0 remaining]</option>');
    $this->assertRaw('data-webform-select-options-disabled="H"');

    // Check that option K was removed.
    $this->assertNoRaw('<option value="K"');

    // Check that option O was not changed but is not selected.
    $this->assertRaw('<option value="O">O [0 remaining]</option>');

    // Check that table select multiple is NOT available.
    $this->assertNoFieldById('edit-options-limit-tableselect-multiple-u', 'U');
    $this->assertRaw('<td>U [0 remaining]</td>');

    // Check that table select single is available.
    $this->assertNoFieldById('edit-options-limit-tableselect-multiple-x', 'X');
    $this->assertRaw('<td>X</td>');
    $this->assertRaw('<td> [0 remaining]</td>');

    // Check that option O being selected triggers validation error.
    $this->postSubmission($webform, ['options_limit_select_none[]' => 'O']);
    $this->assertRaw('options_limit_select_none: O is unavailable.');

    // Chech that unavailable option can't be prepopulated.
    $this->drupalGet('/webform/test_handler_options_limit', ['query' => ['options_limit_default[]' => 'A']]);
    $this->assertNoFieldChecked('edit-options-limit-default-a');
    $this->drupalGet('/webform/test_handler_options_limit', ['query' => ['options_limit_default[]' => 'B']]);
    $this->assertFieldChecked('edit-options-limit-default-b');

    // Post two more submissions.
    $this->postSubmission($webform);
    $this->postSubmission($webform);

    // Change that 'options_limit_default' is disabled and not available.
    $this->assertRaw('A [0 remaining]');
    $this->assertRaw('B [0 remaining]');
    $this->assertRaw('C [0 remaining]');
    $this->assertRaw('options_limit_default is not available.');

    // Login as an admin.
    $this->drupalLogin($this->rootUser);

    // Check that random test values are the only available options.
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $this->assertRaw('<option value="J" selected="selected">J [Unlimited]</option>');
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $this->assertRaw('<option value="J" selected="selected">J [Unlimited]</option>');
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $this->assertRaw('<option value="J" selected="selected">J [Unlimited]</option>');

    // Check that existing submission values are not disabled.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_options_limit/submission/$sid_1/edit");
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-default-a" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" checked="checked" class="form-checkbox" />');
    $this->assertRaw('A [0 remaining]');
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" checked="checked" class="form-checkbox" />');
    $this->assertRaw('No options remaining / 1 limit / 1 total');
    $this->assertRaw('<option value="H" selected="selected">H [0 remaining]</option>');
    $this->assertRaw('<option value="K" selected="selected">K [0 remaining]</option>');
    $this->assertRaw('<option value="O" selected="selected">O [0 remaining]</option>');

    // Check that Options limit report is available.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit/results/options-limit');
    $this->assertResponse(200);

    // Check handler element error messages.
    $webform->deleteElement('options_limit_default');
    $webform->save();
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit/handlers');
    $this->assertRaw('<b class="color-error">\'options_limit_default\' is missing.</b>');
  }

}
