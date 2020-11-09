<?php

namespace Drupal\Tests\metatag\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests metatag field serialization.
 *
 * @group metatag
 */
class MetatagSerializationTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Core modules.
    'serialization',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->serializer = \Drupal::service('serializer');

    // Create a generic metatag field.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'type' => 'metatag',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests the deserialization.
   */
  public function testMetatagDeserialization() {
    $entity = EntityTest::create();
    $json = json_decode($this->serializer->serialize($entity, 'json'), TRUE);
    $json['field_test'][0]['value'] = 'string data';
    $serialized = json_encode($json, TRUE);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "field_test" field (field item class: Drupal\metatag\Plugin\Field\FieldType\MetatagFieldItem).');
    $this->serializer->deserialize($serialized, EntityTest::class, 'json');
  }

}
