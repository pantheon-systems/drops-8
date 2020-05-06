<?php

namespace Drupal\Tests\webform\FunctionalJavaScript\Wizard;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform basic wizard.
 *
 * @group Webform
 */
class WebformWizardBasicJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_basic'];

  /**
   * Test webform basic wizard.
   */
  public function testBasicWizard() {
    global $base_path;

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_form_wizard_basic');

    /**************************************************************************/

    // Check page 1 URL.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $this->assertRaw('Element 1');
    $this->assertQuery();

    // Check page 2 URL.
    $page->pressButton('edit-wizard-next');
    $assert_session->waitForText('Element 2');
    $this->assertQuery();

    // Enable tracking by name.
    $webform
      ->setSetting('wizard_track', 'name')
      ->save();

    // Check page 1 URL with ?page=*.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $this->assertRaw('Element 1');
    $this->assertQuery();

    // Check page 2 URL with ?page=2.
    $page->pressButton('edit-wizard-next');
    $assert_session->waitForText('Element 2');
    $this->assertQuery('page=page_2');

    // Check page 1 URL with ?page=1.
    $page->pressButton('edit-wizard-prev');
    $assert_session->waitForText('Element 1');
    $this->assertQuery('page=page_1');

    // Check page 1 URL with custom param.
    $this->drupalGet('/webform/test_form_wizard_basic', ['query' => ['custom_param' => '1']]);
    $this->assertRaw('Element 1');
    $this->assertQuery('custom_param=1');

    // Check page 2 URL with ?page=2.
    $page->pressButton('edit-wizard-next');
    $assert_session->waitForText('Element 2');
    $this->assertQuery('custom_param=1&page=page_2');

    // Check page 1 URL with ?page=1.
    $page->pressButton('edit-wizard-prev');
    $assert_session->waitForText('Element 1');
    $this->assertQuery('custom_param=1&page=page_1');
  }

  /**
   * Passes if the query string on the current page is matched, fail otherwise.
   *
   * @param string $expected_query
   *   The expected query string.
   */
  protected function assertQuery($expected_query = '') {
    $actual_query = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY) ?: '';
    $this->assertEquals($expected_query, $actual_query);
  }

}
