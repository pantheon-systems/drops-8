<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform image select element.
 *
 * @group Webform
 */
class WebformElementImageSelectTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_image_select'];

  /**
   * Test webform image select element.
   */
  public function testImageSelect() {
    $this->drupalGet('webform/test_element_image_select');

    // Check rendering of image select with required.
    $this->assertRaw('<select data-drupal-selector="edit-image-select-default" data-images="{&quot;kitten_1&quot;:{&quot;text&quot;:&quot;Cute Kitten 1&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/220\/200&quot;},&quot;kitten_2&quot;:{&quot;text&quot;:&quot;Cute Kitten 2&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/180\/200&quot;},&quot;kitten_3&quot;:{&quot;text&quot;:&quot;Cute Kitten 3&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/130\/200&quot;},&quot;kitten_4&quot;:{&quot;text&quot;:&quot;Cute Kitten 4&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/270\/200&quot;}}" class="webform-image-select js-webform-image-select form-select required" id="edit-image-select-default" name="image_select_default" required="required" aria-required="true">');

    // Check rendering of image select with limit.
    $this->assertRaW('<select data-drupal-selector="edit-image-select-limit" data-limit="2" data-images="{&quot;kitten_1&quot;:{&quot;text&quot;:&quot;Cute Kitten 1&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/220\/200&quot;},&quot;kitten_2&quot;:{&quot;text&quot;:&quot;Cute Kitten 2&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/180\/200&quot;},&quot;kitten_3&quot;:{&quot;text&quot;:&quot;Cute Kitten 3&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/130\/200&quot;},&quot;kitten_4&quot;:{&quot;text&quot;:&quot;Cute Kitten 4&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/270\/200&quot;}}" class="webform-image-select js-webform-image-select form-select" multiple="multiple" name="image_select_limit[]" id="edit-image-select-limit">');

    // Check rendering of image select with HTML markup and XSS test.
    $this->assertRaW('<select data-drupal-selector="edit-image-select-html" data-show-label="data-show-label" data-images="{&quot;\u003C1\u003E&quot;:{&quot;text&quot;:&quot;Cute Kitten 1&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/220\/200&quot;},&quot;\u00222\u0022&quot;:{&quot;text&quot;:&quot;Cute \u003Cem\u003EKitten\u003C\/em\u003E 2&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/180\/200&quot;},&quot;\u00263&quot;:{&quot;text&quot;:&quot;Cute Kitten 3&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/130\/200&quot;},&quot;4&quot;:{&quot;text&quot;:&quot;Cute Kitten 4 alert(\u0022XSS\u0022);&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/270\/200&quot;}}" class="webform-image-select js-webform-image-select form-select" id="edit-image-select-html" name="image_select_html"><option value="" selected="selected">- None -</option><option value="&lt;1&gt;">');
  }

}
