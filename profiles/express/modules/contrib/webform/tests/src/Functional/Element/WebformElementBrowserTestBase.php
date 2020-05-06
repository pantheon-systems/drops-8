<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Defines an abstract test base for webform element tests.
 */
abstract class WebformElementBrowserTestBase extends WebformBrowserTestBase {

  /**
   * Assert element preview.
   *
   * @param string $label
   *   The element's label.
   * @param string $value
   *   The element's value.
   */
  protected function assertElementPreview($label, $value) {
    $this->assertPattern('/<label>' . preg_quote($label, '/') . '<\/label>\s+' . preg_quote($value, '/') . '/');
  }

}
