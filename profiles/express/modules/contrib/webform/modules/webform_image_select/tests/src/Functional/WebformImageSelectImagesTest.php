<?php

namespace Drupal\Tests\webform_image_select\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\WebformInterface;
use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;
use Drupal\webform_image_select\Entity\WebformImageSelectImages;

/**
 * Tests for webform image select image entity.
 *
 * @group Webform
 */
class WebformImageSelectImagesTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_image_select', 'webform_image_select_test'];

  /**
   * Tests webform image select images entity.
   */
  public function testWebformImageSelectImages() {
    $normal_user = $this->drupalCreateUser();

    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);

    /**************************************************************************/

    $this->drupalLogin($normal_user);

    // Check get element images.
    $kittens = Yaml::decode("kitten_1:
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
    $element = ['#images' => $kittens];
    $this->assertEqual(WebformImageSelectImages::getElementImages($element), $kittens);
    $element = ['#images' => 'kittens'];
    $this->assertEqual(WebformImageSelectImages::getElementImages($element), $kittens);
    $element = ['#images' => 'not-found'];
    $this->assertEqual(WebformImageSelectImages::getElementImages($element), []);

    $dogs = Yaml::decode("dog_1:
  text: 'Cute Dog 1'
  src: 'http://placedog.com/220/200'
dog_2:
  text: 'Cute Dog 2'
  src: 'http://placedog.com/180/200'
dog_3:
  text: 'Cute Dog 3'
  src: 'http://placedog.com/130/200'
dog_4:
  text: 'Cute Dog 4'
  src: 'http://placedog.com/270/200'");

    // Check get element images for manually defined images.
    $element = ['#images' => $dogs];
    $this->assertEqual(WebformImageSelectImages::getElementImages($element), $dogs);

    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images */
    $webform_images = WebformImageSelectImages::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'dogs',
      'title' => 'Dogs',
      'images' => Yaml::encode($dogs),
    ]);
    $webform_images->save();

    // Check get images.
    $this->assertEqual($webform_images->getImages(), $dogs);

    // Set invalid images.
    $webform_images->set('images', "not\nvalid\nyaml")->save();

    // Check invalid images.
    $this->assertFalse($webform_images->getImages());

    // Check admin user access denied.
    $this->drupalGet('/admin/structure/webform/config/images/manage');
    $this->assertResponse(403);
    $this->drupalGet('/admin/structure/webform/config/images/manage/add');
    $this->assertResponse(403);
    $this->drupalGet('/admin/structure/webform/config/images/manage/animals/edit');
    $this->assertResponse(403);

    // Check admin user access.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/config/images/manage');
    $this->assertResponse(200);
    $this->drupalGet('/admin/structure/webform/config/images/manage/add');
    $this->assertResponse(200);

    // Check image altered message.
    $this->drupalGet('/admin/structure/webform/config/images/manage/animals/edit');
    $this->assertRaw('The <em class="placeholder">Cute Animals</em> images are being altered by the <em class="placeholder">Webform Image Select Test</em> module.');

    // Check hook_webform_image_select_images_alter().
    // Check hook_webform_image_select_images_WEBFORM_IMAGE_SELECT_IMAGES_ID_alter().
    $element = ['#images' => 'animals'];
    $images = WebformImageSelectImages::getElementImages($element);
    $this->debug($images);
    $this->assertEqual(array_keys($images), ['kitten_1', 'kitten_2', 'kitten_3', 'kitten_4', 'bear_1', 'bear_2', 'bear_3', 'bear_4']);
  }

}
