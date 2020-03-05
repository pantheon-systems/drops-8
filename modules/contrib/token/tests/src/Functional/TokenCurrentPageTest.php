<?php

namespace Drupal\Tests\token\Functional;

use Drupal\Core\Url;

/**
 * Test the [current-page:*] tokens.
 *
 * @group token
 */
class TokenCurrentPageTest extends TokenTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  function testCurrentPageTokens() {
    // Cache clear is necessary because the frontpage was already cached by an
    // initial request.
    $this->rebuildAll();
    $tokens = [
      '[current-page:title]' => t('Log in'),
      '[current-page:url]' => Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString(),
      '[current-page:url:absolute]' => Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString(),
      '[current-page:url:relative]' => Url::fromRoute('user.login')->toString(),
      '[current-page:url:path]' => '/user/login',
      '[current-page:url:args:value:0]' => 'user',
      '[current-page:url:args:value:1]' => 'login',
      '[current-page:url:args:value:2]' => NULL,
      '[current-page:url:unaliased]' => Url::fromRoute('user.login', [], ['absolute' => TRUE, 'alias' => TRUE])->toString(),
      '[current-page:page-number]' => 1,
      '[current-page:query:foo]' => NULL,
      '[current-page:query:bar]' => NULL,
      // Deprecated tokens
      '[current-page:arg:0]' => 'user',
      '[current-page:arg:1]' => 'login',
      '[current-page:arg:2]' => NULL,
    ];
    $this->assertPageTokens('user/login', $tokens);

    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode(['title' => 'Node title', 'path' => ['alias' => '/node-alias']]);
    $tokens = [
      '[current-page:title]' => 'Node title',
      '[current-page:url]' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      '[current-page:url:absolute]' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      '[current-page:url:relative]' => $node->toUrl()->toString(),
      '[current-page:url:alias]' => '/node-alias',
      '[current-page:url:args:value:0]' => 'node-alias',
      '[current-page:url:args:value:1]' => NULL,
      '[current-page:url:unaliased]' => $node->toUrl('canonical', ['absolute' => TRUE, 'alias' => TRUE])->toString(),
      '[current-page:url:unaliased:args:value:0]' => 'node',
      '[current-page:url:unaliased:args:value:1]' => $node->id(),
      '[current-page:url:unaliased:args:value:2]' => NULL,
      '[current-page:page-number]' => 1,
      '[current-page:query:foo]' => 'bar',
      '[current-page:query:bar]' => NULL,
      // Deprecated tokens
      '[current-page:arg:0]' => 'node',
      '[current-page:arg:1]' => 1,
      '[current-page:arg:2]' => NULL,
    ];
    $this->assertPageTokens("/node/{$node->id()}", $tokens, [], ['url_options' => ['query' => ['foo' => 'bar']]]);
  }
}
