<?php

namespace Drupal\Tests\metatag\Functional;

/**
 * Ensures that the Metatag field works correctly on nodes.
 *
 * @group metatag
 */
class MetatagFieldNodeTest extends MetatagFieldTestBase {

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
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $entityPerms = [
    // From Field UI.
    'administer node fields',

    // From Node.
    'access content',
    'administer content types',
    'administer nodes',
    'bypass node access',
    'create page content',
    'edit any page content',
    'edit own page content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'node';

  /**
   * {@inheritdoc}
   */
  protected $entityLabel = 'Content';

  /**
   * {@inheritdoc}
   */
  protected $entityBundle = 'page';

  /**
   * {@inheritdoc}
   */
  protected $entityAddPath = 'node/add';

  /**
   * {@inheritdoc}
   */
  protected $entityFieldAdminPath = 'admin/structure/types/manage/page/fields';

  /**
   * {@inheritdoc}
   */
  protected function setUpEntityType() {
    $this->createContentType(['type' => 'page']);

    // 8.3 has the label 'Save and publish'.
    if ((floatval(\Drupal::VERSION) <= 8.3)) {
      $this->entitySaveButtonLabel = 'Save and publish';
    }
  }

}
