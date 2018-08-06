<?php

namespace Drupal\pathauto\Tests;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\pathauto\PathautoState;
use Drupal\simpletest\WebTestBase;

/**
 * Test pathauto functionality with localization and translation.
 *
 * @group pathauto
 */
class PathautoLocaleTest extends WebTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'pathauto', 'locale', 'content_translation');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Article node type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Test that when an English node is updated, its old English alias is
   * updated and its newer French alias is left intact.
   */
  function testLanguageAliases() {

    $this->createPattern('node', '/content/[node:title]');

    // Add predefined French language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $node = array(
      'title' => 'English node',
      'langcode' => 'en',
      'path' => array(array(
        'alias' => '/english-node',
        'pathauto' => FALSE,
      )),
    );
    $node = $this->drupalCreateNode($node);
    $english_alias = \Drupal::service('path.alias_storage')->load(array('alias' => '/english-node', 'langcode' => 'en'));
    $this->assertTrue($english_alias, 'Alias created with proper language.');

    // Also save a French alias that should not be left alone, even though
    // it is the newer alias.
    $this->saveEntityAlias($node, '/french-node', 'fr');

    // Add an alias with the soon-to-be generated alias, causing the upcoming
    // alias update to generate a unique alias with the '-0' suffix.
    $this->saveAlias('/node/invalid', '/content/english-node', Language::LANGCODE_NOT_SPECIFIED);

    // Update the node, triggering a change in the English alias.
    $node->path->pathauto = PathautoState::CREATE;
    $node->save();

    // Check that the new English alias replaced the old one.
    $this->assertEntityAlias($node, '/content/english-node-0', 'en');
    $this->assertEntityAlias($node, '/french-node', 'fr');
    $this->assertAliasExists(array('pid' => $english_alias['pid'], 'alias' => '/content/english-node-0'));

    // Create a new node with the same title as before but without
    // specifying a language.
    $node = $this->drupalCreateNode(array('title' => 'English node', 'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED));

    // Check that the new node had a unique alias generated with the '-0'
    // suffix.
    $this->assertEntityAlias($node, '/content/english-node-0', LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * Test that patterns work on multilingual content.
   */
  function testLanguagePatterns() {
    $this->drupalLogin($this->rootUser);

    // Add French language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    $this->enableArticleTranslation();

    // Create a pattern for English articles.
    $this->drupalGet('admin/config/search/path/patterns/add');
    $edit = array(
      'type' => 'canonical_entities:node',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'type');
    $edit += array(
      'pattern' => '/the-articles/[node:title]',
      'label' => 'English articles',
      'id' => 'english_articles',
      'bundles[article]' => TRUE,
      'languages[en]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Pattern English articles saved.');

    // Create a pattern for French articles.
    $this->drupalGet('admin/config/search/path/patterns/add');
    $edit = array(
      'type' => 'canonical_entities:node',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'type');
    $edit += array(
      'pattern' => '/les-articles/[node:title]',
      'label' => 'French articles',
      'id' => 'french_articles',
      'bundles[article]' => TRUE,
      'languages[fr]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Pattern French articles saved.');

    // Create a node and its translation. Assert aliases.
    $edit = array(
      'title[0][value]' => 'English node',
      'langcode[0][value]' => 'en',
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    $english_node = $this->drupalGetNodeByTitle('English node');
    $this->assertAlias('/node/' . $english_node->id(), '/the-articles/english-node', 'en');

    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink(t('Add'));
    $edit = array(
      'title[0][value]' => 'French node',
    );
    $this->drupalPostForm(NULL, $edit, t('Save and keep published (this translation)'));
    $this->rebuildContainer();
    $english_node = $this->drupalGetNodeByTitle('English node');
    $french_node = $english_node->getTranslation('fr');
    $this->assertAlias('/node/' . $french_node->id(), '/les-articles/french-node', 'fr');

    // Bulk delete and Bulk generate patterns. Assert aliases.
    $this->deleteAllAliases();
    // Bulk create aliases.
    $edit = array(
      'update[canonical_entities:node]' => TRUE,
    );
    $this->drupalPostForm('admin/config/search/path/update_bulk', $edit, t('Update'));
    $this->assertText(t('Generated 2 URL aliases.'));
    $this->assertAlias('/node/' . $english_node->id(), '/the-articles/english-node', 'en');
    $this->assertAlias('/node/' . $french_node->id(), '/les-articles/french-node', 'fr');
  }

  /**
   * Tests the alias created for a node with language Not Applicable.
   */
  public function testLanguageNotApplicable() {
    $this->drupalLogin($this->rootUser);
    $this->enableArticleTranslation();

    // Create a pattern for nodes.
    $pattern = $this->createPattern('node', '/content/[node:title]', -1);
    $pattern->save();

    // Create a node with language Not Applicable.
    $node = $this->createNode(['type' => 'article', 'title' => 'Test node', 'langcode' => LanguageInterface::LANGCODE_NOT_APPLICABLE]);

    // Check that the generated alias has language Not Specified.
    $alias = \Drupal::service('pathauto.alias_storage_helper')->loadBySource('/node/' . $node->id());
    $this->assertEqual($alias['langcode'], LanguageInterface::LANGCODE_NOT_SPECIFIED, 'PathautoGenerator::createEntityAlias() adjusts the alias langcode from Not Applicable to Not Specified.');

    // Check that the alias works.
    $this->drupalGet('content/test-node');
    $this->assertResponse(200);
  }

  /**
   * Enables content translation on articles.
   */
  protected function enableArticleTranslation() {
    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = array(
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

}
