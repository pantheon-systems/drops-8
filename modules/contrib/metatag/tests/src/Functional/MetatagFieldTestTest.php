<?php

namespace Drupal\Tests\metatag\Functional;

/**
 * Ensure that the Metatag field works correctly for the test entity.
 *
 * @group metatag
 */
class MetatagFieldTestTest extends MetatagFieldTestBase {

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
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $entityPerms = [
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected $entityLabel = 'Test entity';

  /**
   * {@inheritdoc}
   */
  protected $entityBundle = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected $entityAddPath = 'entity_test/add';

  /**
   * {@inheritdoc}
   */
  protected $entityFieldAdminPath = 'entity_test/structure/entity_test/fields';

  /**
   * Whether or not the entity type supports defaults.
   *
   * @var bool
   *
   * @todo Fix this.
   */
  protected $entitySupportsDefaults = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $entityTitleField = 'name';

}
