<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ensures that the Metatag field works correctly on taxonomy terms.
 *
 * @group metatag
 */
class MetatagFieldTermTest extends MetatagFieldTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
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
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $entityPerms = [
    // From Field UI.
    'administer taxonomy_term fields',

    // From Taxonomy.
    'administer taxonomy',
    'edit terms in tags',
    'delete terms in tags',
  ];

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'taxonomy_term';

  /**
   * {@inheritdoc}
   */
  protected $entityLabel = 'Taxonomy term';

  /**
   * {@inheritdoc}
   */
  protected $entityBundle = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected $entityAddPath = 'admin/structure/taxonomy/manage/tags/add';

  /**
   * {@inheritdoc}
   */
  protected $entityFieldAdminPath = 'admin/structure/taxonomy/manage/tags/overview/fields';

  /**
   * {@inheritdoc}
   */
  protected $entityTitleField = 'name';

  /**
   * {@inheritdoc}
   */
  protected function setUpEntityType() {
    $new_perms = [
      // From Taxonomy.
      'administer taxonomy',
    ];
    $all_perms = array_merge($this->basePerms, $new_perms);
    $this->adminUser = $this->drupalCreateUser($all_perms);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'name' => 'Tags',
      'vid' => 'tags',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->drupalLogout();
  }

}
