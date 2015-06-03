<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermTranslationUITest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the Term Translation UI.
 *
 * @group taxonomy
 */
class TermTranslationUITest extends ContentTranslationUITestBase {

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'taxonomy');

  protected function setUp() {
    $this->entityTypeId = 'taxonomy_term';
    $this->bundle = 'tags';
    parent::setUp();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITestBase::setupBundle().
   */
  protected function setupBundle() {
    parent::setupBundle();

    // Create a vocabulary.
    $this->vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->bundle,
      'description' => $this->randomMachineName(),
      'vid' => $this->bundle,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $this->vocabulary->save();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITestBase::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer taxonomy'));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITestBase::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    return array('name' => $this->randomMachineName()) + parent::getNewEntityValues($langcode);
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = parent::getEditValues($values, $langcode, $new);

    // To be able to post values for the configurable base fields (name,
    // description) have to be suffixed with [0][value].
    foreach ($edit as $property => $value) {
      foreach (array('name', 'description') as $key) {
        if ($property == $key) {
          $edit[$key . '[0][value]'] = $value;
          unset($edit[$property]);
        }
      }
    }
    return $edit;
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITestBase::testTranslationUI().
   */
  public function testTranslationUI() {
    parent::testTranslationUI();

    // Make sure that no row was inserted for taxonomy vocabularies which do
    // not have translations enabled.
    $rows = db_query('SELECT tid, count(tid) AS count FROM {taxonomy_term_field_data} WHERE vid <> :vid GROUP BY tid', array(':vid' => $this->bundle))->fetchAll();
    foreach ($rows as $row) {
      $this->assertTrue($row->count < 2, 'Term does not have translations.');
    }
  }

  /**
   * Tests translate link on vocabulary term list.
   */
  function testTranslateLinkVocabularyAdminPage() {
    $this->drupalLogin($this->drupalCreateUser(array_merge(parent::getTranslatorPermissions(), ['access administration pages', 'administer taxonomy'])));

    $values = array(
      'name' => $this->randomMachineName(),
    );
    $translatable_tid = $this->createEntity($values, $this->langcodes[0], $this->vocabulary->id());

    // Create an untranslatable vocabulary.
    $untranslatable_vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'untranslatable_voc',
      'description' => $this->randomMachineName(),
      'vid' => 'untranslatable_voc',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $untranslatable_vocabulary->save();

    $values = array(
      'name' => $this->randomMachineName(),
    );
    $untranslatable_tid = $this->createEntity($values, $this->langcodes[0], $untranslatable_vocabulary->id());

    // Verify translation links.
    $this->drupalGet('admin/structure/taxonomy/manage/' .  $this->vocabulary->id() . '/overview');
    $this->assertResponse(200, 'The translatable vocabulary page was found.');
    $this->assertLinkByHref('term/' . $translatable_tid . '/translations', 0, 'The translations link exists for a translatable vocabulary.');
    $this->assertLinkByHref('term/' . $translatable_tid . '/edit', 0, 'The edit link exists for a translatable vocabulary.');

    $this->drupalGet('admin/structure/taxonomy/manage/' . $untranslatable_vocabulary->id() . '/overview');
    $this->assertResponse(200);
    $this->assertLinkByHref('term/' . $untranslatable_tid . '/edit');
    $this->assertNoLinkByHref('term/' . $untranslatable_tid . '/translations');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = array('language' => $languages[$langcode]);
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $title = t('@title [%language translation]', array(
          '@title' => $entity->getTranslation($langcode)->label(),
          '%language' => $languages[$langcode]->getName(),
        ));
        $this->assertRaw($title);
      }
    }
  }

}
