<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests webform computed element Ajax support.
 *
 * @see \Drupal\Tests\ajax_example\FunctionalJavascript\AjaxWizardTest
 *
 * @group webform_javascript
 */
class WebformElementComputedJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_computed_ajax',
  ];

  /**
   * Tests computed element Ajax.
   */
  public function testComputedElementAjax() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_computed_ajax');

    /**************************************************************************/

    // Check computed Twig element a and b elements exist.
    $this->drupalGet($webform->toUrl());
    $assert_session->fieldExists('a[select]');
    $assert_session->fieldExists('b');
    $assert_session->buttonExists('webform-computed-webform_computed_twig-button');
    $assert_session->hiddenFieldValueEquals('webform_computed_twig', 'Please enter a value for a and b.');

    // Calculate computed Twig element.
    $page->fillField('a[select]', '1');
    $page->fillField('b', '1');
    $session->executeScript("jQuery('input[name=\"webform_computed_twig\"]').click()");
    $assert_session->waitForText('1 + 1 = 2');

    // Check that computed Twig was calculated.
    $assert_session->hiddenFieldValueNotEquals('webform_computed_twig', 'Please enter a value for a and b.');
    $assert_session->hiddenFieldValueEquals('webform_computed_twig', '1 + 1 = 2');
  }

}
