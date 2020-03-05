<?php

namespace Drupal\Tests\token\Functional;

use Drupal\Tests\menu_ui\Functional\MenuUiContentModerationTest;

/**
 * Tests Menu UI and Content Moderation integration.
 *
 * @group token
 */
class TokenMenuUiContentModerationTest extends MenuUiContentModerationTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['token'];

}
