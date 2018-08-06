<?php

namespace Drupal\metatag\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\metatag\Tests\MetatagFieldTestBase;

/**
 * Ensure that the Metatag field works correctly for the test entity.
 *
 * @group metatag
 */
class MetatagFieldTestTest extends MetatagFieldTestBase {

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
    'entity_test',
  ];

  /**
   * {@inheritDoc}
   */
  protected $entity_perms = [
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
  ];

  /**
   * {@inheritDoc}
   */
  protected $entity_type = 'entity_test';

  /**
   * {@inheritDoc}
   */
  protected $entity_label = 'Test entity';

  /**
   * {@inheritDoc}
   */
  protected $entity_bundle = 'entity_test';

  /**
   * {@inheritDoc}
   */
  protected $entity_add_path = 'entity_test/add';

  /**
   * {@inheritDoc}
   */
  protected $entity_field_admin_path = 'entity_test/structure/entity_test/fields';

  /**
   * @todo Fix this.
   */
  protected $entity_supports_defaults = FALSE;

  /**
   * {@inheritDoc}
   */
  protected $entity_title_field = 'name';

}
