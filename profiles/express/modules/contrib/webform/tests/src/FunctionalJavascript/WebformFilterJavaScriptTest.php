<?php

namespace Drupal\Tests\webform\FunctionalJavascript;

/**
 * Tests webform filter javascript.
 *
 * @group webform_javascript
 */
class WebformFilterJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Test filter.
   */
  public function testFilter() {
    // Set admin theme to seven.
    \Drupal::service('theme_installer')->install(['seven']);
    \Drupal::configFactory()->getEditable('system.theme')
      ->set('admin', 'seven')
      ->save();

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformAddonsManagerInterface $addons_manager */
    $addons_manager = \Drupal::service('webform.addons_manager');

    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    // Check filter loaded.
    $this->drupalGet('/admin/structure/webform/addons');
    $assert_session->fieldExists('text');

    // Check results.
    $assert_session->waitForElementVisible('css', '.webform-addons-summary');
    $assert_session->waitForText(count($addons_manager->getProjects()) . ' add-ons');
    $this->assertTrue($page->findLink('Address')->isVisible());
    $this->assertFalse($page->find('css', '.webform-addons-no-results')->isVisible());
    $this->assertFalse($page->find('css', '.webform-form-filter-reset')->isVisible());

    // Check no results.
    $session->executeScript("jQuery(':input[name=\"text\"]').val('xxx').keyup()");
    $assert_session->waitForText('0 add-ons');
    $this->assertFalse($page->findLink('Address')->isVisible());
    $this->assertTrue($page->find('css', '.webform-addons-no-results')->isVisible());
    $this->assertTrue($page->find('css', '.webform-form-filter-reset')->isVisible());

    // Check reset.
    $session->executeScript("jQuery('.webform-form-filter-reset').click()");
    $assert_session->waitForElementVisible('css', '.webform-addons-summary');
    $assert_session->waitForText(count($addons_manager->getProjects()) . ' add-ons');
    $this->assertTrue($page->findLink('Address')->isVisible());
    $this->assertFalse($page->find('css', '.webform-addons-no-results')->isVisible());
    $this->assertFalse($page->find('css', '.webform-form-filter-reset')->isVisible());
  }

}
