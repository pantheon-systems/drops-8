<?php

namespace Drupal\Tests\token\Kernel;

/**
 * Test basic, low-level token functions.
 *
 * @group token
 */
class UnitTest extends KernelTestBase {

  /**
   * @var \Drupal\token\Token
   */
  protected $tokenService;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->tokenService = \Drupal::token();
  }

  /**
   * Test invalid tokens.
   */
  public function testGetInvalidTokens() {
    $tests = [];
    $tests[] = [
      'valid tokens' => [
        '[node:title]',
        '[node:created:short]',
        '[node:created:custom:invalid]',
        '[node:created:custom:mm-YYYY]',
        '[node:colons:in:name]',
        '[site:name]',
        '[site:slogan]',
        '[current-date:short]',
        '[current-user:uid]',
        '[current-user:ip-address]',
      ],
      'invalid tokens' => [
        '[node:title:invalid]',
        '[node:created:invalid]',
        '[node:created:short:invalid]',
        '[node:colons:in:name:invalid]',
        '[invalid:title]',
        '[site:invalid]',
        '[user:ip-address]',
        '[user:uid]',
        '[comment:cid]',
        // Deprecated tokens
        '[node:tnid]',
        '[node:type]',
        '[node:type-name]',
        '[date:short]',
      ],
      'types' => ['node'],
    ];
    $tests[] = [
      'valid tokens' => [
        '[node:title]',
        '[node:created:short]',
        '[node:created:custom:invalid]',
        '[node:created:custom:mm-YYYY]',
        '[node:colons:in:name]',
        '[site:name]',
        '[site:slogan]',
        '[user:uid]',
        '[current-date:short]',
        '[current-user:uid]',
      ],
      'invalid tokens' => [
        '[node:title:invalid]',
        '[node:created:invalid]',
        '[node:created:short:invalid]',
        '[node:colons:in:name:invalid]',
        '[invalid:title]',
        '[site:invalid]',
        '[user:ip-address]',
        '[comment:cid]',
        // Deprecated tokens
        '[node:tnid]',
        '[node:type]',
        '[node:type-name]',
      ],
      'types' => ['all'],
    ];

    foreach ($tests as $test) {
      $tokens = array_merge($test['valid tokens'], $test['invalid tokens']);
      shuffle($tokens);

      $invalid_tokens = $this->tokenService->getInvalidTokensByContext(implode(' ', $tokens), $test['types']);

      sort($invalid_tokens);
      sort($test['invalid tokens']);
      $this->assertEquals($test['invalid tokens'], $invalid_tokens, 'Invalid tokens detected properly: ' . implode(', ', $invalid_tokens));
    }
  }

  /**
   * Test that tokens are generated only for content entities.
   */
  public function testContentEntityOnlyTokens() {
    // Verify that type and token info for a config entity is not generated.
    $this->assertNull($this->tokenService->getTokenInfo('user_role', 'original'));
    $this->assertNull($this->tokenService->getTokenInfo('user_role', 'url'));
    $this->assertNull($this->tokenService->getTypeInfo('user_role'));
  }

}
