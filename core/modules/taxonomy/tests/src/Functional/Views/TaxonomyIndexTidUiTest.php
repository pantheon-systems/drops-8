<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\views_ui\Functional\UITestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Entity\View;

/**
 * Tests the taxonomy index filter handler UI.
 *
 * @group taxonomy
 * @see \Drupal\taxonomy\Plugin\views\field\TaxonomyIndexTid
 */
class TaxonomyIndexTidUiTest extends UITestBase {

  use EntityReferenceTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_taxonomy_index_tid', 'test_taxonomy_term_name'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'views',
    'views_ui',
    'taxonomy_test_views',
  ];

  /**
   * A nested array of \Drupal\taxonomy\TermInterface objects.
   *
   * @var \Drupal\taxonomy\TermInterface[][]
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->adminUser = $this->drupalCreateUser([
      'administer taxonomy',
      'administer views',
    ]);
    $this->drupalLogin($this->adminUser);

    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // Setup a hierarchy which looks like this:
    // term 0.0
    // term 1.0
    // - term 1.1
    // term 2.0
    // - term 2.1
    // - term 2.2
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $this->terms[$i][$j] = $term = Term::create([
          'vid' => 'tags',
          'name' => "Term $i.$j",
          'parent' => isset($this->terms[$i][0]) ? $this->terms[$i][0]->id() : 0,
        ]);
        $term->save();
      }
    }
    ViewTestData::createTestViews(static::class, ['taxonomy_test_views']);

    Vocabulary::create([
      'vid' => 'empty_vocabulary',
      'name' => 'Empty Vocabulary',
    ])->save();
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUI() {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');

    $result = $this->assertSession()->selectExists('edit-options-value')->findAll('css', 'option');

    // Ensure that the expected hierarchy is available in the UI.
    $counter = 0;
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $option = $result[$counter++];
        $prefix = $this->terms[$i][$j]->parent->target_id ? '-' : '';
        $tid = $option->getAttribute('value');

        $this->assertEquals($prefix . $this->terms[$i][$j]->getName(), $option->getText());
        $this->assertEquals($this->terms[$i][$j]->id(), $tid);
      }
    }

    // Ensure the autocomplete input element appears when using the 'textfield'
    // type.
    $view = View::load('test_filter_taxonomy_index_tid');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['tid']['type'] = 'textfield';
    $view->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->assertSession()->fieldExists('edit-options-value');

    // Tests \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid::calculateDependencies().
    $expected = [
      'config' => [
        'taxonomy.vocabulary.tags',
      ],
      'content' => [
        'taxonomy_term:tags:' . Term::load(2)->uuid(),
      ],
      'module' => [
        'node',
        'taxonomy',
        'user',
      ],
    ];
    $this->assertSame($expected, $view->calculateDependencies()->getDependencies());
  }

  /**
   * Tests exposed taxonomy filters.
   */
  public function testExposedFilter() {
    $node_type = $this->drupalCreateContentType(['type' => 'page']);

    // Create the tag field itself.
    $field_name = 'taxonomy_tags';
    $this->createEntityReferenceField('node', $node_type->id(), $field_name, NULL, 'taxonomy_term');

    // Create 4 nodes: 1 without a term, 2 with the same term, and 1 with a
    // different term.
    $node1 = $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[1][0]->id()]],
    ]);
    $node3 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[1][0]->id()]],
    ]);
    $node4 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[2][0]->id()]],
    ]);

    // Only the nodes with the selected term should be shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertSession()->elementsCount('xpath', '//div[@class="view-content"]//a', 2);
    $this->assertSession()->elementsCount('xpath', "//div[@class='view-content']//a[@href='{$node2->toUrl()->toString()}']", 1);
    $this->assertSession()->elementsCount('xpath', "//div[@class='view-content']//a[@href='{$node3->toUrl()->toString()}']", 1);

    // Expose the filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm([], 'Expose filter');
    // Set the operator to 'empty' and remove the default term ID.
    $this->submitForm([
      'options[operator]' => 'empty',
      'options[value][]' => [],
    ], 'Apply');
    // Save the view.
    $this->submitForm([], 'Save');

    // After switching to 'empty' operator, the node without a term should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertSession()->elementsCount('xpath', '//div[@class="view-content"]//a', 1);
    $this->assertSession()->elementsCount('xpath', "//div[@class='view-content']//a[@href='{$node1->toUrl()->toString()}']", 1);

    // Set the operator to 'not empty'.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm(['options[operator]' => 'not empty'], 'Apply');
    // Save the view.
    $this->submitForm([], 'Save');

    // After switching to 'not empty' operator, all nodes with terms should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertSession()->elementsCount('xpath', '//div[@class="view-content"]//a', 3);
    $this->assertSession()->elementsCount('xpath', "//div[@class='view-content']//a[@href='{$node2->toUrl()->toString()}']", 1);
    $this->assertSession()->elementsCount('xpath', "//div[@class='view-content']//a[@href='{$node3->toUrl()->toString()}']", 1);
    $this->assertSession()->elementsCount('xpath', "//div[@class='view-content']//a[@href='{$node4->toUrl()->toString()}']", 1);

    // Select 'Term ID' as the field to be displayed.
    $edit = ['name[taxonomy_term_field_data.tid]' => TRUE];
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/field');
    $this->submitForm($edit, 'Add and configure fields');
    // Select 'Term' and 'Vocabulary' as filters.
    $edit = [
      'name[taxonomy_term_field_data.tid]' => TRUE,
      'name[taxonomy_term_field_data.vid]' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/filter');
    $this->submitForm($edit, 'Add and configure filter criteria');
    // Select 'Empty Vocabulary' and 'Autocomplete' from the list of options.
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_taxonomy_term_name/default/filter/tid');
    $this->submitForm([], 'Apply and continue');
    // Expose the filter.
    $edit = ['options[expose_button][checkbox][checkbox]' => TRUE];
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid');
    $this->submitForm($edit, 'Expose filter');
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid');
    $this->submitForm($edit, 'Apply');
    // Filter 'Taxonomy terms' belonging to 'Empty Vocabulary'.
    $edit = ['options[value][empty_vocabulary]' => TRUE];
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/vid');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_taxonomy_term_name/edit/default');
    $this->submitForm([], 'Save');
    $this->submitForm([], 'Update preview');
    $this->assertSession()->elementNotExists('xpath', "//div[@class='view-content']");
  }

  /**
   * Tests that an exposed taxonomy filter doesn't show unpublished terms.
   */
  public function testExposedUnpublishedFilterOptions() {
    $this->terms[1][0]->setUnpublished()->save();
    // Expose the filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm([], 'Expose filter');
    $edit = ['options[expose_button][checkbox][checkbox]' => TRUE];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    // Make sure the unpublished term is shown to the admin user.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
    $this->drupalLogout();
    $this->drupalGet('test-filter-taxonomy-index-tid');
    // Make sure the unpublished term isn't shown to the anonymous user.
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));

    // Tests that the term also isn't shown when not showing hierarchy.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'options[hierarchy]' => FALSE,
    ];
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
    $this->drupalLogout();
    $this->drupalGet('test-filter-taxonomy-index-tid');
    // Make sure the unpublished term isn't shown to the anonymous user.
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
  }

}
