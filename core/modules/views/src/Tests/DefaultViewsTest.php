<?php

/**
 * @file
 * Contains \Drupal\views\Tests\DefaultViewsTest.
 */

namespace Drupal\views\Tests;

use Drupal\comment\CommentInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\views\Views;

/**
 * Tests the default views provided by views.
 *
 * @group views
 */
class DefaultViewsTest extends ViewTestBase {

  use CommentTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'node', 'search', 'comment', 'taxonomy', 'block', 'user');

  /**
   * An array of argument arrays to use for default views.
   *
   * @var array
   */
  protected $viewArgMap = array(
    'backlinks' => array(1),
    'taxonomy_term' => array(1),
    'glossary' => array('all'),
  );

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
      'nodes' => array('page' => 'page'),
      'weight' => mt_rand(0, 10),
    ));
    $vocabulary->save();

    // Create a field.
    $field_name = Unicode::strtolower($this->randomMachineName());

    $handler_settings = array(
      'target_bundles' => array(
        $vocabulary->id() => $vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('node', 'page', $field_name, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Create a time in the past for the archive.
    $time = REQUEST_TIME - 3600;

    $this->addDefaultCommentField('node', 'page');

    for ($i = 0; $i <= 10; $i++) {
      $user = $this->drupalCreateUser();
      $term = $this->createTerm($vocabulary);

      $values = array('created' => $time, 'type' => 'page');
      $values[$field_name][]['target_id'] = $term->id();

      // Make every other node promoted.
      if ($i % 2) {
        $values['promote'] = TRUE;
      }
      $values['body'][]['value'] = \Drupal::l('Node ' . 1, new Url('entity.node.canonical', ['node' => 1]));

      $node = $this->drupalCreateNode($values);

      $comment = array(
        'uid' => $user->id(),
        'status' => CommentInterface::PUBLISHED,
        'entity_id' => $node->id(),
        'entity_type' => 'node',
        'field_name' => 'comment'
      );
      entity_create('comment', $comment)->save();
    }

    // Some views, such as the "Who's Online" view, only return results if at
    // least one user is logged in.
    $account = $this->drupalCreateUser(array());
    $this->drupalLogin($account);
  }

  /**
   * Test that all Default views work as expected.
   */
  public function testDefaultViews() {
    // Get all default views.
    $controller = $this->container->get('entity.manager')->getStorage('view');
    $views = $controller->loadMultiple();

    foreach ($views as $name => $view_storage) {
      $view = $view_storage->getExecutable();
      $view->initDisplay();
      foreach ($view->storage->get('display') as $display_id => $display) {
        $view->setDisplay($display_id);

        // Add any args if needed.
        if (array_key_exists($name, $this->viewArgMap)) {
          $view->preExecute($this->viewArgMap[$name]);
        }

        $this->assert(TRUE, format_string('View @view will be executed.', array('@view' => $view->storage->id())));
        $view->execute();

        $tokens = array('@name' => $name, '@display_id' => $display_id);
        $this->assertTrue($view->executed, format_string('@name:@display_id has been executed.', $tokens));

        $count = count($view->result);
        $this->assertTrue($count > 0, format_string('@count results returned', array('@count' => $count)));
        $view->destroy();
      }
    }
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  function createTerm($vocabulary) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      // Use the first available text format.
      'format' => $format->id(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    return $term;
  }

  /**
   * Tests the archive view.
   */
  public function testArchiveView() {
    // Create additional nodes compared to the one in the setup method.
    // Create two nodes in the same month, and one in each following month.
    $node = array(
      'created' => 280299600, // Sun, 19 Nov 1978 05:00:00 GMT
    );
    $this->drupalCreateNode($node);
    $this->drupalCreateNode($node);
    $node = array(
      'created' => 282891600, // Tue, 19 Dec 1978 05:00:00 GMT
    );
    $this->drupalCreateNode($node);
    $node = array(
      'created' => 285570000, // Fri, 19 Jan 1979 05:00:00 GMT
    );
    $this->drupalCreateNode($node);

    $view = Views::getView('archive');
    $view->setDisplay('page_1');
    $this->executeView($view);
    $columns = array('nid', 'created_year_month', 'num_records');
    $column_map = array_combine($columns, $columns);
    // Create time of additional nodes created in the setup method.
    $created_year_month = date('Ym', REQUEST_TIME - 3600);
    $expected_result = array(
      array(
        'nid' => 1,
        'created_year_month' => $created_year_month,
        'num_records' => 11,
      ),
      array(
        'nid' => 15,
        'created_year_month' => 197901,
        'num_records' => 1,
      ),
      array(
        'nid' => 14,
        'created_year_month' => 197812,
        'num_records' => 1,
      ),
      array(
        'nid' => 12,
        'created_year_month' => 197811,
        'num_records' => 2,
      ),
    );
    $this->assertIdenticalResultset($view, $expected_result, $column_map);

    $view->storage->setStatus(TRUE);
    $view->save();
    \Drupal::service('router.builder')->rebuild();

    $this->drupalGet('archive');
    $this->assertResponse(200);
  }

}
