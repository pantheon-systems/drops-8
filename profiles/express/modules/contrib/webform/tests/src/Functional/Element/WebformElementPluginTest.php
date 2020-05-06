<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for the webform element plugin.
 *
 * @group Webform
 */
class WebformElementPluginTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_element'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_plugin'];

  /**
   * Tests webform element plugin.
   */
  public function testElementPlugin() {
    $this->drupalLogin($this->rootUser);

    /**************************************************************************/
    // Dependencies. @see hook_webform_element_info_alter()
    /**************************************************************************/

    // Check that managed_file and webform_term-select are not available when
    // dependent modules are not installed.
    $this->drupalGet('/admin/reports/webform-plugins/elements');
    $this->assertNoRaw('<td><div class="webform-form-filter-text-source">managed_file</div></td>');
    $this->assertNoRaw('<td><div class="webform-form-filter-text-source">webform_term_select</div></td>');

    // Install file and taxonomy module.
    \Drupal::service('module_installer')->install(['file', 'taxonomy']);

    // Check that managed_file and webform_term-select are available when
    // dependent modules are installed.
    $this->drupalGet('/admin/reports/webform-plugins/elements');
    $this->assertRaw('<td><div class="webform-form-filter-text-source">managed_file</div></td>');
    $this->assertRaw('<td><div class="webform-form-filter-text-source">webform_term_select</div></td>');

    /**************************************************************************/
    // Plugin hooks.
    /**************************************************************************/

    // Get the webform test element.
    $webform_plugin_test = Webform::load('test_element_plugin');

    // Check prepare and setDefaultValue().
    $this->drupalGet('/webform/test_element_plugin');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preCreate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postCreate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:prepare');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:setDefaultValue');

    // Check save.
    $sid = $this->postSubmission($webform_plugin_test);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preCreate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:prepare');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:setDefaultValue');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement::validate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preSave');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postSave insert');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');

    // Check update.
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid . '/edit', [], t('Save'));
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:prepare');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:setDefaultValue');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement::validate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preSave');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postSave update');

    // Check HTML.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid);
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:formatHtml');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:formatText');

    // Check plain text.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid . '/text');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:formatText');

    // Check delete.
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid . '/delete', [], t('Delete'));
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preDelete');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postDelete');
    $this->assertRaw('<em class="placeholder">Test: Element: Test (plugin): Submission #' . $webform_submission->serial() . '</em> has been deleted.');
  }

}
