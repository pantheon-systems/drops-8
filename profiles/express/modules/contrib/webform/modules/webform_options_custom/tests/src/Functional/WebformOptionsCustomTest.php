<?php

namespace Drupal\Tests\webform_options_custom\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_options_custom\Entity\WebformOptionsCustom;

/**
 * Webform options custom test.
 *
 * @group webform_browser
 */
class WebformOptionsCustomTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'webform',
    'webform_options_custom',
    'webform_options_custom_test',
  ];

  /**
   * Test options custom.
   */
  public function testOptionsCustom() {
    $webform = Webform::load('test_element_options_custom_html');

    /**************************************************************************/
    // Webform custom options element.
    /**************************************************************************/

    $this->drupalGet('/webform/test_element_options_custom_html');

    // Check that 'data-option-value' is added to the basdic HTML markup.
    // @see webform_options_custom.webform_options_custom.test_html.yml
    $this->assertRaw('<div data-id="one" data-name="One" data-option-value="one">One</div>');
    $this->assertRaw('<div data-id="two" data-name="Two" data-option-value="two">Two</div>');
    $this->assertRaw('<div data-id="three" data-name="Three" data-option-value="three">Three</div>');

    // Check that 'data-option-value' is added to the advanced HTML markup.
    // @see webform_options_custom.webform_options_custom.test_html_advanced.yml
    $this->assertRaw('<div data-id="a" data-name="A -- This is the letter A" data-option-value="a">A</div>');
    $this->assertRaw('<div data-name="B" data-option-value="b">B</div>');
    $this->assertRaw('<div data-id="c" data-name="C" data-option-value="c">C</div>');

    // Check advanced HTML descriptions which all confirm that descriptions
    // can be overridden.
    $this->assertRaw('data-descriptions="{&quot;c&quot;:&quot;This is the letter C. [element#options]&quot;,&quot;b&quot;:&quot;\u003Cem\u003EThis is the letter B\u003C\/em\u003E alert(\u0027XSS\u0027);. [entity#options]&quot;,&quot;a&quot;:&quot;This is the letter A&quot;}"');

    // Check <script> tags are removed from descriptions.
    // @see \Drupal\webform_options_custom\Element\WebformOptionsCustom::processWebformOptionsCustom
    $this->assertNoRaw('\u003Cscript\u003Ealert(\u0027XSS\u0027);\u003C\/script\u003E');

    // Check validation.
    $this->postSubmission($webform);
    $this->assertRaw('webform_options_custom_html field is required.');
    $this->assertRaw('webform_options_custom_html_advanced field is required.');

    // Check preview.
    $this->postSubmission($webform, [
      'webform_options_custom_html[select]' => 'one',
      'webform_options_custom_html_advanced[select][]' => 'a',
    ], 'Preview');
    $this->assertPattern('#<label>webform_options_custom_html</label>\s*One\s*</div>#');
    $this->assertPattern('#<label>webform_options_custom_html_advanced</label>\s*A\s*</div>#');

    // Check processing.
    $this->postSubmission($webform, [
      'webform_options_custom_html[select]' => 'one',
      'webform_options_custom_html_advanced[select][]' => 'a',
    ]);
    $this->assertRaw('webform_options_custom_html: one
webform_options_custom_html_advanced:
  - a');

    // Check CSS asset.
    $this->drupalGet('/webform/css/test_element_options_custom_html');
    $this->assertRaw('.webform-options-custom--test-html-advanced [data-option-value]');

    // Check JavaScript asset.
    $this->drupalGet('/webform/javascript/test_element_options_custom_html');
    $this->assertRaw("window.console && window.console.log('Test: HTML advanced loaded.');");

    /**************************************************************************/
    // Webform custom options entity.
    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    // Get basic HTML with default settings.
    $this->drupalGet('/admin/structure/webform/config/options_custom/manage/test_html/preview');

    // Check 'data-fill' attribute.
    $this->assertCssSelect('.webform-options-custom--test-html[data-fill]');

    // Check 'data-tooltip' attribute.
    $this->assertCssSelect('.webform-options-custom--test-html[data-tooltip]');

    // Check no 'data-select-hidden' attribute.
    $this->assertNoCssSelect('.webform-options-custom--test-html[data-select-hidden]');

    // Update basic HTML settings.
    $webform_options_custom = WebformOptionsCustom::load('test_html');
    $webform_options_custom->set('fill', FALSE);
    $webform_options_custom->set('tooltip', FALSE);
    $webform_options_custom->set('show_select', FALSE);
    $webform_options_custom->save();

    // Get basic HTML with updated settings.
    $this->drupalGet('/admin/structure/webform/config/options_custom/manage/test_html/preview');

    // Check no 'data-fill' attribute.
    $this->assertNoCssSelect('.webform-options-custom--test-html[data-fill]');

    // Check no 'data-tooltip' attribute.
    $this->assertNoCssSelect('.webform-options-custom--test-html[data-tooltip]');

    // Check 'data-select-hidden' attribute.
    $this->assertCssSelect('.webform-options-custom--test-html[data-select-hidden]');

    /**************************************************************************/
    // Webform custom options Twig.
    /**************************************************************************/

    // Get preview has 3 options.
    $this->drupalGet('/admin/structure/webform/config/options_custom/manage/test_twig/preview');
    $this->assertRaw('<td data-option-value="1" style="text-align:center">1</td>');
    $this->assertRaw('<td data-option-value="2" style="text-align:center">2</td>');
    $this->assertRaw('<td data-option-value="3" style="text-align:center">3</td>');
    $this->assertNoRaw('<td data-option-value="4" style="text-align:center">4</td>');
    $this->assertNoRaw('<td data-option-value="5" style="text-align:center">5</td>');

    // Get instance has 5 options.
    $this->drupalGet('/webform/test_element_options_custom_twig');
    $this->assertRaw('<td data-option-value="1" style="text-align:center">1</td>');
    $this->assertRaw('<td data-option-value="2" style="text-align:center">2</td>');
    $this->assertRaw('<td data-option-value="3" style="text-align:center">3</td>');
    $this->assertRaw('<td data-option-value="4" style="text-align:center">4</td>');
    $this->assertRaw('<td data-option-value="5" style="text-align:center">5</td>');
  }

}
