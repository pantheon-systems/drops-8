<?php

namespace Drupal\Tests\libraries\Functional\ExternalLibrary\Definition;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that remote library definitions are found and downloaded.
 *
 * This is a browser test because Guzzle is not usable from a kernel test.
 *
 * @group libraries
 *
 * @todo Make this a kernel test when https://www.drupal.org/node/2571475 is in.
 */
class ChainDefinitionDiscoveryTest extends BrowserTestBase {

  /**
   * The chained library definition discovery.
   *
   * @var \Drupal\libraries\ExternalLibrary\Definition\DefinitionDiscoveryInterface
   */
  protected $discovery;

  /**
   * The local library definition discovery.
   *
   * @var \Drupal\libraries\ExternalLibrary\Definition\DefinitionDiscoveryInterface
   */
  protected $localDiscovery;

  /**
   * The remote library definition discovery.
   *
   * @var \Drupal\libraries\ExternalLibrary\Definition\DefinitionDiscoveryInterface
   */
  protected $remoteDiscovery;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['libraries'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    // Set up the remote library definition URL to point to the local website.
    $base_url = getenv('SIMPLETEST_BASE_URL');
    $module_path = $module_handler->getModule('libraries')->getPath();
    $url = "$base_url/$module_path/tests/library_definitions";
    $config_factory->getEditable('libraries.settings')
      ->set('definition.remote.url', $url)
      ->save();
    // LibrariesConfigSubscriber::onConfigSave() invalidates the container so
    // that it is rebuilt on the next request. We need the container rebuilt
    // immediately, however.
    $this->rebuildContainer();

    $this->discovery = $this->container->get('libraries.definition.discovery');
    $this->localDiscovery = $this->container->get('libraries.definition.discovery.local');
    $this->remoteDiscovery = $this->container->get('libraries.definition.discovery.remote');
  }

  /**
   * Tests that remote definitions are written locally.
   */
  public function testRemoteFetching() {
    $library_id = 'test_asset_library';
    $expected_definition = [
      'type' => 'asset',
      'version_detector' => [
        'id' => 'static',
        'configuration' => [
          'version' => '1.0.0'
        ],
      ],
      'remote_url' => 'http://example.com',
      'css' => [
        'base' => [
          'example.css' => [],
        ],
      ],
      'js' => [
        'example.js' => [],
      ],
    ];

    $this->assertFalse($this->localDiscovery->hasDefinition($library_id));
    $this->assertTrue($this->remoteDiscovery->hasDefinition($library_id));
    $this->assertEquals($this->remoteDiscovery->getDefinition($library_id), $expected_definition);

    $this->assertTrue($this->discovery->hasDefinition($library_id));
    $this->assertEquals($this->discovery->getDefinition($library_id), $expected_definition);

    $this->assertTrue($this->localDiscovery->hasDefinition($library_id));
    $this->assertEquals($this->localDiscovery->getDefinition($library_id), $expected_definition);
  }

}
