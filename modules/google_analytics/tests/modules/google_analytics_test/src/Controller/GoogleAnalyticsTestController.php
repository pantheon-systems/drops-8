<?php

namespace Drupal\google_analytics_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for system_test routes.
 */
class GoogleAnalyticsTestController extends ControllerBase {

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return string
   *   Empty string, we just test the setting of messages.
   */
  public function drupalSetMessageTest() {
    // Set some messages.
    drupal_set_message('Example status message.', 'status');
    drupal_set_message('Example warning message.', 'warning');
    drupal_set_message('Example error message.', 'error');
    drupal_set_message('Example error <em>message</em> with html tags and <a href="http://example.com/">link</a>.', 'error');

    return [];
  }

}
