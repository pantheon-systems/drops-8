<?php

namespace Drupal\Tests\webform_ui\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests webform UI element JavasScript.
 *
 * @group webform_javascript
 */
class WebformUiElementJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webform', 'webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [];

  /**
   * Tests element.
   */
  public function testElement() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');

    // Check 'destination' element key warning.
    $title = $page->findField('properties[title]');
    $title->setValue('destination');
    $assert_session->waitForText("Please avoid using the reserved word 'destination' as the element's key.");

    // Check 'form_id' element key warning.
    $title = $page->findField('properties[title]');
    $title->setValue('destination');
    $assert_session->waitForText("Please avoid using the reserved word 'destination' as the element's key.");
  }

}
