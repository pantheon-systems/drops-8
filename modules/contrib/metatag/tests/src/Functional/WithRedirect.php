<?php

namespace Drupal\Tests\metatag\Functional;

/**
 * Tests the Metatag administration when Redirect is installed.
 *
 * @group metatag
 */
class WithRedirect extends MetatagAdminTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field_ui',
    'test_page_test',
    'token',
    'metatag',

    // @see testAvailableConfigEntities
    'block',
    'block_content',
    'comment',
    'contact',
    'menu_link_content',
    'menu_ui',
    'shortcut',
    'taxonomy',
    'entity_test',

    // The whole point of this test.
    'redirect',
  ];

}
