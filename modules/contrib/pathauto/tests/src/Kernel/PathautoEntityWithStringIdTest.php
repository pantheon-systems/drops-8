<?php

namespace Drupal\Tests\pathauto\Kernel;

use Drupal\Component\Serialization\PhpSerialize;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;
use Drupal\pathauto\PathautoState;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\pathauto_string_id_test\Entity\PathautoStringIdTest;

/**
 * Tests auto-aliasing of entities that use string IDs.
 *
 * @group pathauto
 */
class PathautoEntityWithStringIdTest extends KernelTestBase {

  use PathautoTestHelperTrait;

  /**
   * The alias type plugin instance.
   *
   * @var \Drupal\pathauto\AliasTypeBatchUpdateInterface
   */
  protected $aliasType;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'token',
    'path',
    'pathauto',
    'pathauto_string_id_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Kernel tests are using the 'keyvalue.memory' store but we want to test
    // against the 'keyvalue.database'.
    $container
      ->register('keyvalue.database', KeyValueDatabaseFactory::class)
      ->addArgument(new PhpSerialize())
      ->addArgument($container->get('database'))
      ->addTag('persist');
    $container->setAlias('keyvalue', 'keyvalue.database');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['key_value']);
    $this->installConfig(['system', 'pathauto']);
    if ($this->container->get('entity_type.manager')->hasDefinition('path_alias')) {
      $this->installEntitySchema('path_alias');
    }
    $this->installEntitySchema('pathauto_string_id_test');
    $this->createPattern('pathauto_string_id_test', '/[pathauto_string_id_test:name]');
    /** @var \Drupal\pathauto\AliasTypeManager $alias_type_manager */
    $alias_type_manager = $this->container->get('plugin.manager.alias_type');
    $this->aliasType = $alias_type_manager->createInstance('canonical_entities:pathauto_string_id_test');
  }

  /**
   * Test aliasing entities with long string ID.
   *
   * @dataProvider entityWithStringIdProvider
   *
   * @param string|int $id
   *   The entity ID
   * @param string $expected_key
   *   The expected key for 'pathauto_state.*' collections.
   */
  public function testEntityWithStringId($id, $expected_key) {
    $entity = PathautoStringIdTest::create([
      'id' => $id,
      'name' => $name = $this->randomMachineName(),
    ]);
    $entity->save();

    // Check that the path was generated.
    $this->assertEntityAlias($entity, mb_strtolower("/$name"));
    // Check that the path auto state was saved with the expected key.
    $value = \Drupal::keyValue('pathauto_state.pathauto_string_id_test')->get($expected_key);
    $this->assertEquals(PathautoState::CREATE, $value);

    $context = [];
    // Batch delete uses the key-value store collection 'pathauto_state.*. We
    // test that after a bulk delete all aliases are removed. Running only once
    // the batch delete process is enough as the batch size is 100.
    $this->aliasType->batchDelete($context);

    // Check that the paths were removed on batch delete.
    $this->assertNoEntityAliasExists($entity, "/$name");
  }

  /**
   * Provides test cases for ::testEntityWithStringId().
   *
   * @see \Drupal\Tests\pathauto\Kernel\PathautoEntityWithStringIdTest::testEntityWithStringId()
   */
  public function entityWithStringIdProvider() {
    return [
      'ascii with less or equal 128 chars' => [
        str_repeat('a', 128), str_repeat('a', 128)
      ],
      'ascii with over 128 chars' => [
        str_repeat('a', 191), Crypt::hashBase64(str_repeat('a', 191))
      ],
      'non-ascii with less or equal 128 chars' => [
        str_repeat('社', 128), Crypt::hashBase64(str_repeat('社', 128))
      ],
      'non-ascii with over 128 chars' => [
        str_repeat('社', 191), Crypt::hashBase64(str_repeat('社', 191))
      ],
      'simulating an integer id' => [
        123, '123'
      ],
    ];
  }

}
