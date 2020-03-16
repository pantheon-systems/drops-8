<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for ensuring that the Metatag field works correctly.
 */
abstract class MetatagFieldTestBase extends BrowserTestBase {

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    // Needed for token handling.
    'token',

    // Needed for the field UI testing.
    'field_ui',

    // Needed to verify that nothing is broken for unsupported entities.
    'contact',

    // The base module.
    'metatag',

    // Some extra custom logic for testing Metatag.
    'metatag_test_tag',

    // Manages the entity type that is being tested.
    'entity_test',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Basic permissions that all of the entity tests will need.
   *
   * @var array
   */
  protected $basePerms = [
    'access administration pages',
    'administer meta tags',
  ];

  /**
   * Additional permissions needed for this entity type.
   *
   * @var array
   */
  protected $entityPerms = [];

  /**
   * The entity type that is being tested.
   *
   * @var string
   */
  protected $entityType = '';

  /**
   * The formal name for this entity type.
   *
   * @var string
   */
  protected $entityLabel = '';

  /**
   * The entity bundle that is being tested.
   *
   * @var string
   */
  protected $entityBundle = '';

  /**
   * The path to add an object for this entity type.
   *
   * @var string
   */
  protected $entityAddPath = '';

  /**
   * The path to access the field admin for this entity bundle.
   *
   * @var string
   */
  protected $entityFieldAdminPath = '';

  /**
   * Whether or not this entity type supports default meta tag values.
   *
   * @var bool
   */
  protected $entitySupportsDefaults = TRUE;

  /**
   * The label used on the entity form for the 'save' action.
   *
   * @var string
   */
  protected $entitySaveButtonLabel = 'Save';

  /**
   * The name of the primary title or name field for this entity.
   *
   * @var string
   */
  protected $entityTitleField = 'title';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Any additional configuration that's neede for this entity type.
    $this->setUpEntityType();

    // Merge the base permissions with the custom ones for the entity type and
    // create a user with these permissions.
    $all_perms = array_merge($this->basePerms, $this->entityPerms);
    $this->adminUser = $this->drupalCreateUser($all_perms);
    $this->drupalGet('/user/login');
    $this->assertResponse(200);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * {@inheritdoc}
   */
  protected function verbose($message, $title = NULL) {
    // Handle arrays, objects, etc.
    if (!is_string($message)) {
      $message = "<pre>\n" . print_r($message, TRUE) . "\n</pre>\n";
    }

    // Optional title to go before the output.
    if (!empty($title)) {
      $title = '<h2>' . Html::escape($title) . "</h2>\n";
    }

    parent::verbose($title . $message);
  }

  /**
   * Any additional configuration that's needed for this entity type.
   */
  protected function setUpEntityType() {}

  /**
   * A list of default values to add to the entity being created.
   *
   * Defaults to "{$entityTitleField}[0][value]" => $title.
   *
   * @return array
   *   Default values.
   */
  protected function entityDefaultValues() {
    return [];
  }

  /**
   * Add a Metatag field to this entity type.
   */
  protected function addField() {
    // Add a metatag field to the entity type test_entity.
    $this->drupalGet($this->entityFieldAdminPath . '/add-field');
    $this->assertResponse(200);
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag',
      'new_storage_type' => 'metatag',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));

    // Clear all settings.
    $this->container->get('entity.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Confirm that the global default values work correctly.
   *
   * Specifically when there are no entity or bundle defaults available.
   */
  public function testGlobalDefaultsInheritance() {
    // First we set global defaults.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $global_values = [
      'metatag_test_tag' => 'Global description',
    ];
    $this->drupalPostForm(NULL, $global_values, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');

    // Add the field to this entity type.
    $this->addField();

    // Now when we create an entity, global defaults are used to fill the form
    // fields.
    $this->drupalGet($this->entityAddPath);
    $this->assertResponse(200);
    $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag'], t('The metatag_test_tag field has the global default as the field default does not define it.'));
  }

  /**
   * Confirm that the entity default values work correctly.
   */
  public function testEntityDefaultsInheritance() {
    // This test doesn't make sense if the entity doesn't support defaults.
    if (!$this->entitySupportsDefaults) {
      return;
    }

    // Set a global default.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $global_values = [
      'metatag_test_tag' => 'Global description',
    ];
    $this->drupalPostForm(NULL, $global_values, 'Save');
    $this->assertText(strip_tags(t('Saved the %label Metatag defaults.', ['%label' => t('Global')])));

    // Set an entity default.
    $this->drupalGet('admin/config/search/metatag/' . $this->entityType);
    $this->assertResponse(200);
    $entity_values = [
      'metatag_test_tag' => 'Entity description',
    ];
    $this->drupalPostForm(NULL, $entity_values, 'Save');
    $this->assertText(strip_tags(t('Saved the %label Metatag defaults.', ['%label' => t($this->entityLabel)])));

    // Add the field to this entity type.
    $this->addField();

    // Load the entity form for this entity type.
    $this->drupalGet($this->entityAddPath);
    $this->assertResponse(200);
    $this->assertNoText('Fatal error');

    // Allow the fields to be customized if needed.
    $title = 'Barfoo';
    $edit = $this->entityDefaultValues();
    if (empty($edit)) {
      $edit = [
        $this->entityTitleField . '[0][value]' => $title,
      ];
    }

    // If this entity type supports defaults then verify the global default is
    // not present but that the entity default *is* present.
    $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $entity_values['metatag_test_tag']);
    $this->assertNoFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag']);
  }

  /**
   * Confirm that the default values for an entity bundle work.
   *
   * When there is no field for overriding the defaults.
   *
   * @todo
   */
  public function testBundleDefaultsInheritance() {
  }

  /**
   * Confirm a field can be added to the entity bundle.
   */
  public function testFieldCanBeAdded() {
    $this->drupalGet($this->entityFieldAdminPath . '/add-field');
    $this->assertResponse(200);
    $this->assertRaw('<option value="metatag">' . t('Meta tags') . '</option>');
  }

  /**
   * Confirm a field can be added to the entity bundle.
   */
  public function testEntityFieldsAvailable() {
    // Add a field to the entity type.
    $this->addField();

    // Load the entity's form.
    $this->drupalGet($this->entityAddPath);
    $this->assertResponse(200);
    $this->assertNoText('Fatal error');
    $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]');
  }

  /**
   * Confirm that the default values load correctly for an entity created.
   *
   * Before the custom field is added.
   */
  public function testEntityFieldValuesOldEntity() {
    // Set a global default.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $global_values = [
      'metatag_test_tag' => 'Global description',
    ];
    $this->drupalPostForm(NULL, $global_values, 'Save');
    $this->assertText(strip_tags(t('Saved the %label Metatag defaults.', ['%label' => t('Global')])));

    // Set an entity default if it's supported by the entity type.
    if ($this->entitySupportsDefaults) {
      $this->drupalGet('admin/config/search/metatag/' . $this->entityType);
      $this->assertResponse(200);
      $entity_values = [
        'metatag_test_tag' => 'Entity description',
      ];
      $this->drupalPostForm(NULL, $entity_values, 'Save');
      $this->assertText(strip_tags(t('Saved the %label Metatag defaults.', ['%label' => t($this->entityLabel)])));
    }

    // Load the entity form for this entity type.
    $title = 'Barfoo';
    $this->drupalGet($this->entityAddPath);
    $this->assertResponse(200);
    $this->assertNoText('Fatal error');

    // Allow the fields to be customized if needed.
    $edit = $this->entityDefaultValues();
    if (empty($edit)) {
      $edit = [
        $this->entityTitleField . '[0][value]' => $title,
      ];
    }

    // Create a new entity object.
    $this->drupalPostForm(NULL, $edit, t($this->entitySaveButtonLabel));
    $entities = \Drupal::entityTypeManager()
      ->getStorage($this->entityType)
      ->loadByProperties([$this->entityTitleField => $title]);
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);

    // @todo Confirm the values output correctly.
    // Add a field to the entity type.
    $this->addField();

    // Open the 'edit' form for the entity.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertResponse(200);

    // If this entity type supports defaults then verify the global default is
    // not present but that the entity default *is* present.
    if ($this->entitySupportsDefaults) {
      $this->assertNoFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag']);
      $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $entity_values['metatag_test_tag']);
    }
    else {
      $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag']);
    }

    // @todo Confirm the values output correctly.
  }

  /**
   * Confirm that the default values load correctly.
   *
   * For an entity created after the custom field is added.
   */
  public function testEntityFieldValuesNewEntity() {
    // Set a global default.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $global_values = [
      'metatag_test_tag' => 'Global description',
    ];
    $this->drupalPostForm(NULL, $global_values, 'Save');
    $this->assertText(strip_tags(t('Saved the %label Metatag defaults.', ['%label' => t('Global')])));

    // Set an entity default if it's supported by the entity type.
    if ($this->entitySupportsDefaults) {
      $this->drupalGet('admin/config/search/metatag/' . $this->entityType);
      $this->assertResponse(200);
      $entity_values = [
        'metatag_test_tag' => 'Entity description',
      ];
      $this->drupalPostForm(NULL, $entity_values, 'Save');
      $this->assertText(strip_tags(t('Saved the %label Metatag defaults.', ['%label' => t($this->entityLabel)])));
    }

    // Add a field to the entity type.
    $this->addField();

    // Load the entity form for this entity type.
    $title = 'Barfoo';
    $this->drupalGet($this->entityAddPath);
    $this->assertResponse(200);
    $this->assertNoText('Fatal error');

    // If this entity type supports defaults then verify the global default is
    // not present but that the entity default *is* present.
    if ($this->entitySupportsDefaults) {
      $this->assertNoFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag']);
      $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $entity_values['metatag_test_tag']);
    }
    else {
      $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag']);
    }

    // Allow the fields to be customized if needed.
    $edit = $this->entityDefaultValues();
    if (empty($edit)) {
      $edit = [
        $this->entityTitleField . '[0][value]' => $title,
      ];
    }

    // Create a new entity object.
    $this->drupalPostForm(NULL, $edit, t($this->entitySaveButtonLabel));
    $entities = \Drupal::entityTypeManager()
      ->getStorage($this->entityType)
      ->loadByProperties([$this->entityTitleField => $title]);
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);

    // @todo Confirm the values output correctly.
    // Open the 'edit' form for the entity.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertResponse(200);

    // If this entity type supports defaults then verify the global default is
    // not present but that the entity default *is* present.
    if ($this->entitySupportsDefaults) {
      $this->assertNoFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag']);
      $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $entity_values['metatag_test_tag']);
    }
    else {
      $this->assertFieldByName('field_metatag[0][basic][metatag_test_tag]', $global_values['metatag_test_tag']);
    }

    // @todo Confirm the values output correctly.
  }

  /**
   * Tests adding and editing values on a given entity type.
   *
   * @todo Finish this.
   */
  public function tofixTestEntityField() {
    // Add a field to the entity type.
    $this->addField();

    // Create a test entity.
    $this->drupalGet($this->entityAddPath);
    $this->assertResponse(200);
    $this->assertNoText('Fatal error');
    $edit = $this->entityDefaultValues($title) + [
      'field_metatag[0][basic][metatag_test_tag]' => 'Kilimanjaro',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $entities = \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->loadByProperties([$this->entityTitleField => 'Barfoo']);
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);

    // Make sure tags that have a field value but no default value still show
    // up.
    $this->drupalGet($entity->toUrl());
    $this->assertResponse(200);
    $elements = $this->cssSelect('meta[name=metatag_test_tag]');
    $this->assertTrue(count($elements) === 1, 'Found keywords metatag_test_tag from defaults');
    $this->assertEqual((string) $elements[0]['content'], 'Kilimanjaro', 'Field value for metatag_test_tag found when no default set.');

    // @todo This should not be required, but meta tags does not invalidate
    // cache upon setting globals.
    Cache::invalidateTags(['entity_test:' . $entity->id()]);

    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $values = [
      'metatag_test_tag' => 'Purple monkey dishwasher',
    ];
    $this->drupalPostForm(NULL, $values, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');
    $this->drupalGet($entity->toUrl());
    $this->assertResponse(200);
    $elements = $this->cssSelect('meta[name=metatag_test_tag]');
    $this->assertTrue(count($elements) === 1, 'Found test metatag from defaults');
    $this->verbose('<pre>' . print_r($elements, TRUE) . '</pre>');
    $this->assertEqual((string) $elements[0]['content'], $values['metatag_test_tag']);
  }

}
