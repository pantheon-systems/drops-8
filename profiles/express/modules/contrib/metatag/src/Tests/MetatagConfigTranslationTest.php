<?php

namespace Drupal\metatag\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that the Metatag config translations work correctly.
 *
 * @group metatag
 */
class MetatagConfigTranslationTest extends WebTestBase {

  /**
   * Profile to use.
   */
  protected $profile = 'testing';

  /**
   * Admin user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'metatag',
    'language',
    'config_translation',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    // From Metatag.
    'administer meta tags',

    // From system module, in order to access the /admin pages.
    'access administration pages',

    // From language module.
    'administer languages',

    // From config_translations module.
    'translate configuration',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);

    // Enable the French language.
    $this->drupalGet('admin/config/regional/language/add');
    $this->assertResponse(200);
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add language'));
    $this->assertRaw(t(
      'The language %language has been created and can now be used.',
      ['%language' => t('French')]
    ));
  }

  /**
   * Confirm the config defaults show on the translations page.
   */
  public function testConfigTranslationsExist() {
    // Ensure the config shows on the admin form.
    $this->drupalGet('admin/config/regional/config-translation');
    $this->assertResponse(200);
    $this->assertText(t('Metatag defaults'));

    // Load the main metatag_defaults config translation page.
    $this->drupalGet('admin/config/regional/config-translation/metatag_defaults');
    $this->assertResponse(200);
    // @todo Update this to confirm the H1 is loaded.
    $this->assertRaw(t('Metatag defaults'));

    // Load all of the Metatag defaults.
    $defaults = \Drupal::configFactory()->listAll('metatag.metatag_defaults');

    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');

    // Confirm each of the configs is available on the translation form.
    foreach ($defaults as $config_name) {
      if ($config_entity = $config_manager->loadConfigEntityByName($config_name)) {
        $this->assertText($config_entity->label());
      }
    }

    // Confirm that each config translation page can be loaded.
    foreach ($defaults as $config_name) {
      if ($config_entity = $config_manager->loadConfigEntityByName($config_name)) {
        $this->drupalGet('admin/config/search/metatag/' . $config_entity->id() . '/translate');
        $this->assertResponse(200);
      }
      else {
        $this->error('Unable to load a Metatag default config: ' . $config_name);
      }
    }
  }

  /**
   * Confirm the global configs are translatable page.
   */
  public function testConfigTranslations() {
    // Add something to the Global config.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $edit = [
      'title' => 'Test title',
      'description' => 'Test description',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText(t('Saved the Global Metatag defaults.'));

    // Confirm the config has languages available to translate into.
    $this->drupalGet('admin/config/search/metatag/global/translate');
    $this->assertResponse(200);

    // Load the translation form.
    $this->drupalGet('admin/config/search/metatag/global/translate/fr/add');
    $this->assertResponse(200);

    // Confirm the meta tag fields are shown on the form. Confirm the fields and
    // values separately to make it easier to pinpoint where the problem is if
    // one should fail.
    $this->assertFieldByName('translation[config_names][metatag.metatag_defaults.global][tags][title]');
    $this->assertFieldByName('translation[config_names][metatag.metatag_defaults.global][tags][title]', $edit['title']);
    $this->assertFieldByName('translation[config_names][metatag.metatag_defaults.global][tags][description]');
    $this->assertFieldByName('translation[config_names][metatag.metatag_defaults.global][tags][description]', $edit['description']);

    // Confirm the form can be saved correctly.
    $edit = [
      'translation[config_names][metatag.metatag_defaults.global][tags][title]' => 'Le title',
      'translation[config_names][metatag.metatag_defaults.global][tags][description]' => 'Le description',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save translation'));
    $this->assertResponse(200);
    $this->assertText(t('Successfully saved French translation'));
  }

}
