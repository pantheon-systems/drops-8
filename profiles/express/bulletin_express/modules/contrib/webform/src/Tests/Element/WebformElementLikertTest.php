<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for likert element.
 *
 * @group Webform
 */
class WebformElementLikertTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_likert'];

  /**
   * Test likert element.
   */
  public function testLikertElement() {

    $this->drupalGet('webform/test_element_likert');

    // Check default likert element.
    $this->assertRaw('<table class="webform-likert-table responsive-enabled" data-likert-answers-count="3" data-drupal-selector="edit-likert-default-table" id="edit-likert-default-table" data-striping="1">');
    $this->assertPattern('#<tr>\s+<th></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+</tr>#');
    $this->assertRaw('<label for="edit-likert-default-table-q1-likert-question">Question 1</label>');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-likert-default-q1 form-item-likert-default-q1">');
    $this->assertRaw('<input data-drupal-selector="edit-likert-default-q1" type="radio" id="edit-likert-default-q1" name="likert_default[q1]" value="1" class="form-radio" />');
    $this->assertRaw('<label for="edit-likert-default-q1" class="option">Option 1</label>');

    // Check advanced likert element with N/A.
    $this->assertPattern('#<tr>\s+<th></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+<th>Not applicable</th>\s+</tr>#');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-likert-advanced-q1 form-item-likert-advanced-q1">');
    $this->assertRaw('<input data-drupal-selector="edit-likert-advanced-q1" type="radio" id="edit-likert-advanced-q1--4" name="likert_advanced[q1]" value="N/A" class="form-radio" />');
    $this->assertRaw('<label for="edit-likert-advanced-q1--4" class="option">Not applicable</label>');

    // Check likert required.
    $this->drupalPostForm('webform/test_element_likert', [], t('Submit'));
    $this->assertRaw('Question 1 field is required.');
    $this->assertRaw('Question 2 field is required.');
    $this->assertRaw('Question 3 field is required.');

    // Check likert processing.
    $edit = [
      'likert_advanced[q1]' => '1',
      'likert_advanced[q2]' => '2',
      'likert_advanced[q3]' => 'N/A',
    ];
    $this->drupalPostForm('webform/test_element_likert', $edit, t('Submit'));
    $this->assertRaw("likert_advanced:
  q1: '1'
  q2: '2'
  q3: N/A");
  }

}
