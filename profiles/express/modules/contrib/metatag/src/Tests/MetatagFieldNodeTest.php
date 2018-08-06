<?php

namespace Drupal\metatag\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\metatag\Tests\MetatagFieldTestBase;

/**
 * Ensures that the Metatag field works correctly on nodes.
 *
 * @group metatag
 */
class MetatagFieldNodeTest extends MetatagFieldTestBase {

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
    'node',
  ];

  /**
   * {@inheritDoc}
   */
  protected $entity_perms = [
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
   * {@inheritDoc}
   */
  protected $entity_type = 'node';

  /**
   * {@inheritDoc}
   */
  protected $entity_label = 'Content';

  /**
   * {@inheritDoc}
   */
  protected $entity_bundle = 'page';

  /**
   * {@inheritDoc}
   */
  protected $entity_add_path = 'node/add';

  /**
   * {@inheritDoc}
   */
  protected $entity_field_admin_path = 'admin/structure/types/manage/page/fields';

  /**
   * {@inheritDoc}
   */
  protected function setUpEntityType() {
    $this->createContentType(['type' => 'page']);

    // 8.3 has the label 'Save and publish'.
    if ((floatval(\Drupal::VERSION) <= 8.3)) {
      $this->entity_save_button_label = 'Save and publish';
    }
  }

}
