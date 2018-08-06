<?php

namespace Drupal\Tests\video_embed_field\Kernel;

use Drupal\image\Entity\ImageStyle;

/**
 * Test the configuration dependencies are created correctly.
 *
 * @group video_embed_field
 */
class FormatterDependenciesTest extends KernelTestBase {

  /**
   * A test image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $style;

  /**
   * A test image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $replacementStyle;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->style = ImageStyle::create(['name' => 'style_foo', 'label' => $this->randomString()]);
    $this->style->save();
    $this->replacementStyle = ImageStyle::create(['name' => 'style_bar', 'label' => $this->randomString()]);
    $this->replacementStyle->save();

    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

  /**
   * Test dependencies are created correctly added for the image formatter.
   */
  public function testThumbnailConfigDependencies() {
    $this->assertFormatterDependencyBehavior([
      'type' => 'video_embed_field_thumbnail',
      'settings' => [
        'image_style' => $this->style->id(),
      ],
    ]);
  }

  /**
   * Test dependencies are created correctly added for the colorbox formatter.
   */
  public function testColorboxConfigDependencies() {
    $this->assertFormatterDependencyBehavior([
      'type' => 'video_embed_field_colorbox',
      'settings' => [
        'image_style' => $this->style->id(),
      ],
    ]);
  }

  /**
   * Assert the behavior of the formatter dependencies.
   *
   * @param array $formatter_settings
   *   The formatter settings to apply to the entity dispaly.
   */
  protected function assertFormatterDependencyBehavior($formatter_settings) {
    // Assert the image style becomes a dependency of the entity display.
    $this->loadEntityDisplay()->setComponent($this->fieldName, $formatter_settings)->save();
    $this->assertTrue(in_array('image.style.' . $this->style->id(), $this->loadEntityDisplay()->getDependencies()['config']), 'The image style was correctly added as a dependency the entity display config object.');
    // Delete the image style.
    $storage = $this->entityTypeManager->getStorage('image_style');
    $storage->setReplacementId($this->style->id(), $this->replacementStyle->id());
    $this->style->delete();
    // Ensure the replacement is now a dependency.
    $this->assertTrue(in_array('image.style.' . $this->replacementStyle->id(), $this->loadEntityDisplay()->getDependencies()['config']), 'The replacement style was added to the entity display.');
  }

  /**
   * Load the entity display for the test entity.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The entity display for the test entity.
   */
  protected function loadEntityDisplay() {
    return $this->entityTypeManager->getStorage('entity_view_display')->load('entity_test.entity_test.default');
  }

}
