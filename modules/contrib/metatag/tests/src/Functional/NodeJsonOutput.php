<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the JSON output from core works as intended.
 *
 * @group metatag
 */
class NodeJsonOutput extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',

    // The modules to test.
    'serialization',
    'hal',
    'rest',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * Create an entity, view its JSON output, confirm Metatag data exists.
   */
  public function testNode() {
    $this->provisionResource();

    /* @var\Drupal\node\NodeInterface $node */
    $node = $this->createContentTypeNode('Test JSON output', 'Testing JSON output for a content type');
    $url = $node->toUrl();

    // Load the node's page.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Load the JSON output.
    $url->setOption('query', ['_format' => 'json']);
    $response = $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Decode the JSON output.
    $response = $this->getRawContent();
    $this->assertTrue(!empty($response));
    $json = json_decode($response);
    $this->verbose($json, 'JSON output');
    $this->assertTrue(!empty($json));

    // Confirm the JSON object's values.
    $this->assertTrue(isset($json->nid));
    if (isset($json->nid)) {
      $this->assertTrue($json->nid[0]->value == $node->id());
    }
    $this->assertTrue(isset($json->metatag));
    if (isset($json->metatag)) {
      $this->assertTrue($json->metatag->value->title == $node->label() . ' | Drupal');
      // @todo Test other meta tags.
    }
  }

  /**
   * Provisions the REST resource under test.
   *
   * @param string $entity_type
   *   The entity type to be enabled; defaults to 'node'.
   * @param array $formats
   *   The allowed formats for this resource; defaults to ['json'].
   * @param array $authentication
   *   The allowed authentication providers for this resource; defaults to
   *   ['basic_auth'].
   */
  protected function provisionResource($entity_type = 'node', array $formats = [], array $authentication = []) {
    $this->resourceConfigStorage = $this->container
      ->get('entity_type.manager')
      ->getStorage('rest_resource_config');

    // Defaults.
    if (empty($formats)) {
      $formats[] = 'json';
    }
    if (empty($authentication)) {
      $authentication[] = 'basic_auth';
    }

    $this->resourceConfigStorage->create([
      'id' => 'entity.' . $entity_type,
      'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
      'configuration' => [
        'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
        'formats' => $formats,
        'authentication' => $authentication,
      ],
      'status' => TRUE,
    ])->save();

    // Ensure that the cache tags invalidator has its internal values reset.
    // Otherwise the http_response cache tag invalidation won't work.
    // Clear the tag cache.
    \Drupal::service('cache_tags.invalidator')->resetChecksums();
    foreach (Cache::getBins() as $backend) {
      if (is_callable([$backend, 'reset'])) {
        $backend->reset();
      }
    }
    $this->container->get('config.factory')->reset();
    $this->container->get('state')->resetCache();

    // Tests using this base class may trigger route rebuilds due to changes to
    // RestResourceConfig entities or 'rest.settings'. Ensure the test generates
    // routes using an up-to-date router.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

}
