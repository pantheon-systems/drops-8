<?php

namespace Drupal\Tests\webform\Unit\Plugin\WebformSourceEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\webform\Plugin\WebformSourceEntity\RouteParametersWebformSourceEntity;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Tests the "route_parameters" webform source entity plugin.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Plugin\WebformSourceEntity\RouteParametersWebformSourceEntity
 */
class RouteParametersWebformSourceEntityTest extends UnitTestCase {

  /**
   * Tests detection of source entity via route parameters.
   *
   * @param array $route_parameters
   *   Route parameters array to inject. You may use the following magic values:
   *   - source_entity: to denote that this parameter should contain source
   *     entity
   *   - another_entity: to denote that this parameter should contain some other
   *     entity.
   * @param array $ignored_types
   *   Array of entity types that may not be source.
   * @param bool $expect_source_entity
   *   Whether we expect the tested method to return the source entity.
   * @param string $assert_message
   *   Assert message to use.
   *
   * @see RouteParametersWebformSourceEntity::getSourceEntity()
   *
   * @dataProvider providerGetCurrentSourceEntity
   */
  public function testGetCurrentSourceEntity(array $route_parameters, array $ignored_types, $expect_source_entity, $assert_message = '') {
    $route_match = $this->getMockBuilder(RouteMatchInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $source_entity = $this->getMockBuilder(EntityInterface::class)
      ->getMock();

    // Process $route_parameters to unwrap it with real objects.
    if (isset($route_parameters['source_entity'])) {
      $route_parameters['source_entity'] = $source_entity;
    }
    if (isset($route_parameters['another_entity'])) {
      $route_parameters['another_entity'] = $this->getMockBuilder(EntityInterface::class)
        ->getMock();
    }

    $route_match->method('getParameters')
      ->willReturn(new ParameterBag($route_parameters));

    $plugin = new RouteParametersWebformSourceEntity([], 'route_parameters', [], $route_match);
    $output = $plugin->getSourceEntity($ignored_types);

    if ($expect_source_entity) {
      $this->assertSame($source_entity, $output, $assert_message);
    }
    else {
      $this->assertNull($output, $assert_message);
    }
  }

  /**
   * Data provider for testGetCurrentSourceEntity().
   *
   * @see testGetCurrentSourceEntity()
   */
  public function providerGetCurrentSourceEntity() {
    $tests[] = [[], [], FALSE, 'Empty parameters'];
    $tests[] = [['source_entity' => 1], [], TRUE, 'Just source entity in the parameters'];
    $tests[] = [['source_entity' => 1], ['source_entity'], FALSE, 'Source entity in the parameters but forced to ignore'];
    $tests[] = [['another_entity' => 1, 'source_entity' => 1, 'static_value' => 'static_value'], [], TRUE, 'Testing the order in which parameters are scanned'];
    return $tests;
  }

}
