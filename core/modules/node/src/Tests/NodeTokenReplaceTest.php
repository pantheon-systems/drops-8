<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeTokenReplaceTest.
 */

namespace Drupal\node\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\system\Tests\System\TokenReplaceUnitTestBase;

/**
 * Generates text using placeholders for dummy content to check node token
 * replacement.
 *
 * @group node
 */
class NodeTokenReplaceTest extends TokenReplaceUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('filter', 'node'));

    $node_type = entity_create('node_type', array('type' => 'article', 'name' => 'Article'));
    $node_type->save();
    node_add_body_field($node_type);
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  function testNodeTokenReplacement() {
    $url_options = array(
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    );

    // Create a user and a node.
    $account = $this->createUser();
    /* @var $node \Drupal\node\NodeInterface */
    $node = entity_create('node', array(
      'type' => 'article',
      'tnid' => 0,
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => [['value' => 'Regular NODE body for the test.', 'summary' => 'Fancy NODE summary.', 'format' => 'plain_text']],
    ));
    $node->save();

    // Generate and test tokens.
    $tests = array();
    $tests['[node:nid]'] = $node->id();
    $tests['[node:vid]'] = $node->getRevisionId();
    $tests['[node:type]'] = 'article';
    $tests['[node:type-name]'] = 'Article';
    $tests['[node:title]'] = Html::escape($node->getTitle());
    $tests['[node:body]'] = $node->body->processed;
    $tests['[node:summary]'] = $node->body->summary_processed;
    $tests['[node:langcode]'] = $node->language()->getId();
    $tests['[node:url]'] = $node->url('canonical', $url_options);
    $tests['[node:edit-url]'] = $node->url('edit-form', $url_options);
    $tests['[node:author]'] = $account->getUsername();
    $tests['[node:author:uid]'] = $node->getOwnerId();
    $tests['[node:author:name]'] = $account->getUsername();
    $tests['[node:created:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($node->getCreatedTime(), array('langcode' => $this->interfaceLanguage->getId()));
    $tests['[node:changed:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($node->getChangedTime(), array('langcode' => $this->interfaceLanguage->getId()));

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($node);

    $metadata_tests = [];
    $metadata_tests['[node:nid]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:vid]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:type]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:type-name]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:title]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:body]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:summary]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:langcode]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[node:edit-url]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:author]'] = $bubbleable_metadata->addCacheTags(['user:1']);
    $metadata_tests['[node:author:uid]'] = $bubbleable_metadata;
    $metadata_tests['[node:author:name]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[node:created:since]'] = $bubbleable_metadata->setCacheMaxAge(0);
    $metadata_tests['[node:changed:since]'] = $bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($input, array('node' => $node), array('langcode' => $this->interfaceLanguage->getId()), $bubbleable_metadata);
      $this->assertEqual($output, $expected, format_string('Node token %token replaced.', ['%token' => $input]));
      $this->assertEqual($bubbleable_metadata, $metadata_tests[$input]);
    }

    // Repeat for a node without a summary.
    $node = entity_create('node', array(
      'type' => 'article',
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => [['value' => 'A string that looks random like TR5c2I', 'format' => 'plain_text']],
    ));
    $node->save();

    // Generate and test token - use full body as expected value.
    $tests = array();
    $tests['[node:summary]'] = $node->body->processed;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated for node without a summary.');

    foreach ($tests as $input => $expected) {
      $output = $this->tokenService->replace($input, array('node' => $node), array('language' => $this->interfaceLanguage));
      $this->assertEqual($output, $expected, new FormattableMarkup('Node token %token replaced for node without a summary.', ['%token' => $input]));
    }
  }

}
