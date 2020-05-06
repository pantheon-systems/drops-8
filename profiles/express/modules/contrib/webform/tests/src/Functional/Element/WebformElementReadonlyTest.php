<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element readonly attribute.
 *
 * @group Webform
 */
class WebformElementReadonlyTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_readonly'];

  /**
   * Tests element readonly.
   */
  public function testReadonly() {
    $this->drupalGet('/webform/test_element_readonly');

    $this->assertRaw('<div class="webform-readonly js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-textfield form-item-textfield">');
    $this->assertRaw('<input readonly="readonly" data-drupal-selector="edit-textfield" type="text" id="edit-textfield" name="textfield" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertRaw('<div class="webform-readonly js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-textarea form-item-textarea">');
    $this->assertRaw('<textarea readonly="readonly" data-drupal-selector="edit-textarea" id="edit-textarea" name="textarea" rows="5" cols="60" class="form-textarea resize-vertical"></textarea>');
  }

}
