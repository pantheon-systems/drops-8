<?php

namespace Drupal\webform\Tests\Element;

use Drupal\Core\Form\OptGroup;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform element #states selectors.
 *
 * @group Webform
 */
class WebformElementStatesSelectorsTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'file', 'language', 'taxonomy', 'node', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_example_elements', 'test_example_elements_composite'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create 'tags' vocabulary.
    $this->createTags();
  }

  /**
   * Tests element #states selectors for basic and composite elements.
   */
  public function testSelectors() {
    foreach (['test_example_elements', 'test_example_elements_composite'] as $webform_id) {
      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = Webform::load($webform_id);
      $webform->setStatus(WebformInterface::STATUS_OPEN)->save();

      $this->drupalGet('webform/' . $webform_id);

      $selectors = OptGroup::flattenOptions($webform->getElementsSelectorOptions());
      // Ignore text format and captcha selectors which are not available during
      // this test.
      unset(
        $selectors[':input[name="text_format[format]"]'],
        $selectors[':input[name="captcha"]']
      );
      foreach ($selectors as $selector => $name) {
        // Remove :input since it is a jQuery specific selector.
        $selector = str_replace(':input', '', $selector);
        $this->assertCssSelect($selector);
      }
    }
  }

}
