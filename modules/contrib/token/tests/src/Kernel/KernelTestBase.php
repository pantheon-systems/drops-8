<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\KernelTests\KernelTestBase as BaseKernelTestBase;
use Drupal\Tests\token\Functional\TokenTestTrait;

/**
 * Helper test class with some added functions for testing.
 */
abstract class KernelTestBase extends BaseKernelTestBase {

  use TokenTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['path', 'token', 'token_module_test', 'system', 'user', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('path_alias');
    \Drupal::service('router.builder')->rebuild();
    $this->installConfig(['system']);
  }

}
