<?php

namespace Drupal\Tests\metatag\Functional;

/**
 * Verify that enabling WebProfiler won't cause the site to blow up.
 *
 * @group metatag
 */
class EnsureDevelWebProfilerWorks extends EnsureDevelWorks {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',

    // Use the custom route to verify the site works.
    'metatag_test_custom_route',

    // The modules to test.
    'devel',
    'webprofiler',
  ];

}
