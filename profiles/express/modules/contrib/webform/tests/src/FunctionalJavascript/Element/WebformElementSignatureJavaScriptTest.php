<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests webform signature element.
 *
 * @group webform_javascript
 */
class WebformElementSignatureJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_signature',
  ];

  /**
   * Tests computed element Ajax.
   */
  public function testComputedElementAjax() {
    $session = $this->getSession();
    $page = $session->getPage();

    $webform = Webform::load('test_element_signature');

    /**************************************************************************/

    $this->drupalGet($webform->toUrl());

    // Check that default signature element 'Reset' button is visible.
    $this->assertTrue($page->find('css', '#edit-signature input[type="submit"]')->isVisible());

    // Check that disabled signature element 'Reset' button is hidden.
    $this->assertFalse($page->find('css', '#edit-signature-disabled input[type="submit"]')->isVisible());

    // Check that read-only signature element 'Reset' button is hidden.
    $this->assertFalse($page->find('css', '#edit-signature-readonly input[type="submit"]')->isVisible());

    // Check that disabled signature element 'Reset' button is hidden.
    $this->click('#edit-disable');
    $this->assertFalse($page->find('css', '#edit-signature input[type="submit"]')->isVisible());

    // Check that default signature element 'Reset' button is visible.
    $this->click('#edit-disable');
    $this->assertTrue($page->find('css', '#edit-signature input[type="submit"]')->isVisible());

    // Check that read-only signature element 'Reset' button is hidden.
    $this->click('#edit-readonly');
    $this->assertFalse($page->find('css', '#edit-signature input[type="submit"]')->isVisible());

    $this->drupalLogin($this->rootUser);

    // Check that default signature element has a test value.
    $this->drupalGet('/webform/test_element_signature/test');
    $this->assertTrue($page->find('css', '#edit-signature input[type="submit"]')->isVisible());
    $this->assertRaw('<input data-drupal-selector="edit-signature" aria-describedby="edit-signature--description" type="hidden" name="signature" value="data:image/png;base64');

    // Check that default signature element's test value has been reset.
    $this->click('#edit-signature input[type="submit"]');
    $this->assertNoRaw('<input data-drupal-selector="edit-signature" aria-describedby="edit-signature--description" type="hidden" name="signature" value="data:image/png;base64');
  }

}
