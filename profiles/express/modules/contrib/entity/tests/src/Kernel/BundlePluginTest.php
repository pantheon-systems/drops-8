<?php

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity_module_bundle_plugin_test\Entity\EntityTestBundlePlugin;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the bundle plugin API.
 *
 * @group entity
 */
class BundlePluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');

    // Install the modules properly. Putting them into static::$modules doesn't trigger the install
    // hooks, like hook_modules_installed, so entity_modules_installed is not triggered().
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->install(['entity_module_bundle_plugin_test', 'entity_module_bundle_plugin_examples_test']);
  }

  /**
   * Tests the bundle plugins.
   */
  public function testPluginBundles() {
    $bundled_entity_types = entity_get_bundle_plugin_entity_types();
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $bundled_entity_types['entity_test_bundle_plugin'];
    $this->assertEquals('entity_test_bundle_plugin', $entity_type->id());
    $this->assertTrue($entity_type->hasHandlerClass('bundle_plugin'));

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info */
    $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundle_info = $entity_type_bundle_info->getBundleInfo('entity_test_bundle_plugin');
    $this->assertEquals(2, count($bundle_info));
    $this->assertArrayHasKey('first', $bundle_info);
    $this->assertArrayHasKey('second', $bundle_info);
    $this->assertEquals('First', $bundle_info['first']['label']);
    $this->assertEquals('Some description', $bundle_info['first']['description']);

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions('entity_test_bundle_plugin');
    $this->assertArrayHasKey('first_mail', $field_storage_definitions);
    $this->assertArrayHasKey('second_mail', $field_storage_definitions);
    $first_field_definitions = $entity_field_manager->getFieldDefinitions('entity_test_bundle_plugin', 'first');
    $this->assertArrayHasKey('first_mail', $first_field_definitions);
    $this->assertArrayNotHasKey('second_mail', $first_field_definitions);
    $second_field_definitions = $entity_field_manager->getFieldDefinitions('entity_test_bundle_plugin', 'second');
    $this->assertArrayNotHasKey('first_mail', $second_field_definitions);
    $this->assertArrayHasKey('second_mail', $second_field_definitions);

    $first_entity = EntityTestBundlePlugin::create([
      'type' => 'first',
      'first_mail' => 'admin@test.com',
    ]);
    $first_entity->save();
    $first_entity = EntityTestBundlePlugin::load($first_entity->id());
    $this->assertEquals('admin@test.com', $first_entity->first_mail->value);

    $second_entity = EntityTestBundlePlugin::create([
      'type' => 'second',
      'second_mail' => 'admin@example.com',
    ]);
    $second_entity->save();
    $second_entity = EntityTestBundlePlugin::load($second_entity->id());
    $this->assertEquals('admin@example.com', $second_entity->second_mail->value);

    // Also test entity queries.
    $result = \Drupal::entityTypeManager()->getStorage('entity_test_bundle_plugin')
      ->getQuery()
      ->condition('second_mail', 'admin@example.com')
      ->execute();
    $this->assertEquals([$second_entity->id() => $second_entity->id()], $result);

    $result = \Drupal::entityTypeManager()->getStorage('entity_test_bundle_plugin')
      ->getQuery()
      ->condition('type', 'first')
      ->execute();
    $this->assertEquals([$first_entity->id() => $first_entity->id()], $result);

  }

  /**
   * Tests the uninstallation for a bundle provided by a module.
   */
  public function testBundlePluginModuleUninstallation() {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');

    // One should be possible to uninstall without any actual content.
    $violations = $module_installer->validateUninstall(['entity_module_bundle_plugin_examples_test']);
    $this->assertEmpty($violations);

    $first_entity = EntityTestBundlePlugin::create([
      'type' => 'first',
      'first_mail' => 'admin@test.com',
    ]);
    $first_entity->save();
    $second_entity = EntityTestBundlePlugin::create([
      'type' => 'second',
      'second_mail' => 'admin@example.com',
    ]);
    $second_entity->save();

    $violations = $module_installer->validateUninstall(['entity_module_bundle_plugin_examples_test']);
    $this->assertCount(1, $violations);
    $this->assertCount(1, $violations['entity_module_bundle_plugin_examples_test']);
    $this->assertEquals('There is data for the bundle Second on the entity type Entity test bundle plugin. Please remove all content before uninstalling the module.', $violations['entity_module_bundle_plugin_examples_test'][0]);

    $second_entity->delete();

    // The first entity is defined by entity_module_bundle_plugin_test, so it should be possible
    // to uninstall the module providing the second bundle plugin.
    $violations = $module_installer->validateUninstall(['entity_module_bundle_plugin_examples_test']);
    $this->assertEmpty($violations);

    $module_installer->uninstall(['entity_module_bundle_plugin_examples_test']);

    // The first entity is provided by entity_module_bundle_plugin_test which we cannot uninstall,
    // until all the entities are deleted.
    $violations = $module_installer->validateUninstall(['entity_module_bundle_plugin_test']);
    $this->assertNotEmpty($violations);

    $first_entity->delete();
    $violations = $module_installer->validateUninstall(['entity_module_bundle_plugin_test']);
    $this->assertEmpty($violations);
  }

}
