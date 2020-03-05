<?php

namespace Drupal\Tests\token\Functional;

use Drupal\Core\Url;

/**
 * Tests url tokens.
 *
 * @group token
 */
class TokenURLTest extends TokenTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->saveAlias('/node/1', '/first-node');
  }

  function testURLTokens() {
    $url = new Url('entity.node.canonical', ['node' => 1]);
    $tokens = [
      'absolute' => $url->setAbsolute()->toString(),
      'relative' => $url->setAbsolute(FALSE)->toString(),
      'path' => '/first-node',
      'brief' => preg_replace(['!^https?://!', '!/$!'], '', $url->setAbsolute()->toString()),
      'args:value:0' => 'first-node',
      'args:value:1' => NULL,
      'args:value:N' => NULL,
      'unaliased' => $url->setAbsolute()->setOption('alias', TRUE)->toString(),
      'unaliased:relative' => $url->setAbsolute(FALSE)->setOption('alias', TRUE)->toString(),
      'unaliased:path' => '/node/1',
      'unaliased:brief' => preg_replace(['!^https?://!', '!/$!'], '', $url->setAbsolute()->setOption('alias', TRUE)->toString()),
      'unaliased:args:value:0' => 'node',
      'unaliased:args:value:1' => '1',
      'unaliased:args:value:2' => NULL,
      // Deprecated tokens.
      'alias' => '/first-node',
    ];
    $this->assertTokens('url', ['url' => new Url('entity.node.canonical', ['node' => 1])], $tokens);
  }
}
