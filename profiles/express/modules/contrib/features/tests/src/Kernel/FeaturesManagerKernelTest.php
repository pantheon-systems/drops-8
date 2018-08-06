<?php

namespace Drupal\Tests\features\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\features\ConfigurationItem;
use Drupal\features\Package;

/**
 * @group features
 */
class FeaturesManagerKernelTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'config', 'features'];

  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('features');
    $this->installConfig('system');

    $this->featuresManager = $this->container->get('features.manager');
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * @covers \Drupal\features\FeaturesManager::createConfiguration
   */
  public function testCreateConfiguration() {
    $config_name = 'system_simple.testcreate';
    $config = [
      'string_value' => 'example',
      'array_value' => [
        'item1' => 'value1',
        'item2' => 'value2',
      ],
    ];
    $this->featuresManager->createConfiguration([$config_name => $config]);
    $config_item = $this->configFactory->get($config_name);
    $this->assertEquals($config['string_value'], $config_item->get('string_value'), 'Test config string saved');
    $this->assertEquals($config['array_value'], $config_item->get('array_value'), 'Test config array saved');
  }

  /**
   * @covers \Drupal\features\FeaturesManager::import
   */
  public function testImport() {
    $packages = [
      'package' => new Package('package', [
        'configOrig' => ['system_simple.example' => 'system_simple.example'],
        'dependencies' => [],
        'bundle' => '',
      ]),
      'package2' => new Package('package2', [
        'configOrig' => ['system_simple.example2' => 'system_simple.example2'],
        'dependencies' => [],
        'bundle' => '',
      ]),
      'package3' => new Package('package3', [
        'configOrig' => ['system_simple.example3' => 'system_simple.example3'],
        'dependencies' => [],
        'bundle' => '',
      ]),
    ];
    $this->featuresManager->setPackages($packages);

    // Create all three configuration items.
    $config_item = new ConfigurationItem('system_simple.example', ['value' => 'example'], ['package' => 'package']);
    $config_item2 = new ConfigurationItem('system_simple.example2', ['value' => 'example2'], ['package' => 'package2']);
    $config_item3 = new ConfigurationItem('system_simple.example3', ['value' => 'example3'], ['package' => 'package3']);
    // Only save example and example3 as currently active config (so example2 will be new).
    $this->featuresManager->setConfigCollection(['system_simple.example' => $config_item, 'system_simple.example3' => $config_item3]);

    // Only import example and example2, so example3 is unchanged.
    $result = $this->featuresManager->import(['package', 'package2']);
    $this->assertEquals(['system_simple.example'], array_keys($result['package']['updated']), 'Expected config updated');
    $this->assertEquals(['system_simple.example2'], array_keys($result['package2']['new']), 'Expected config created');

    // Test if config was actually saved to the Factory.
    // Cannot test for example2 because we didn't save the original config data
    // and Package2 isn't a real module so config can't be loaded from module.
    $example = $this->configFactory->get('system_simple.example')->get('value');
    $this->assertEquals('example', $example, 'Example config saved');
  }

}
