<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for likert element.
 *
 * @group Webform
 */
class WebformElementLikertTest extends WebformElementBrowserTestBase {

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

    $this->drupalGet('/webform/test_element_likert');

    // Check default likert element.
    $this->assertRaw('<table class="webform-likert-table sticky-enabled responsive-enabled" data-likert-answers-count="3" data-drupal-selector="edit-likert-default-table" id="edit-likert-default-table" data-striping="1">');
    $this->assertPattern('#<tr>\s+<th><span class="visually-hidden">Questions</span></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+</tr>#');
    $this->assertRaw('<label>Question 1</label>');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-likert-default-q1 form-item-likert-default-q1">');
    $this->assertRaw('<input aria-labelledby="edit-likert-default-table-q1-likert-question" data-drupal-selector="edit-likert-default-q1" type="radio" id="edit-likert-default-q1" name="likert_default[q1]" value="1" class="form-radio" />');
    $this->assertRaw('<label for="edit-likert-default-q1" class="option"><span class="webform-likert-label visually-hidden">Option 1</span></label>');

    // Check advanced likert element with N/A.
    $this->assertPattern('#<tr>\s+<th><span class="visually-hidden">Questions</span></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+<th>Not applicable</th>\s+</tr>#');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-likert-advanced-q1 form-item-likert-advanced-q1">');
    $this->assertRaw('<input aria-labelledby="edit-likert-advanced-table-q1-likert-question" data-drupal-selector="edit-likert-advanced-q1" type="radio" id="edit-likert-advanced-q1--4" name="likert_advanced[q1]" value="N/A" class="form-radio" />');
    $this->assertRaw('<label for="edit-likert-advanced-q1--4" class="option"><span class="webform-likert-label visually-hidden">Not applicable</span></label>');

    // Check likert with description.
    $this->assertRaw('<th>Option 1<div class="description">This is a description</div>');
    $this->assertRaw('<label>Question 1</label>');
    $this->assertRaw('<div id="edit-likert-description-table-q1-likert-question--description" class="webform-element-description">');
    $this->assertRaw('<label for="edit-likert-description-q1" class="option"><span class="webform-likert-label visually-hidden">Option 1</span></label>');
    $this->assertRaw('<span class="webform-likert-description hidden">This is a description</span>');

    // Check likert with help.
    $this->assertRaw('<th>Option 1<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Option 1&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $this->assertRaw('<label>Question 1<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Question 1&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $this->assertRaw('<label for="edit-likert-help-q1--2" class="option"><span class="webform-likert-label visually-hidden">Option 2<span class="webform-likert-help hidden"><span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Option 2&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check likert required.
    $this->drupalPostForm('/webform/test_element_likert', [], t('Submit'));
    $this->assertRaw('Question 1 field is required.');
    $this->assertRaw('Question 2 field is required.');
    $this->assertRaw('Question 3 field is required.');

    // Check likert processing.
    $edit = [
      'likert_advanced[q1]' => '1',
      'likert_advanced[q2]' => '2',
      'likert_advanced[q3]' => 'N/A',
      'likert_values[0]' => '0',
      'likert_values[1]' => '1',
      'likert_values[2]' => 'N/A',
    ];
    $this->drupalPostForm('/webform/test_element_likert', $edit, t('Submit'));
    $this->assertRaw("likert_default:
  q1: null
  q2: null
  q3: null
likert_advanced:
  q1: '1'
  q2: '2'
  q3: N/A
likert_description:
  q1: null
  q2: null
  q3: null
likert_help:
  q1: null
  q2: null
  q3: null
likert_values:
  - '0'
  - '1'
  - N/A");
  }

}
