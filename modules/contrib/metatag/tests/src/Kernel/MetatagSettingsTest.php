<?php

namespace Drupal\Tests\metatag\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the Metatag settings.
 *
 * @group metatag
 */
class MetatagSettingsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['metatag'];

  /**
   * The metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->metatagManager = $this->container->get('metatag.manager');
    $this->configFactory = $this->container->get('config.factory');

    $this->installConfig(['metatag']);
  }

  /**
   * Tests the Metatag settings.
   */
  public function testMetatagSettings() {
    $metatag_groups = $this->metatagManager->sortedGroups();
    $config = $this->configFactory->getEditable('metatag.settings');

    $group = reset($metatag_groups);
    $group_id = $group['id'];

    $value = [];
    $value['user']['user'][$group_id] = $group_id;
    $config->set('entity_type_groups', $value)->save();

    $this->assertSame($value, $config->get('entity_type_groups'));
  }

}
