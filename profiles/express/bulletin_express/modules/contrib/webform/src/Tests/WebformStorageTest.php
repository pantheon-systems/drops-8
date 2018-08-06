<?php

namespace Drupal\webform\Tests;

/**
 * Tests for webform storage tests.
 *
 * @group Webform
 */
class WebformStorageTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform'];

  /**
   * Test webform storage.
   *
   * @see \Drupal\webform\WebformEntityStorage::load
   */
  public function testStorageCaching() {
    /** @var \Drupal\webform\WebformEntityStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('webform');

    $webform = $storage->load('contact');
    $webform->cached = TRUE;

    // Check that load (single) has the custom 'cached' property.
    $this->assertEqual($webform->cached, $storage->load('contact')->cached);

    // Check that loadMultiple does not have the custom 'cached' property.
    // The below test will fail when and if
    // 'Issue #1885830: Enable static caching for config entities.'
    // is resolved.
    $this->assert(!isset($storage->loadMultiple(['contact'])->cached));
  }

}
