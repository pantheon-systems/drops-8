<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Kernel\Handler\FilterEqualityTest.
 */

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\Equality handler.
 *
 * @group views
 */
class FilterEqualityTest extends ViewsKernelTestBase {

  public static $modules = array('system');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = array(
    'views_test_data_name' => 'name',
  );

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['id'] = 'equality';
    return $data;
  }

  function testEqual() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '=',
        'value' => 'Ringo',
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'Ringo',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testEqualGroupedExposed() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Name, Operator: =, Value: Ringo
    $filters['name']['group_info']['default_group'] = 1;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'Ringo',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  function testNotEqual() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '!=',
        'value' => 'Ringo',
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
      ),
      array(
        'name' => 'George',
      ),
      array(
        'name' => 'Paul',
      ),
      array(
        'name' => 'Meredith',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testEqualGroupedNotExposed() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Name, Operator: !=, Value: Ringo
    $filters['name']['group_info']['default_group'] = 2;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
      ),
      array(
        'name' => 'George',
      ),
      array(
        'name' => 'Paul',
      ),
      array(
        'name' => 'Meredith',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }


  protected function getGroupedExposedFilters() {
    $filters = array(
      'name' => array(
        'id' => 'name',
        'plugin_id' => 'equality',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'group' => 1,
        'exposed' => TRUE,
        'expose' => array(
          'operator' => 'name_op',
          'label' => 'name',
          'identifier' => 'name',
        ),
        'is_grouped' => TRUE,
        'group_info' => array(
          'label' => 'name',
          'identifier' => 'name',
          'default_group' => 'All',
          'group_items' => array(
            1 => array(
              'title' => 'Name is equal to Ringo',
              'operator' => '=',
              'value' => 'Ringo',
            ),
            2 => array(
              'title' => 'Name is not equal to Ringo',
              'operator' => '!=',
              'value' => 'Ringo',
            ),
          ),
        ),
      ),
    );
    return $filters;
  }

}
