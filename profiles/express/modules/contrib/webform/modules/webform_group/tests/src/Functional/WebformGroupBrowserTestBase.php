<?php

namespace Drupal\Tests\webform_group\Functional;

use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\Tests\webform\Traits\WebformBrowserTestTrait;
use Drupal\Tests\webform\Traits\WebformAssertLegacyTrait;
use Drupal\Tests\webform_node\Traits\WebformNodeBrowserTestTrait;

/**
 * Base class for webform group tests.
 */
abstract class WebformGroupBrowserTestBase extends GroupBrowserTestBase {

  use WebformAssertLegacyTrait;
  use WebformBrowserTestTrait;
  use WebformNodeBrowserTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_group', 'webform_group_test'];

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->purgeSubmissions();
    parent::tearDown();
  }

}
