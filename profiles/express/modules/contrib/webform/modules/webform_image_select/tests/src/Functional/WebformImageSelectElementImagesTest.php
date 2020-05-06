<?php

namespace Drupal\Tests\webform_image_select\Functional;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;

/**
 * Tests for webform image select images element.
 *
 * @group Webform
 */
class WebformImageSelectElementImagesTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_image_select', 'webform_image_select_test'];

  /**
   * Tests webform images select images element.
   */
  public function testElementOptions() {
    // Check default value handling.
    $this->drupalPostForm('/webform/test_element_images', [], t('Submit'));
    $this->assertRaw("webform_image_select_images: {  }
webform_image_select_images_default_value:
  kitten_1:
    text: 'Cute Kitten 1'
    src: 'http://placekitten.com/220/200'
  kitten_2:
    text: 'Cute Kitten 2'
    src: 'http://placekitten.com/180/200'
  kitten_3:
    text: 'Cute Kitten 3'
    src: 'http://placekitten.com/130/200'
  kitten_4:
    text: 'Cute Kitten 4'
    src: 'http://placekitten.com/270/200'
webform_image_select_element_images_entity: kittens
webform_image_select_element_images_custom:
  kitten_1:
    text: 'Cute Kitten 1'
    src: 'http://placekitten.com/220/200'
  kitten_2:
    text: 'Cute Kitten 2'
    src: 'http://placekitten.com/180/200'
  kitten_3:
    text: 'Cute Kitten 3'
    src: 'http://placekitten.com/130/200'
  kitten_4:
    text: 'Cute Kitten 4'
    src: 'http://placekitten.com/270/200'");

    // Check unique key validation with image src.
    $edit = [
      'webform_image_select_images[images][items][0][src]' => 'src01',
      'webform_image_select_images[images][items][1][src]' => 'src02',
    ];
    $this->drupalPostForm('/webform/test_element_images', $edit, t('Submit'));
    $this->assertRaw("The <em class=\"placeholder\">Image value</em> '' is already in use. It must be unique.");
  }

}
