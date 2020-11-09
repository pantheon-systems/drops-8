<?php

namespace Drupal\Tests\token\Kernel;

/**
 * Tests random tokens.
 *
 * @group token
 */
class RandomTest extends KernelTestBase {

  function testRandomTokens() {
    $tokens = [
      'number' => '[0-9]{1,}',
      'hash:md5' => '[0-9a-f]{32}',
      'hash:sha1' => '[0-9a-f]{40}',
      'hash:sha256' => '[0-9a-f]{64}',
      'hash:invalid-algo' => NULL,
    ];

    $first_set = $this->assertTokens('random', [], $tokens, ['regex' => TRUE]);
    $second_set = $this->assertTokens('random', [], $tokens, ['regex' => TRUE]);
    foreach ($first_set as $token => $value) {
      $this->assertNotSame($first_set[$token], $second_set[$token]);
    }
  }

}
