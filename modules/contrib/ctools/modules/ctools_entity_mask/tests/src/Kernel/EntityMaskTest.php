<?php

namespace Drupal\Tests\ctools_entity_mask\Kernel;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\entity_mask_test\Entity\BlockContent;
use Drupal\KernelTests\KernelTestBase;

/**
 * Basic test of entity type masking.
 *
 * @group ctools_entity_mask
 */
class EntityMaskTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'block_content',
    'ctools_entity_mask',
    'entity_mask_test',
    'field',
    'field_ui',
    'file',
    'image',
    'link',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['block_content', 'entity_mask_test']);
    $this->installEntitySchema('fake_block_content');
  }

  /**
   * Tests that fields are correctly masked.
   */
  public function testFields() {
    $block = BlockContent::create([
      'type' => 'basic',
    ]);

    $this->assertTrue($block->hasField('body'));
    $this->assertTrue($block->hasField('field_link'));
    $this->assertTrue($block->hasField('field_image'));
  }

  /**
   * Tests that entity view displays are correctly masked.
   */
  public function testViewDisplays() {
    $view_modes = $this->container
      ->get('entity_display.repository')
      ->getAllViewModes();
    $this->assertSame($view_modes['block_content'], $view_modes['fake_block_content']);

    $display = entity_get_display('fake_block_content', 'basic', 'default');
    $this->assertTrue($display->isNew());

    $components = $display->getComponents();
    $this->assertArrayHasKey('body', $components);
    $this->assertArrayHasKey('field_link', $components);
    $this->assertArrayHasKey('field_image', $components);
  }

  /**
   * Tests that entity form displays are correctly masked.
   */
  public function testFormDisplays() {
    EntityFormMode::create([
      'id' => 'block_content.foobar',
      'label' => $this->randomString(),
      'targetEntityType' => 'block_content',
    ])->save();

    $form_modes = $this->container
      ->get('entity_display.repository')
      ->getAllFormModes();
    $this->assertSame($form_modes['block_content'], $form_modes['fake_block_content']);

    $display = entity_get_form_display('fake_block_content', 'basic', 'default');
    $this->assertTrue($display->isNew());

    $components = $display->getComponents();
    $this->assertArrayHasKey('body', $components);
    $this->assertArrayHasKey('field_link', $components);
    $this->assertArrayHasKey('field_image', $components);
  }

  /**
   * Tests that mask entity types define no tables.
   */
  public function testNoTables() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->container
      ->get('entity_type.manager')
      ->getDefinition('fake_block_content');

    $this->assertNull($entity_type->getBaseTable());
    $this->assertNull($entity_type->getDataTable());
    $this->assertNull($entity_type->getRevisionTable());
    $this->assertNull($entity_type->getRevisionDataTable());
  }

  /**
   * Tests that mask entity types are not exposed to Field UI.
   */
  public function testNotExposedToFieldUI() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->container
      ->get('entity_type.manager')
      ->getDefinition('fake_block_content');

    $this->assertNull($entity_type->get('field_ui_base_route'));
  }

  /**
   * Asserts that a mask entity can be serialized and de-serialized coherently.
   *
   * @depends testFields
   */
  public function testSerialization() {
    $body = $this->getRandomGenerator()->paragraphs(2);
    $link = 'https://www.drupal.org/project/ctools';

    /** @var \Drupal\Core\Entity\EntityInterface $block */
    $block = BlockContent::create([
      'type' => 'basic',
      'body' => $body,
      'field_link' => $link,
    ]);

    $block = serialize($block);
    $block = unserialize($block);

    $this->assertSame($body, $block->body->value);
    $this->assertSame($link, $block->field_link->uri);
  }

  /**
   * Tests that mask entities' isNew() method behaves consistently.
   */
  public function testIsNew() {
    $block = BlockContent::create(['type' => 'basic']);
    $this->assertTrue($block->isNew());
    $block->save();
    $this->assertFalse($block->isNew());
  }

  /**
   * Tests that mask entities' id() method returns the UUID.
   */
  public function testId() {
    $block = BlockContent::create(['type' => 'basic']);
    $this->assertSame($block->id(), $block->uuid());
    $block->save();
    $this->assertSame($block->id(), $block->uuid());
  }

  /**
   * Tests that mask entities cannot be loaded.
   *
   * @depends testId
   */
  public function testLoad() {
    $block = BlockContent::create(['type' => 'basic']);
    $block->save();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('fake_block_content');

    $id = $block->id();
    $this->assertNull($storage->load($id));
    $this->assertEmpty($storage->loadMultiple([$id]));
  }

  /**
   * Tests that deleting a mask entity doesn't throw an exception or anything.
   */
  public function testDelete() {
    $block = BlockContent::create(['type' => 'basic']);
    $block->save();
    $block->delete();
  }

  /**
   * Tests that mask entities have field data after save.
   *
   * @depends testFields
   * @depends testNoTables
   */
  public function testSave() {
    $body = $this->getRandomGenerator()->paragraphs(2);
    $link = 'https://www.drupal.org/project/ctools';

    /** @var \Drupal\Core\Entity\EntityInterface $block */
    $block = BlockContent::create([
      'type' => 'basic',
      'body' => $body,
      'field_link' => $link,
    ]);

    // Ensure that the field values are preserved after save...
    $this->assertSame($body, $block->body->value);
    $this->assertSame($link, $block->field_link->uri);
  }

}
