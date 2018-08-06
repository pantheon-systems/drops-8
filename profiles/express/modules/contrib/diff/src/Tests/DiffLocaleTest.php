<?php

namespace Drupal\diff\Tests;

/**
 * Test diff functionality with localization and translation.
 *
 * @group diff
 */
class DiffLocaleTest extends DiffTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'locale',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Add French language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = array(
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

  /**
   * Run all independent tests.
   */
  public function testAll() {
    $this->doTestTranslationRevisions();
    $this->doTestUndefinedTranslationFilter();
    $this->doTestTranslationFilter();
  }

  /**
   * Test Diff functionality for the revisions of a translated node.
   */
  protected function doTestTranslationRevisions() {

    // Create an article and its translation. Assert aliases.
    $edit = array(
      'title[0][value]' => 'English node',
      'langcode[0][value]' => 'en',
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    $english_node = $this->drupalGetNodeByTitle('English node');

    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink(t('Add'));
    $edit = array(
      'title[0][value]' => 'French node',
      'revision' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save and keep published (this translation)'));
    $this->rebuildContainer();
    $english_node = $this->drupalGetNodeByTitle('English node');
    $french_node = $english_node->getTranslation('fr');

    // Create a new revision on both languages.
    $edit = array(
      'title[0][value]' => 'Updated title',
      'revision' => TRUE,
    );
    $this->drupalPostForm('node/' . $english_node->id() . '/edit', $edit, t('Save and keep published (this translation)'));
    $edit = array(
      'title[0][value]' => 'Le titre',
      'revision' => TRUE,
    );
    $this->drupalPostForm('fr/node/' . $english_node->id() . '/edit', $edit, t('Save and keep published (this translation)'));

    // View differences between revisions. Check that they don't mix up.
    $this->drupalGet('node/' . $english_node->id() . '/revisions');
    $this->drupalGet('node/' . $english_node->id() . '/revisions/view/1/2/split_fields');
    $this->assertText('Title');
    $this->assertText('English node');
    $this->assertText('Updated title');
    $this->drupalGet('fr/node/' . $english_node->id() . '/revisions');
    $this->drupalGet('fr/node/' . $english_node->id() . '/revisions/view/1/3/split_fields');
    $this->assertText('Title');
    $this->assertNoText('English node');
    $this->assertNoText('Updated title');
    $this->assertText('French node');
    $this->assertText('Le titre');
  }

  /**
   * Tests the translation filtering when navigating trough revisions.
   */
  protected function doTestTranslationFilter() {
    // Create a node in English.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'english_revision_0',
    ]);
    $revision1 = $node->getRevisionId();

    // Translate to french.
    $node->addTranslation('fr', ['title' => 'french_revision_0']);
    $node->save();

    // Create a revision in English.
    $english_node = $node->getTranslation('en');
    $english_node->setTitle('english_revision_1');
    $english_node->setNewRevision(TRUE);
    $english_node->save();
    $revision2 = $node->getRevisionId();

    // Create a revision in French.
    $french_node = $node->getTranslation('fr');
    $french_node->setTitle('french_revision_1');
    $french_node->setNewRevision(TRUE);
    $french_node->save();

    // Create a new revision in English.
    $english_node = $node->getTranslation('en');
    $english_node->setTitle('english_revision_2');
    $english_node->setNewRevision(TRUE);
    $english_node->save();

    // Create a new revision in French.
    $french_node = $node->getTranslation('fr');
    $french_node->setTitle('french_revision_2');
    $french_node->setNewRevision(TRUE);
    $french_node->save();

    // Compare first two revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions/view/' . $revision1 . '/' . $revision2 . '/split_fields');
    $diffs = $this->xpath('//span[@class="diffchange"]');
    $this->assertEqual($diffs[0], 'english_revision_0');
    $this->assertEqual($diffs[1], 'english_revision_1');

    // Check next difference.
    $this->clickLink('Next change');
    $diffs = $this->xpath('//span[@class="diffchange"]');
    $this->assertEqual($diffs[0], 'english_revision_1');
    $this->assertEqual($diffs[1], 'english_revision_2');

    // There shouldn't be other differences in the current language.
    $this->assertNoLink('Next change');
  }

  /**
   * Tests the undefined translation filtering when navigating trough revisions.
   */
  protected function doTestUndefinedTranslationFilter() {
    // Create a node in with undefined langcode.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'undefined_language_revision_0',
      'langcode' => 'und',
    ]);
    $revision1 = $node->getRevisionId();

    // Create 3 new revisions of the node.
    $node->setTitle('undefined_language_revision_1');
    $node->setNewRevision(TRUE);
    $node->save();
    $revision2 = $node->getRevisionId();

    $node->setTitle('undefined_language_revision_2');
    $node->setNewRevision(TRUE);
    $node->save();

    $node->setTitle('undefined_language_revision_3');
    $node->setNewRevision(TRUE);
    $node->save();

    // Check the amount of revisions displayed.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $element = $this->xpath('//*[@id="edit-node-revisions-table"]/tbody/tr');
    $this->assertEqual(count($element), 4);

    // Compare the first two revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions/view/' . $revision1 . '/' . $revision2 . '/split_fields');
    $diffs = $this->xpath('//span[@class="diffchange"]');
    $this->assertEqual($diffs[0], 'undefined_language_revision_0');
    $this->assertEqual($diffs[1], 'undefined_language_revision_1');

    // Compare the next two revisions.
    $this->clickLink('Next change');
    $diffs = $this->xpath('//span[@class="diffchange"]');
    $this->assertEqual($diffs[0], 'undefined_language_revision_1');
    $this->assertEqual($diffs[1], 'undefined_language_revision_2');
  }

}
