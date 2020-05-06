<?php

namespace Drupal\Tests\webform_node\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\Tests\webform_node\Traits\WebformNodeBrowserTestTrait;

/**
 * Defines an abstract test base for webform node tests.
 */
abstract class WebformNodeBrowserTestBase extends WebformBrowserTestBase {

  use WebformNodeBrowserTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_node'];

}
