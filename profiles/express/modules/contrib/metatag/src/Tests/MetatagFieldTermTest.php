<?php

namespace Drupal\metatag\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\metatag\Tests\MetatagFieldTestBase;

/**
 * Ensures that the Metatag field works correctly on taxonomy terms.
 *
 * @group metatag
 */
class MetatagFieldTermTest extends MetatagFieldTestBase {

  /**
   * {@inheritDoc}
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
   * {@inheritDoc}
   */
  protected $entity_perms = [
    // From Field UI.
    'administer taxonomy_term fields',

    // From Taxonomy.
    'administer taxonomy',
    'edit terms in tags',
    'delete terms in tags',
  ];

  /**
   * {@inheritDoc}
   */
  protected $entity_type = 'taxonomy_term';

  /**
   * {@inheritDoc}
   */
  protected $entity_label = 'Taxonomy term';

  /**
   * {@inheritDoc}
   */
  protected $entity_bundle = 'entity_test';

  /**
   * {@inheritDoc}
   */
  protected $entity_add_path = 'admin/structure/taxonomy/manage/tags/add';

  /**
   * {@inheritDoc}
   */
  protected $entity_field_admin_path = 'admin/structure/taxonomy/manage/tags/overview/fields';

  /**
   * {@inheritDoc}
   */
  protected $entity_title_field = 'name';

  /**
   * {@inheritDoc}
   */
  protected function setUpEntityType() {
    $new_perms = [
      // From Taxonomy.
      'administer taxonomy',
    ];
    $all_perms = array_merge($this->base_perms, $new_perms);
    $this->adminUser = $this->drupalCreateUser($all_perms);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->assertResponse(200);
    $edit = [
      'name' => 'Tags',
      'vid' => 'tags',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalLogout();
  }

}
