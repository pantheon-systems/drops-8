<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\PagerKernelTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests pager-related APIs.
 *
 * @group views
 */
class PagerKernelTest extends ViewUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_pager_full'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Tests pager-related setter methods on ViewExecutable.
   *
   * @see \Drupal\views\ViewExecutable::setItemsPerPage
   * @see \Drupal\views\ViewExecutable::setOffset
   * @see \Drupal\views\ViewExecutable::setCurrentPage
   */
  public function testSetPagerMethods() {
    $view = Views::getView('test_pager_full');
    $output = $view->preview();

    \Drupal::service('renderer')->renderPlain($output);
    $this->assertIdentical(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age']);

    foreach (['setItemsPerPage', 'setOffset', 'setCurrentPage'] as $method) {
      // Without $keep_cacheablity.
      $view = Views::getView('test_pager_full');
      $view->setDisplay('default');
      $view->{$method}(1);
      $output = $view->preview();

      \Drupal::service('renderer')->renderPlain($output);
      $this->assertIdentical(0, $output['#cache']['max-age'], 'Max age set to 0 without $keep_cacheablity.');

      // With $keep_cacheablity.
      $view = Views::getView('test_pager_full');
      $view->setDisplay('default');
      $view->{$method}(1, TRUE);
      $output = $view->preview();

      \Drupal::service('renderer')->renderPlain($output);
      $this->assertIdentical(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age'], 'Max age unchanged with $keep_cacheablity.');
    }

  }

}
