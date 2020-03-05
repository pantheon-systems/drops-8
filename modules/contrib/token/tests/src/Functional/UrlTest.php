<?php

namespace Drupal\Tests\token\Functional;

use Drupal\block\Entity\Block;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests URL tokens.
 *
 * @group token
 */
class UrlTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * The first testing node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node1;

  /**
   * The second testing node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node2;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = ['node', 'token', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();

    $this->node1 = Node::create([
      'type' => 'article',
      'title' => 'Test Node 1',
    ]);
    $this->node1->save();

    $this->node2 = Node::create([
      'type' => 'article',
      'title' => 'Test Node 2',
    ]);
    $this->node2->save();

  }

  /**
   * Creates a block with token for title and tests cache contexts.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testBlockUrlTokenReplacement() {

    $node1_url = $this->node1->toUrl();
    $node2_url = $this->node2->toUrl();

    // Using a @dataprovider causes repeated database installations and takes a
    // very long time.
    $tests = [];
    $tests[] = [
      'token' => 'prefix_[current-page:url:path]_suffix',
      'expected1' => 'prefix_/' . $node1_url->getInternalPath() . '_suffix',
      'expected2' => 'prefix_/' . $node2_url->getInternalPath() . '_suffix',
      // A path can only be generated from a routed path.
      'expected3' => 'prefix_/_suffix',
    ];
    $tests[] = [
      'token' => 'prefix_[current-page:url]_suffix',
      'expected1' => 'prefix_' . $node1_url->setAbsolute()->toString() . '_suffix',
      'expected2' => 'prefix_' . $node2_url->setAbsolute()->toString() . '_suffix',
      'expected3' => 'prefix_' . $this->getAbsoluteUrl('does-not-exist') . '_suffix',
    ];

    // Place a standard block and use a token in the label.
    $edit = [
      'id' => 'token_url_test_block',
      'label' => 'label',
      'label_display' => TRUE,
    ];
    $this->placeBlock('system_powered_by_block', $edit);
    $block = Block::load('token_url_test_block');

    $assert_session = $this->assertSession();

    foreach ($tests as $test) {
      // Set the block label.
      $block->getPlugin()->setConfigurationValue('label', $test['token']);
      $block->save();

      // Go to the first node page and test that the token is correct.
      $this->drupalGet($node1_url);
      $assert_session->elementContains('css', '#block-token-url-test-block', $test['expected1']);

      // Go to the second node page and check that the block title has changed.
      $this->drupalGet($node2_url);
      $assert_session->elementContains('css', '#block-token-url-test-block', $test['expected2']);

      // Test the current page url on a 404 page.
      $this->drupalGet('does-not-exist');
      $assert_session->statusCodeEquals(404);
      $assert_session->elementContains('css', '#block-token-url-test-block', $test['expected3']);
    }


    // Can't do this test in the for loop above, it's too different.
    $block->getPlugin()->setConfigurationValue('label', 'prefix_[current-page:query:unicorns]_suffix');
    $block->save();

    // Test the parameter token.
    $this->drupalGet($node1_url->setOption('query', ['unicorns' => 'fluffy']));
    $this->assertCacheContext('url.query_args');
    $assert_session->elementContains('css', '#block-token-url-test-block', 'prefix_fluffy_suffix');

    // Change the parameter on the same page.
    $this->drupalGet($node1_url->setOption('query', ['unicorns' => 'dead']));
    $assert_session->elementContains('css', '#block-token-url-test-block', 'prefix_dead_suffix');
  }

}
