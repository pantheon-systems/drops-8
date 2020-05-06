<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform image resolution element.
 *
 * @group Webform
 */
class WebformElementImageResolutionTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_image_resolution'];

  /**
   * Tests image resolution element.
   */
  public function testImageResolution() {

    $this->drupalGet('/webform/test_element_image_resolution');

    // Check rendering.
    $this->assertRaw('<label>webform_image_resolution_advanced</label>');
    $this->assertRaw('<label for="edit-webform-image-resolution-advanced-x" class="visually-hidden">{width_title}</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-image-resolution-advanced-x" type="number" id="edit-webform-image-resolution-advanced-x" name="webform_image_resolution_advanced[x]" value="300" step="1" min="1" class="form-number" />');
    $this->assertRaw('<label for="edit-webform-image-resolution-advanced-y" class="visually-hidden">{height_title}</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-image-resolution-advanced-y" type="number" id="edit-webform-image-resolution-advanced-y" name="webform_image_resolution_advanced[y]" value="400" step="1" min="1" class="form-number" />');
    $this->assertRaw('{description}');

    // Check validation.
    $this->drupalPostForm('/webform/test_element_image_resolution', ['webform_image_resolution[x]' => '100'], t('Submit'));
    $this->assertRaw('Both a height and width value must be specified in the webform_image_resolution field.');

    // Check processing.
    $this->drupalPostForm('/webform/test_element_image_resolution', [], t('Submit'));
    $this->assertRaw("webform_image_resolution: ''
webform_image_resolution_advanced: 300x400");
  }

}
