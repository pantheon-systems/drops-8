<?php

namespace Drupal\Tests\diff\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\diff\VisualDiffThemeNegotiator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests theme negotiator.
 *
 * @coversDefaultClass \Drupal\diff\VisualDiffThemeNegotiator
 * @group diff
 */
class VisualDiffThemeNegotiatorTest extends UnitTestCase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $configFactory;

  /**
   * The class under test.
   *
   * @var \Drupal\diff\VisualDiffThemeNegotiator
   */
  protected $themeNegotiator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->themeNegotiator = new VisualDiffThemeNegotiator($this->configFactory->reveal());
  }

  /**
   * @covers ::determineActiveTheme
   */
  public function testDetermineActiveTheme() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('default')->willReturn('the_default_theme');
    $this->configFactory->get('system.theme')->willReturn($config->reveal());

    $route_match = $this->prophesize(RouteMatchInterface::class);
    $result = $this->themeNegotiator->determineActiveTheme($route_match->reveal());
    $this->assertSame('the_default_theme', $result);
  }

  /**
   * Tests if the theme negotiator applies under correct conditions.
   *
   * @param string $filter_parameter
   *   The filter parameter.
   * @param string $route_name
   *   The route name.
   * @param string $config_value
   *   The configuration value of the element taken from the form values.
   * @param bool $expected
   *   The expected result.
   *
   * @covers ::applies
   * @covers ::isDiffRoute
   *
   * @dataProvider providerTestApplies
   */
  public function testApplies($filter_parameter, $route_name, $config_value, $expected) {
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getParameter('filter')->willReturn($filter_parameter);

    if ($route_name) {
      $route_match->getRouteName()->willReturn($route_name);
    }
    else {
      $route_match->getRouteName()->shouldNotBeCalled();
    }

    if ($config_value) {
      $diff_config = $this->prophesize(ImmutableConfig::class);
      $diff_config->get('general_settings.visual_inline_theme')->willReturn($config_value);
      $this->configFactory->get('diff.settings')->willReturn($diff_config->reveal());
    }
    else {
      $this->configFactory->get('diff.settings')->shouldNotBeCalled();
    }

    $this->assertSame($expected, $this->themeNegotiator->applies($route_match->reveal()));
  }

  /**
   * Provides test data to ::testApplies().
   */
  public function providerTestApplies() {
    $data = [];
    $data[] = [
      'unexpected_filter_parameter',
      NULL,
      NULL,
      FALSE,
    ];
    $data[] = [
      'visual_inline',
      'unexpected_route_name',
      NULL,
      FALSE,
    ];
    $data[] = [
      'visual_inline',
      'diff.revisions_diff',
      'unexpected_config_value',
      FALSE,
    ];
    $data[] = [
      'visual_inline',
      'diff.revisions_diff',
      'default',
      TRUE,
    ];
    $data[] = [
      'visual_inline',
      'entity.foo.revisions_diff',
      'default',
      TRUE,
    ];
    return $data;
  }

}
