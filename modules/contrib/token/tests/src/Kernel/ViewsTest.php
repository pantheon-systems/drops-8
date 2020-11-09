<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Test the views tokens.
 *
 * @group token
 */
class ViewsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'block'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['token_views_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    ViewTestData::createTestViews(get_class($this), ['token_module_test']);
  }

  /**
   * Tests path token replacements generated from a view without a path.
   */
  public function testTokenReplacementNoPath() {
    $token_handler = \Drupal::token();
    $view = Views::getView('token_views_test');
    $view->setDisplay('block_1');
    $view->execute();

    $this->assertSame('', $token_handler->replace('[view:url]', ['view' => $view]), 'Token [view:url] is empty for views without path.');
  }

}
