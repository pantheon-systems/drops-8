<?php

namespace Drupal\webform\Tests;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform translation.
 *
 * @group Webform
 */
class WebformTranslationTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform', 'webform_test_translation'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Place blocks.
    $this->placeBlocks();

    // Create users.
    $this->createUsers();

    // Login admin user.
    $this->drupalLogin($this->adminWebformUser);
  }

  /**
   * Tests webform translate.
   */
  public function testTranslate() {
    /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
    $translation_manager = \Drupal::service('webform.translation_manager');

    $webform = Webform::load('test_translation');
    $elements_raw = \Drupal::config('webform.webform.test_translation')->get('elements');
    $elements = Yaml::decode($elements_raw);

    // Check translate tab.
    $this->drupalGet('admin/structure/webform/manage/test_translation');
    $this->assertRaw('>Translate<');

    // Check translations.
    $this->drupalGet('admin/structure/webform/manage/test_translation/translate');
    $this->assertRaw('<a href="' . base_path() . 'admin/structure/webform/manage/test_translation/translate/es/edit">Edit</a>');

    // Check Spanish translations.
    $this->drupalGet('admin/structure/webform/manage/test_translation/translate/es/edit');
    $this->assertFieldByName('translation[config_names][webform.webform.test_translation][title]', 'Prueba: Traducción');
    $this->assertField('translation[config_names][webform.webform.test_translation][elements]');

    // Check translated webform options.
    $this->drupalGet('es/webform/test_translation');
    $this->assertRaw('<label for="edit-textfield">Campo de texto</label>');
    $this->assertRaw('<option value="1">Uno</option>');
    $this->assertRaw('<option value="4">Las cuatro</option>');

    // Check that webform is not translated into French.
    $this->drupalGet('fr/webform/test_translation');
    $this->assertRaw('<label for="edit-textfield">Text field</label>');
    $this->assertRaw('<option value="1">One</option>');
    $this->assertRaw('<option value="4">Four</option>');

    // Check that French config elements returns the default languages elements.
    // Please note: This behavior might change.
    $translation_element = $translation_manager->getConfigElements($webform, 'fr', TRUE);
    $this->assertEqual($elements, $translation_element);

    // Create French translation.
    $translation_elements = [
      'textfield' => [
        '#title' => 'French',
        '#custom' => 'custom',
      ],
      'custom' => [
        '#title' => 'Custom',
      ],
    ] + $elements;
    $edit = [
      'translation[config_names][webform.webform.test_translation][elements]' => Yaml::encode($translation_elements),
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_translation/translate/fr/add', $edit, t('Save translation'));

    // Check French translation.
    $this->drupalGet('fr/webform/test_translation');
    $this->assertRaw('<label for="edit-textfield">French</label>');

    // Check French config elements only contains translated properties and
    // custom properties are removed.
    $translation_element = $translation_manager->getConfigElements($webform, 'fr', TRUE);
    $this->assertEqual(['textfield' => ['#title' => 'French']], $translation_element);

    /**************************************************************************/
    // Site wide language
    /**************************************************************************/

    // Make sure the site language is English (en).
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'en')->save();

    $language_manager = \Drupal::languageManager();

    $this->drupalGet('webform/test_translation', ['language' => $language_manager->getLanguage('en')]);
    $this->assertRaw('<label for="edit-textfield">Text field</label>');

    // Check Spanish translation.
    $this->drupalGet('webform/test_translation', ['language' => $language_manager->getLanguage('es')]);
    $this->assertRaw('<label for="edit-textfield">Campo de texto</label>');

    // Check French translation.
    $this->drupalGet('webform/test_translation', ['language' => $language_manager->getLanguage('fr')]);
    $this->assertRaw('<label for="edit-textfield">French</label>');

    // Change site language to French (fr).
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'fr')->save();

    // Check English translation.
    $this->drupalGet('webform/test_translation', ['language' => $language_manager->getLanguage('en')]);
    $this->assertRaw('<label for="edit-textfield">Text field</label>');

    // Check Spanish translation.
    $this->drupalGet('webform/test_translation', ['language' => $language_manager->getLanguage('es')]);
    $this->assertRaw('<label for="edit-textfield">Campo de texto</label>');

    // Check French translation.
    $this->drupalGet('webform/test_translation', ['language' => $language_manager->getLanguage('fr')]);
    $this->assertRaw('<label for="edit-textfield">French</label>');
  }

}
