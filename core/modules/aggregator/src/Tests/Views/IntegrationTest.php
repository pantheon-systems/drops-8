<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Views\IntegrationTest.
 */

namespace Drupal\aggregator\Tests\Views;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests basic integration of views data from the aggregator module.
 *
 * @group aggregator
 */
class IntegrationTest extends ViewUnitTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('aggregator', 'aggregator_test_views', 'system', 'field', 'options', 'user');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_aggregator_items');

  /**
   * The entity storage for aggregator items.
   *
   * @var \Drupal\aggregator\ItemStorage
   */
  protected $itemStorage;

  /**
   * The entity storage for aggregator feeds.
   *
   * @var \Drupal\aggregator\FeedStorage
   */
  protected $feedStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('aggregator_item');
    $this->installEntitySchema('aggregator_feed');

    ViewTestData::createTestViews(get_class($this), array('aggregator_test_views'));

    $this->itemStorage = $this->container->get('entity.manager')->getStorage('aggregator_item');
    $this->feedStorage = $this->container->get('entity.manager')->getStorage('aggregator_feed');
  }

  /**
   * Tests basic aggregator_item view.
   */
  public function testAggregatorItemView() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $feed = $this->feedStorage->create(array(
      'title' => $this->randomMachineName(),
      'url' => 'https://www.drupal.org/',
      'refresh' => 900,
      'checked' => 123543535,
      'description' => $this->randomMachineName(),
    ));
    $feed->save();

    $items = array();
    $expected = array();
    for ($i = 0; $i < 10; $i++) {
      $values = array();
      $values['fid'] = $feed->id();
      $values['timestamp'] = mt_rand(REQUEST_TIME - 10, REQUEST_TIME + 10);
      $values['title'] = $this->randomMachineName();
      $values['description'] = $this->randomMachineName();
      // Add a image to ensure that the sanitizing can be tested below.
      $values['author'] = $this->randomMachineName() . '<img src="http://example.com/example.png" \>"';
      $values['link'] = 'https://www.drupal.org/node/' . mt_rand(1000, 10000);
      $values['guid'] = $this->randomString();

      $aggregator_item = $this->itemStorage->create($values);
      $aggregator_item->save();
      $items[$aggregator_item->id()] = $aggregator_item;

      $values['iid'] = $aggregator_item->id();
      $expected[] = $values;
    }

    $view = Views::getView('test_aggregator_items');
    $this->executeView($view);

    $column_map = array(
      'iid' => 'iid',
      'title' => 'title',
      'aggregator_item_timestamp' => 'timestamp',
      'description' => 'description',
      'aggregator_item_author' => 'author',
    );
    $this->assertIdenticalResultset($view, $expected, $column_map);

    // Ensure that the rendering of the linked title works as expected.
    foreach ($view->result as $row) {
      $iid = $view->field['iid']->getValue($row);
      $expected_link = \Drupal::l($items[$iid]->getTitle(), Url::fromUri($items[$iid]->getLink(), ['absolute' => TRUE]));
      $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $row) {
        return $view->field['title']->advancedRender($row);
      });
      $this->assertEqual($output, $expected_link, 'Ensure the right link is generated');

      $expected_author = aggregator_filter_xss($items[$iid]->getAuthor());
      $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $row) {
        return $view->field['author']->advancedRender($row);
      });
      $this->assertEqual($output, $expected_author, 'Ensure the author got filtered');

      $expected_description = aggregator_filter_xss($items[$iid]->getDescription());
      $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $row) {
        return $view->field['description']->advancedRender($row);
      });
      $this->assertEqual($output, $expected_description, 'Ensure the author got filtered');
    }
  }

}
