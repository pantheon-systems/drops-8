<?php

namespace Drupal\webform\Tests\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Tests for element ignored properties.
 *
 * @group Webform
 */
class WebformElementIgnoredPropertiesTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_ignored_properties'];

  /**
   * Test element ignored properties.
   */
  public function testIgnoredProperties() {
    $webform_ignored_properties = Webform::load('test_element_ignored_properties');
    $elements = $webform_ignored_properties->getElementsInitialized();
    foreach (WebformElementHelper::$ignoredProperties as $ignored_property) {
      $this->assert(!isset($elements['test'][$ignored_property]), new FormattableMarkup('@property ignored.', ['@property' => $ignored_property]));
    }
  }

}
