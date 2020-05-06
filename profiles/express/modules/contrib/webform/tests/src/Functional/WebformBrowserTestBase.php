<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\webform\Traits\WebformBrowserTestTrait;
use Drupal\Tests\webform\Traits\WebformAssertLegacyTrait;

/**
 * Defines an abstract test base for webform tests.
 */
abstract class WebformBrowserTestBase extends BrowserTestBase {

  use AssertMailTrait;
  use WebformBrowserTestTrait;
  use WebformAssertLegacyTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->loadWebforms(static::$testWebforms);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->purgeSubmissions();
    parent::tearDown();
  }

}
