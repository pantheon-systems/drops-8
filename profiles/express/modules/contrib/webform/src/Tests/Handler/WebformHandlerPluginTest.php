<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for the webform handler plugin.
 *
 * @group Webform
 */
class WebformHandlerPluginTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_handler'];

  /**
   * Tests webform element plugin.
   */
  public function testWebformHandler() {
    $webform = Webform::load('contact');

    // Check initial dependencies.
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform']]);

    /** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
    $handler_manager = $this->container->get('plugin.manager.webform.handler');

    // Add 'test' handler provided by the webform_test.module.
    $webform_handler_configuration = [
      'id' => 'test',
      'label' => 'test',
      'handler_id' => 'test',
      'status' => 1,
      'weight' => 2,
      'settings' => [],
    ];
    $webform_handler = $handler_manager->createInstance('test', $webform_handler_configuration);
    $webform->addWebformHandler($webform_handler);
    $webform->save();

    // Check that handler has been added to the dependencies.
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform_test_handler', 'webform']]);

    // Uninstall the webform_test.module which will also remove the
    // debug handler.
    $this->container->get('module_installer')->uninstall(['webform_test_handler']);
    $webform = Webform::load('contact');

    // Check that handler was removed from the dependencies.
    $this->assertNotEqual($webform->getDependencies(), ['module' => ['webform_test_handler', 'webform']]);
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform']]);
  }

}
