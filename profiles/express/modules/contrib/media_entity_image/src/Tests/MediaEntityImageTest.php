<?php

namespace Drupal\media_entity_image\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for media entity image.
 *
 * @group media_entity_image
 */
class MediaEntityImageTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'media_entity',
    'entity_browser',
    'media_entity_image_test',
  ];

  /**
   * Tests media entity image.
   */
  public function testMediaEntityImage() {
    $account = $this->drupalCreateUser([
      'access test_entity_browser_for_images entity browser pages',
    ]);
    $this->drupalLogin($account);
    $image = current($this->drupalGetTestFiles('image'));
    // Go to the entity browser iframe link.
    $this->drupalGet('/entity-browser/iframe/test_entity_browser_for_images');
    $edit = [
      'files[upload][]' => $this->container->get('file_system')->realpath($image->uri),
    ];
    $this->drupalPostForm(NULL, $edit, t('Select'));
    $this->assertText('Labels:');
    $this->assertNoText('The media bundle is not configured correctly.');
  }

}
