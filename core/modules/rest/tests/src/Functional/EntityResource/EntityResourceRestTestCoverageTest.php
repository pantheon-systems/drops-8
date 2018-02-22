<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Checks that all core content/config entity types have REST test coverage.
 *
 * Every entity type must have test coverage for:
 * - every format in core (json + xml + hal_json)
 * - every authentication provider in core (anon, cookie, basic_auth)
 *
 * @group rest
 */
class EntityResourceRestTestCoverageTest extends BrowserTestBase {

  /**
   * Entity definitions array.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $all_modules = system_rebuild_module_data();
    $stable_core_modules = array_filter($all_modules, function ($module) {
      // Filter out contrib, hidden, testing, and experimental modules. We also
      // don't need to enable modules that are already enabled.
      return
        $module->origin === 'core' &&
        empty($module->info['hidden']) &&
        $module->status == FALSE &&
        $module->info['package'] !== 'Testing' &&
        $module->info['package'] !== 'Core (Experimental)';
    });

    $this->container->get('module_installer')->install(array_keys($stable_core_modules));
    $this->rebuildContainer();

    $this->definitions = $this->container->get('entity_type.manager')->getDefinitions();

    // Entity types marked as "internal" are not exposed by the entity REST
    // resource plugin and hence also don't need test coverage.
    $this->definitions = array_filter($this->definitions, function (EntityTypeInterface $entity_type) {
      return !$entity_type->isInternal();
    });
  }

  /**
   * Tests that all core content/config entity types have REST test coverage.
   */
  public function testEntityTypeRestTestCoverage() {
    $default_test_locations = [
      // Test coverage for formats provided by the 'serialization' module.
      'serialization' => [
        'possible paths' => [
          '\Drupal\Tests\rest\Functional\EntityResource\CLASS\CLASS',
        ],
        'class suffix' => [
          'JsonAnonTest',
          'JsonBasicAuthTest',
          'JsonCookieTest',
          'XmlAnonTest',
          'XmlBasicAuthTest',
          'XmlCookieTest',
        ],
      ],
      // Test coverage for formats provided by the 'hal' module.
      'hal' => [
        'possible paths' => [
          '\Drupal\Tests\hal\Functional\EntityResource\CLASS\CLASS',
        ],
        'class suffix' => [
          'HalJsonAnonTest',
          'HalJsonBasicAuthTest',
          'HalJsonCookieTest',
        ],
      ],
    ];

    $problems = [];
    foreach ($this->definitions as $entity_type_id => $info) {
      $class_name_full = $info->getClass();
      $parts = explode('\\', $class_name_full);
      $class_name = end($parts);
      $module_name = $parts[1];

      // The test class can live either in the REST/HAL module, or in the module
      // providing the entity type.
      $tests = $default_test_locations;
      $tests['serialization']['possible paths'][] = '\Drupal\Tests\\' . $module_name . '\Functional\Rest\CLASS';
      $tests['hal']['possible paths'][] = '\Drupal\Tests\\' . $module_name . '\Functional\Hal\CLASS';

      foreach ($tests as $module => $info) {
        $possible_paths = $info['possible paths'];
        $missing_tests = [];
        foreach ($info['class suffix'] as $postfix) {
          foreach ($possible_paths as $path) {
            $class = str_replace('CLASS', $class_name, $path . $postfix);
            if (class_exists($class)) {
              continue 2;
            }
          }
          $missing_tests[] = $postfix;
        }
        if (!empty($missing_tests)) {
          $missing_tests_list = implode(', ', array_map(function ($missing_test) use ($class_name) {
            return $class_name . $missing_test;
          }, $missing_tests));
          $which_normalization = $module === 'serialization' ? 'default' : $module;
          $problems[] = "$entity_type_id: $class_name ($class_name_full), $which_normalization normalization (expected tests: $missing_tests_list)";
        }
      }
    }
    $all = count($this->definitions);
    $good = $all - count($problems);
    $this->assertSame([], $problems, $this->getLlamaMessage($good, $all));
  }

  /**
   * Message from Llama.
   *
   * @param int $g
   *   A count of entities with test coverage.
   * @param int $a
   *   A count of all entities.
   *
   * @return string
   *   An information about progress of REST test coverage.
   */
  protected function getLlamaMessage($g, $a) {
    return "
☼
      ________________________
     /           Hi!          \\
    |  It's llame to not have  |
    |   complete REST tests!   |
    |                          |
    |     Progress: $g/$a.     |
    | ________________________/
    |/
//  o
l'>
ll
llama
|| ||
'' ''
";
  }

}
