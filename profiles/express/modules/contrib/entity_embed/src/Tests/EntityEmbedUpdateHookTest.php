<?php

namespace Drupal\entity_embed\Tests;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests the update hooks in entity_embed module.
 *
 * @group entity_embed
 */
class EntityEmbedUpdateHookTest extends UpdatePathTestBase {

  /**
   * Set database dump files to be used.
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../tests/fixtures/update/entity_embed.update-hook-test.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $button = $this->container
      ->get('entity_type.manager')
      ->getDefinition('embed_button');

    $this->container
      ->get('entity.last_installed_schema.repository')
      ->setLastInstalledDefinition($button);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    parent::doSelectionTest();
    $this->assertRaw('8002 -   Updates the default mode settings.');
    $this->assertRaw('8003 -   Updates allowed HTML for all filter format config entities that have an  Entity Embed button.');
  }

  /**
   * Tests entity_embed_update_8002().
   */
  public function todotestPostUpdate() {
    $this->runUpdates();
    $mode = $this->container->get('config.factory')
      ->get('entity_embed.settings')
      ->get('rendered_entity_mode');
    $this->assertTrue($mode, 'Render entity mode settings after update is correct.');
  }

  /**
   * Tests entity_embed_update_8003().
   */
  public function testAllowedHTML() {
    $allowed_html = '<drupal-entity data-entity-type data-entity-uuid data-entity-embed-display data-entity-embed-settings data-align data-caption data-embed-button>';
    $expected_allowed_html = '<drupal-entity data-entity-type data-entity-uuid data-entity-embed-display data-entity-embed-settings data-entity-embed-display-settings data-align data-caption data-embed-button>';
    $filter_format = $this->container->get('entity_type.manager')->getStorage('filter_format')->load('full_html');
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => $allowed_html,
      ],
    ])->save();
    $editor = $this->container->get('entity_type.manager')->getStorage('editor')->load('full_html');
    $button = $this->container->get('entity_type.manager')->getStorage('embed_button')->load('node');
    $editor->setSettings(['toolbar' => ['rows' => [0 => [0 => ['items' => [0 => $button->id()]]]]]])->save();
    $this->runUpdates();
    $filter_format = $this->container->get('entity_type.manager')->getStorage('filter_format')->load('full_html');
    $filter_html = $filter_format->filters('filter_html');
    $this->assertEqual($expected_allowed_html, $filter_html->getConfiguration()['settings']['allowed_html'], 'Allowed html is correct');
  }

}
