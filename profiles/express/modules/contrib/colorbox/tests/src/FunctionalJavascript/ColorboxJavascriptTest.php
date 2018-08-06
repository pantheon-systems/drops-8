<?php

namespace Drupal\Tests\colorbox\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Test the colorbox JavaScript.
 *
 * @group colorbox
 */
class ColorboxJavascriptTest extends JavascriptTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'colorbox',
    'colorbox_library_test',
    'node',
  ];

  /**
   * How long to wait for colorbox to launch.
   */
  const COLORBOX_WAIT_TIMEOUT = 500;

  /**
   * Node var.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Test the colorbox launches when a gallery is clicked.
   */
  public function testColorboxLaunches() {
    $this->drupalGet('node/' . $this->node->id());
    $this->getSession()->getPage()->find('css', 'img')->click();
    $this->getSession()->wait(static::COLORBOX_WAIT_TIMEOUT);
    $this->assertSession()->elementContains('css', '#colorbox', 'test.png');
  }

  /**
   * Test the gallery works.
   */
  public function testColorboxGallery() {
    $this->drupalGet('node/' . $this->node->id());

    // Click and launch the gallery.
    $this->getSession()->getPage()->find('css', 'img')->click();
    $this->getSession()->wait(static::COLORBOX_WAIT_TIMEOUT);
    $this->assertSession()->elementContains('css', '#cboxTitle', 'Image title 1');
    $this->assertSession()->elementContains('css', '#colorbox', 'test.png');

    // Click on the next image and assert the second image is visible.
    $this->assertSession()->elementExists('css', '#cboxNext')->click();
    $this->getSession()->wait(static::COLORBOX_WAIT_TIMEOUT);
    $this->assertSession()->elementContains('css', '#cboxTitle', 'Image title 2');
    $this->assertSession()->elementContains('css', '#colorbox', 'test.png');

    // Use alt captions.
    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.page.default')
      ->setComponent('field_test_image', [
        'type' => 'colorbox',
        'settings' => ['colorbox_caption' => 'alt'],
        'status' => TRUE,
      ])
      ->save();
    drupal_flush_all_caches();

    // Ensure the caption being used is the alt text.
    $this->drupalGet('node/' . $this->node->id());
    $this->getSession()->getPage()->find('css', 'img')->click();
    $this->getSession()->wait(static::COLORBOX_WAIT_TIMEOUT);
    $this->assertSession()->elementContains('css', '#cboxTitle', 'Image alt 1');
  }

  /**
   * Test the mobile detection.
   */
  public function testMobileDetection() {
    $this->changeSetting('advanced.mobile_detect', TRUE);
    $this->changeSetting('advanced.mobile_detect_width', '1200px');
    $this->getSession()->resizeWindow(200, 200);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->elementAttributeContains('css', '#colorbox', 'style', 'display: none;');
  }

  /**
   * Test the admin form.
   */
  public function testAdminForm() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));
    $this->drupalGet('admin/config/media/colorbox');
    $this->assertFalse($this->getSession()->getPage()->find('css', '.form-item-colorbox-transition-speed')->isVisible());
    $this->assertSession()->fieldExists('colorbox_custom_settings_activate')->setValue(TRUE);
    $this->assertTrue($this->getSession()->getPage()->find('css', '.form-item-colorbox-transition-speed')->isVisible());
    $this->assertSession()->fieldExists('colorbox_overlayclose')->setValue(FALSE);
    $this->getSession()->getPage()->find('css', '.form-submit')->click();
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

  /**
   * Change a colorbox setting.
   *
   * @param string $setting
   *   The name of the setting.
   * @param string $value
   *   The value.
   */
  protected function changeSetting($setting, $value) {
    \Drupal::configFactory()
      ->getEditable('colorbox.settings')
      ->set($setting, $value)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    FieldStorageConfig::create(array(
      'field_name' => 'field_test_image',
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => [],
      'cardinality' => 3,
    ))->save();
    $field_config = FieldConfig::create([
      'field_name' => 'field_test_image',
      'label' => 'Colorbox Field',
      'entity_type' => 'node',
      'bundle' => 'page',
      'required' => TRUE,
      'settings' => [],
    ]);
    $field_config->save();
    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.page.default')
      ->setComponent('field_test_image', [
        'type' => 'colorbox',
        'settings' => [],
        'status' => TRUE,
      ])
      ->save();
    file_unmanaged_copy(DRUPAL_ROOT . '/core/modules/simpletest/files/image-1.png', 'public://test.png');
    $file_a = File::create([
      'uri' => 'public://test.png',
      'filename' => 'test.png',
    ]);
    $file_a->save();
    $file_b = File::create([
      'uri' => 'public://test.png',
      'filename' => 'test.png',
    ]);
    $file_b->save();
    $this->node = $this->createNode([
      'type' => 'page',
      'field_test_image' => [
        [
          'target_id' => $file_a->id(),
          'alt' => 'Image alt 1',
          'title' => 'Image title 1',
        ],
        [
          'target_id' => $file_b->id(),
          'alt' => 'Image alt 2',
          'title' => 'Image title 2',
        ],
      ],
    ]);
  }

}
