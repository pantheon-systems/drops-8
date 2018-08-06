<?php

namespace Drupal\Tests\pathauto\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\pathauto\PathautoState;
use Drupal\pathauto\Tests\PathautoTestHelperTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Unit tests for Pathauto functions.
 *
 * @group pathauto
 */
class PathautoKernelTest extends KernelTestBase {

  use PathautoTestHelperTrait;

  public static $modules = array('system', 'field', 'text', 'user', 'node', 'path', 'pathauto', 'taxonomy', 'token', 'filter', 'ctools', 'language');

  protected $currentUser;

  /**
   * @var \Drupal\pathauto\PathautoPatternInterface
   */
  protected $nodePattern;

  /**
   * @var \Drupal\pathauto\PathautoPatternInterface
   */
  protected $userPattern;

  public function setUp() {
    parent::setup();

    $this->installConfig(array('pathauto', 'taxonomy', 'system', 'node'));

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');

    ConfigurableLanguage::createFromLangcode('fr')->save();

    $this->installSchema('node', array('node_access'));
    $this->installSchema('system', array('url_alias', 'sequences', 'router'));

    $type = NodeType::create(['type' => 'page']);
    $type->save();
    node_add_body_field($type);

    $this->nodePattern = $this->createPattern('node', '/content/[node:title]');
    $this->userPattern = $this->createPattern('user', '/users/[user:name]');

    \Drupal::service('router.builder')->rebuild();

    $this->currentUser = User::create(array('name' => $this->randomMachineName()));
    $this->currentUser->save();
  }

  /**
   * Test _pathauto_get_schema_alias_maxlength().
   */
  public function testGetSchemaAliasMaxLength() {
    $this->assertIdentical(\Drupal::service('pathauto.alias_storage_helper')->getAliasSchemaMaxlength(), 255);
  }

  /**
   * Test pathauto_pattern_load_by_entity().
   */
  public function testPatternLoadByEntity() {
    $pattern = $this->createPattern('node', '/article/[node:title]', -1);
    $this->addBundleCondition($pattern, 'node', 'article');
    $pattern->save();

    $pattern = $this->createPattern('node', '/article/en/[node:title]', -2);
    $this->addBundleCondition($pattern, 'node', 'article');
    $pattern->addSelectionCondition(
      [
        'id' => 'language',
        'langcodes' => [
          'en' => 'en',
        ],
        'negate' => FALSE,
        'context_mapping' => [
          'language' => 'node:langcode:language',
        ]
      ]
    );

    $pattern->addRelationship('node:langcode:language');
    $pattern->save();

    $pattern = $this->createPattern('node', '/[node:title]', -1);
    $this->addBundleCondition($pattern, 'node', 'page');
    $pattern->save();

    $tests = array(
      array(
        'entity' => 'node',
        'values' => [
          'title' => 'Article fr',
          'type' => 'article',
          'langcode' => 'fr',
        ],
        'expected' => '/article/[node:title]',
      ),
      array(
        'entity' => 'node',
        'values' => [
          'title' => 'Article en',
          'type' => 'article',
          'langcode' => 'en',
        ],
        'expected' => '/article/en/[node:title]',
      ),
      array(
        'entity' => 'node',
        'values' => [
          'title' => 'Article und',
          'type' => 'article',
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ],
        'expected' => '/article/[node:title]',
      ),
      array(
        'entity' => 'node',
        'values' => [
          'title' => 'Page',
          'type' => 'page',
        ],
        'expected' => '/[node:title]',
      ),
      array(
        'entity' => 'user',
        'values' => [
          'name' => 'User',
        ],
        'expected' => '/users/[user:name]',
      ),
    );
    foreach ($tests as $test) {
      $entity = \Drupal::entityTypeManager()->getStorage($test['entity'])->create($test['values']);
      $entity->save();
      $actual = \Drupal::service('pathauto.generator')->getPatternByEntity($entity);
      $this->assertIdentical($actual->getPattern(), $test['expected'], t("Correct pattern returned for @entity_type with @values", array(
        '@entity' => $test['entity'],
        '@values' => print_r($test['values'], TRUE),
      )));
    }
  }

  /**
   * Test potential conflicts with the same alias in different languages.
   */
  public function testSameTitleDifferentLanguages() {
    // Create two English articles with the same title.
    $edit = [
      'title' => 'Sample page',
      'type' => 'page',
      'langcode' => 'en',
    ];
    $node1 = $this->drupalCreateNode($edit);
    $this->assertEntityAlias($node1, '/content/sample-page', 'en');

    $node2 = $this->drupalCreateNode($edit);
    $this->assertEntityAlias($node2, '/content/sample-page-0', 'en');

    // Now, create a French article with the same title, and verify that it gets
    // the basic alias with the correct langcode.
    $edit['langcode'] = 'fr';
    $node3 = $this->drupalCreateNode($edit);
    $this->assertEntityAlias($node3, '/content/sample-page', 'fr');
  }

  /**
   * Test pathauto_cleanstring().
   */
  public function testCleanString() {

    // Test with default settings defined in pathauto.settings.yml.
    $this->installConfig(array('pathauto'));
    \Drupal::service('pathauto.generator')->resetCaches();

    $tests = array();

    // Test the 'ignored words' removal.
    $tests['this'] = 'this';
    $tests['this with that'] = 'this-with-that';
    $tests['this thing with that thing'] = 'thing-thing';

    // Test 'ignored words' removal and duplicate separator removal.
    $tests[' - Pathauto is the greatest - module ever - '] = 'pathauto-greatest-module-ever';

    // Test length truncation and lowering of strings.
    $long_string = $this->randomMachineName(120);
    $tests[$long_string] = strtolower(substr($long_string, 0, 100));

    // Test that HTML tags are removed.
    $tests['This <span class="text">text</span> has <br /><a href="http://example.com"><strong>HTML tags</strong></a>.'] = 'text-has-html-tags';
    $tests[Html::escape('This <span class="text">text</span> has <br /><a href="http://example.com"><strong>HTML tags</strong></a>.')] = 'text-has-html-tags';

    // Transliteration.
    $tests['ľščťžýáíéňô'] = 'lsctzyaieno';

    foreach ($tests as $input => $expected) {
      $output = \Drupal::service('pathauto.alias_cleaner')->cleanString($input);
      $this->assertEqual($output, $expected, t("Drupal::service('pathauto.alias_cleaner')->cleanString('@input') expected '@expected', actual '@output'", array(
        '@input' => $input,
        '@expected' => $expected,
        '@output' => $output,
      )));
    }
  }

  /**
   * Test pathauto_clean_alias().
   */
  public function testCleanAlias() {
    $tests = array();
    $tests['one/two/three'] = '/one/two/three';
    $tests['/one/two/three/'] = '/one/two/three';
    $tests['one//two///three'] = '/one/two/three';
    $tests['one/two--three/-/--/-/--/four---five'] = '/one/two-three/four-five';
    $tests['one/-//three--/four'] = '/one/three/four';

    foreach ($tests as $input => $expected) {
      $output = \Drupal::service('pathauto.alias_cleaner')->cleanAlias($input);
      $this->assertEqual($output, $expected, t("Drupal::service('pathauto.generator')->cleanAlias('@input') expected '@expected', actual '@output'", array(
        '@input' => $input,
        '@expected' => $expected,
        '@output' => $output,
      )));
    }
  }

  /**
   * Test pathauto_path_delete_multiple().
   */
  public function testPathDeleteMultiple() {
    $this->saveAlias('/node/1', '/node-1-alias');
    $this->saveAlias('/node/1/view', '/node-1-alias/view');
    $this->saveAlias('/node/1', '/node-1-alias-en', 'en');
    $this->saveAlias('/node/1', '/node-1-alias-fr', 'fr');
    $this->saveAlias('/node/2', '/node-2-alias');
    $this->saveAlias('/node/10', '/node-10-alias');

    \Drupal::service('pathauto.alias_storage_helper')->deleteBySourcePrefix('/node/1');
    $this->assertNoAliasExists(array('source' => "/node/1"));
    $this->assertNoAliasExists(array('source' => "/node/1/view"));
    $this->assertAliasExists(array('source' => "/node/2"));
    $this->assertAliasExists(array('source' => "/node/10"));
  }

  /**
   * Test the different update actions in \Drupal::service('pathauto.generator')->createEntityAlias().
   */
  public function testUpdateActions() {
    $config = $this->config('pathauto.settings');

    // Test PATHAUTO_UPDATE_ACTION_NO_NEW with unaliased node and 'insert'.
    $config->set('update_action', PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW);
    $config->save();
    $node = $this->drupalCreateNode(array('title' => 'First title'));
    $this->assertEntityAlias($node, '/content/first-title');

    $node->path->pathauto = PathautoState::CREATE;

    // Default action is PATHAUTO_UPDATE_ACTION_DELETE.
    $config->set('update_action', PathautoGeneratorInterface::UPDATE_ACTION_DELETE);
    $config->save();
    $node->setTitle('Second title');
    $node->save();
    $this->assertEntityAlias($node, '/content/second-title');
    $this->assertNoAliasExists(array('alias' => '/content/first-title'));

    // Test PATHAUTO_UPDATE_ACTION_LEAVE
    $config->set('update_action', PathautoGeneratorInterface::UPDATE_ACTION_LEAVE);
    $config->save();
    $node->setTitle('Third title');
    $node->save();
    $this->assertEntityAlias($node, '/content/third-title');
    $this->assertAliasExists(array('source' => '/' . $node->toUrl()->getInternalPath(), 'alias' => '/content/second-title'));

    $config->set('update_action', PathautoGeneratorInterface::UPDATE_ACTION_DELETE);
    $config->save();
    $node->setTitle('Fourth title');
    $node->save();
    $this->assertEntityAlias($node, '/content/fourth-title');
    $this->assertNoAliasExists(array('alias' => '/content/third-title'));
    // The older second alias is not deleted yet.
    $older_path = $this->assertAliasExists(array('source' => '/' . $node->toUrl()->getInternalPath(), 'alias' => '/content/second-title'));
    \Drupal::service('path.alias_storage')->delete($older_path);

    $config->set('update_action', PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW);
    $config->save();
    $node->setTitle('Fifth title');
    $node->save();
    $this->assertEntityAlias($node, '/content/fourth-title');
    $this->assertNoAliasExists(array('alias' => '/content/fifth-title'));

    // Test PATHAUTO_UPDATE_ACTION_NO_NEW with unaliased node and 'update'.
    $this->deleteAllAliases();
    $node->save();
    $this->assertEntityAlias($node, '/content/fifth-title');

    // Test PATHAUTO_UPDATE_ACTION_NO_NEW with unaliased node and 'bulkupdate'.
    $this->deleteAllAliases();
    $node->setTitle('Sixth title');
    \Drupal::service('pathauto.generator')->updateEntityAlias($node, 'bulkupdate');
    $this->assertEntityAlias($node, '/content/sixth-title');
  }

  /**
   * Test that \Drupal::service('pathauto.generator')->createEntityAlias() will not create an alias for a pattern
   * that does not get any tokens replaced.
   */
  public function testNoTokensNoAlias() {
    $this->installConfig(['filter']);
    $this->nodePattern
      ->setPattern('/content/[node:body]')
      ->save();

    $node = $this->drupalCreateNode();
    $this->assertNoEntityAliasExists($node);

    $node->body->value = 'hello';
    $node->save();
    $this->assertEntityAlias($node, '/content/hello');
  }

  /**
   * Test the handling of path vs non-path tokens in pathauto_clean_token_values().
   */
  public function testPathTokens() {
    $this->createPattern('taxonomy_term', '/[term:parent:url:path]/[term:name]');

    $vocab = $this->addVocabulary();

    $term1 = $this->addTerm($vocab, array('name' => 'Parent term'));
    $this->assertEntityAlias($term1, '/parent-term');

    $term2 = $this->addTerm($vocab, array('name' => 'Child term', 'parent' => $term1->id()));
    $this->assertEntityAlias($term2, '/parent-term/child-term');

    $this->saveEntityAlias($term1, '/My Crazy/Alias/');
    $term2->save();
    $this->assertEntityAlias($term2, '/My Crazy/Alias/child-term');
  }

  /**
   * Test using fields for path structures.
   */
  function testParentChildPathTokens() {
    // First create a field which will be used to create the path. It must
    // begin with a letter.

    $this->installEntitySchema('taxonomy_term');

    Vocabulary::create(['vid' => 'tags'])->save();

    $fieldname = 'a' . Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(['entity_type' => 'taxonomy_term', 'field_name' => $fieldname, 'type' => 'string']);
    $field_storage->save();
    $field = FieldConfig::create(['field_storage' => $field_storage, 'bundle' => 'tags']);
    $field->save();

    $display = entity_get_display('taxonomy_term', 'tags', 'default');
    $display->setComponent($fieldname, ['type' => 'string']);
    $display->save();

    // Make the path pattern of a field use the value of this field appended
    // to the parent taxonomy term's pattern if there is one.
    $this->createPattern('taxonomy_term', '/[term:parents:join-path]/[term:' . $fieldname . ']');

    // Start by creating a parent term.
    $parent = Term::create(['vid' => 'tags', $fieldname => $this->randomMachineName(), 'name' => $this->randomMachineName()]);
    $parent->save();

    // Create the child term.
    $child = Term::create(['vid' => 'tags', $fieldname => $this->randomMachineName(), 'parent' => $parent, 'name' => $this->randomMachineName()]);
    $child->save();
    $this->assertEntityAlias($child, '/' . Unicode::strtolower($parent->getName() . '/' . $child->$fieldname->value));

    // Re-saving the parent term should not modify the child term's alias.
    $parent->save();
    $this->assertEntityAlias($child, '/' . Unicode::strtolower($parent->getName() . '/' . $child->$fieldname->value));
  }

  /**
   * Tests aliases on taxonomy terms.
   */
  public function testTaxonomyPattern() {
    // Create a vocabulary and test that it's pattern variable works.
    $vocab = $this->addVocabulary(array('vid' => 'name'));
    $this->createPattern('taxonomy_term', 'base');
    $pattern = $this->createPattern('taxonomy_term', 'bundle', -1);
    $this->addBundleCondition($pattern, 'taxonomy_term', 'name');
    $pattern->save();
    $this->assertEntityPattern('taxonomy_term', 'name', Language::LANGCODE_NOT_SPECIFIED, 'bundle');
  }

  function testNoExistingPathAliases() {
    $this->config('pathauto.settings')
      ->set('punctuation.period', PathautoGeneratorInterface::PUNCTUATION_DO_NOTHING)
      ->save();

    $this->nodePattern
      ->setPattern('[node:title]')
      ->save();

    // Check that Pathauto does not create an alias of '/admin'.
    $node = $this->drupalCreateNode(array('title' => 'Admin', 'type' => 'page'));
    $this->assertEntityAlias($node, '/admin-0');

    // Check that Pathauto does not create an alias of '/modules'.
    $node->setTitle('Modules');
    $node->save();
    $this->assertEntityAlias($node, '/modules-0');

    // Check that Pathauto does not create an alias of '/index.php'.
    $node->setTitle('index.php');
    $node->save();
    $this->assertEntityAlias($node, '/index.php-0');

    // Check that a safe value gets an automatic alias. This is also a control
    // to ensure the above tests work properly.
    $node->setTitle('Safe value');
    $node->save();
    $this->assertEntityAlias($node, '/safe-value');
  }

  /**
   * Test programmatic entity creation for aliases.
   */
  function testProgrammaticEntityCreation() {
    $this->createPattern('taxonomy_term', '/[term:vocabulary]/[term:name]');
    $node = $this->drupalCreateNode(array('title' => 'Test node', 'path' => array('pathauto' => TRUE)));
    $this->assertEntityAlias($node, '/content/test-node');

    $vocabulary = $this->addVocabulary(array('name' => 'Tags'));
    $term = $this->addTerm($vocabulary, array('name' => 'Test term', 'path' => array('pathauto' => TRUE)));
    $this->assertEntityAlias($term, '/tags/test-term');

    $edit['name'] = 'Test user';
    $edit['mail'] = 'test-user@example.com';
    $edit['pass']   = user_password();
    $edit['path'] = array('pathauto' => TRUE);
    $edit['status'] = 1;
    $account = User::create($edit);
    $account->save();
    $this->assertEntityAlias($account, '/users/test-user');
  }

  /**
   * Tests word safe alias truncating.
   */
  function testPathAliasUniquifyWordsafe() {
    $this->config('pathauto.settings')
      ->set('max_length', 26)
      ->save();

    $node_1 = $this->drupalCreateNode(array('title' => 'thequick brownfox jumpedover thelazydog', 'type' => 'page'));
    $node_2 = $this->drupalCreateNode(array('title' => 'thequick brownfox jumpedover thelazydog', 'type' => 'page'));

    // Check that alias uniquifying is truncating with $wordsafe param set to
    // TRUE.
    // If it doesn't path alias result would be content/thequick-brownf-0
    $this->assertEntityAlias($node_1, '/content/thequick-brownfox');
    $this->assertEntityAlias($node_2, '/content/thequick-0');
  }

  /**
   * Test if aliases are (not) generated with enabled/disabled patterns.
   */
  function testPatternStatus() {
    // Create a node to get an alias for.
    $title = 'Pattern enabled';
    $alias = '/content/pattern-enabled';
    $node1 = $this->drupalCreateNode(['title' => $title, 'type' => 'page']);
    $this->assertEntityAlias($node1, $alias);

    // Disable the pattern, save the node again and make sure the alias is still
    // working.
    $this->nodePattern->setStatus(FALSE)->save();

    $node1->save();
    $this->assertEntityAlias($node1, $alias);

    // Create a new node with disabled pattern and make sure there is no new
    // alias created.
    $title = 'Pattern disabled';
    $node2 = $this->drupalCreateNode(['title' => $title, 'type' => 'page']);
    $this->assertNoEntityAlias($node2);
  }

  /**
   * Tests that enabled entity types genrates the necessary fields and plugins.
   */
  public function testSettingChangeInvalidatesCache() {

    $this->installConfig(['pathauto']);

    $this->enableModules(['entity_test']);

    $definitions = \Drupal::service('plugin.manager.alias_type')->getDefinitions();
    $this->assertFalse(isset($definitions['canonical_entities:entity_test']));

    $fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('entity_test');
    $this->assertFalse(isset($fields['path']));

    $this->config('pathauto.settings')
      ->set('enabled_entity_types', ['user', 'entity_test'])
      ->save();

    $definitions = \Drupal::service('plugin.manager.alias_type')->getDefinitions();
    $this->assertTrue(isset($definitions['canonical_entities:entity_test']));

    $fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('entity_test');
    $this->assertTrue(isset($fields['path']));

  }

  /**
   * Tests that aliases are only generated for default revisions.
   */
  public function testDefaultRevision() {
    $node1 = $this->drupalCreateNode(['title' => 'Default revision', 'type' => 'page']);
    $this->assertEntityAlias($node1, '/content/default-revision');

    $node1->setNewRevision(TRUE);
    $node1->isDefaultRevision(FALSE);
    $node1->setTitle('New non-default-revision');
    $node1->save();

    $this->assertEntityAlias($node1, '/content/default-revision');
  }

  /**
   * Creates a node programmatically.
   *
   * @param array $settings
   *   The array of values for the node.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   */
  protected function drupalCreateNode(array $settings = array()) {
    // Populate defaults array.
    $settings += array(
      'title'     => $this->randomMachineName(8),
      'type'      => 'page',
    );

    $node = Node::create($settings);
    $node->save();

    return $node;
  }

}
