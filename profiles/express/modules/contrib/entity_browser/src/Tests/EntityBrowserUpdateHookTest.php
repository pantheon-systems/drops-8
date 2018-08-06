<?php

namespace Drupal\entity_browser\Tests;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests the update hooks in entity_browser module.
 *
 * @group entity_browser
 */
class EntityBrowserUpdateHookTest extends UpdatePathTestBase {

  /**
   * Set database dump files to be used.
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../tests/fixtures/update/entity_browser.update-hook-test.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $entity_browser_type = $this->container
      ->get('entity_type.manager')
      ->getDefinition('entity_browser');

    $this->container
      ->get('entity.last_installed_schema.repository')
      ->setLastInstalledDefinition($entity_browser_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    parent::doSelectionTest();
    $this->assertRaw('8001 -   Updates submit text for existing Entity browsers.');
    $this->assertRaw('8002 -   Migrates duplicated Views entity_browser_select fields.');
  }

  /**
   * Tests entity_browser_update_8001().
   */
  public function testSubmitTextUpdate() {
    $this->runUpdates();
    $browser = $this->container->get('config.factory')
      ->get('entity_browser.browser.test_update');

    $this->assertNull($browser->get('submit_text'), 'Old submit text is gone');
    $this->assertEqual($browser->get('widgets.a4ad947c-9669-497c-9988-24351955a02f.settings.submit_text'), 'All animals are created equal','New submit text appears on the widget.');
  }

  /**
   * Tests entity_browser_update_8002().
   */
  public function testViewsFieldUpdate() {
    $this->runUpdates();
    $view = $this->container->get('config.factory')
      ->get('views.view.test_deprecated_field');

    $this->assertEqual($view->get('display.default.display_options.fields.entity_browser_select.table'), 'node', 'Data table in "entity_browser_select" replaced with data field.');
  }

}
