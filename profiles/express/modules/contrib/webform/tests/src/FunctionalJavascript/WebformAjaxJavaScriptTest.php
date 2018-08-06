<?php

namespace Drupal\Tests\webform\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestTrait;

/**
 * Tests webform JavasScript.
 *
 * @group webform_javascript
 */
class WebformAjaxJavaScriptTest extends JavascriptTestBase {

  use WebformTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_ajax',
    'test_ajax_confirmation_inline',
    'test_ajax_confirmation_message',
    'test_ajax_confirmation_modal',
    'test_ajax_confirmation_page',
    'test_ajax_confirmation_url',
    'test_ajax_confirmation_url_msg',
  ];

  /**
   * Tests Ajax.
   */
  public function testAjax() {

    /**************************************************************************/
    // Test Ajax. (test_ajax)
    /**************************************************************************/

    $webform_ajax = Webform::load('test_ajax');

    $assert_session = $this->assertSession();

    // Validate form.
    $this->drupalPostForm($webform_ajax->toUrl(), ['textfield' => ''], t('Submit'));
    $assert_session->waitForElement('css', '.messages--error');

    // Check validation message.
    $assert_session->responseContains('textfield field is required.');

    // Preview form.
    $this->drupalPostForm($webform_ajax->toUrl(), ['textfield' => 'test value'], t('Preview'));
    $assert_session->waitForElement('css', '.messages--warning');

    // Check preview message.
    $assert_session->responseContains('Please review your submission. Your submission is not complete until you press the "Submit" button!');

    // Submit form.
    $this->drupalPostForm($webform_ajax->toUrl(), ['textfield' => 'test value'], t('Submit'));
    $assert_session->waitForElement('css', '.messages--status');

    // Check submit message.
    $assert_session->responseContains('New submission added to Test: Ajax.');

    // Check that submission was created.
    $sid = $this->getLastSubmissionId($webform_ajax);
    $this->assertEquals($sid, 1);

    // Check that text field is blank.
    $assert_session->fieldValueEquals('textfield', '');

    /**************************************************************************/
    // Test Ajax confirmation inline. (test_ajax_confirmation_inline)
    /**************************************************************************/

    $webform_ajax_confirmation_inline = Webform::load('test_ajax_confirmation_inline');

    // Submit form.
    $this->drupalPostForm($webform_ajax_confirmation_inline->toUrl(), [], t('Submit'));
    $assert_session->waitForElement('css', '.messages--status');

    // Check submit message.
    $assert_session->responseContains('This is a custom inline confirmation message.');

    // Click back to form.
    $this->clickLink('Back to form');
    $assert_session->waitForButton('Submit');

    // Check submit message.
    $assert_session->responseNotContains('This is a custom inline confirmation message.');
    $assert_session->responseContains('This webform will display the confirmation inline when submitted.');

    /**************************************************************************/
    // Test Ajax confirmation message. (test_ajax_confirmation_message)
    /**************************************************************************/

    $webform_ajax_confirmation_message = Webform::load('test_ajax_confirmation_message');

    // Submit form.
    $this->drupalPostForm($webform_ajax_confirmation_message->toUrl(), [], t('Submit'));
    $assert_session->waitForElement('css', '.messages--status');

    // Check confirmation message.
    $assert_session->responseContains('This is a <b>custom</b> confirmation message.');
    $assert_session->responseContains('This webform will display a confirmation message when submitted.');

    /**************************************************************************/
    // Test Ajax confirmation message. (test_ajax_confirmation_modal)
    /**************************************************************************/

    $webform_ajax_confirmation_modal = Webform::load('test_ajax_confirmation_modal');

    // Submit form.
    $this->drupalPostForm($webform_ajax_confirmation_modal->toUrl(), [], t('Submit'));
    $assert_session->waitForElementVisible('css', '.ui-dialog.webform-confirmation-modal');

    // Check confirmation modal.
    $assert_session->responseContains('This is a <b>custom</b> confirmation modal.');

    /**************************************************************************/
    // Test Ajax confirmation page. (test_ajax_confirmation_page)
    /**************************************************************************/

    $webform_ajax_confirmation_page = Webform::load('test_ajax_confirmation_page');

    // Submit form.
    $this->drupalPostForm($webform_ajax_confirmation_page->toUrl(), [], t('Submit'));
    $assert_session->waitForLink('Back to form');

    // Check confirmation page message.
    $assert_session->responseContains('This is a custom confirmation page.');

    /**************************************************************************/
    // Test Ajax confirmation url. (test_ajax_confirmation_url)
    /**************************************************************************/

    $webform_ajax_confirmation_url = Webform::load('test_ajax_confirmation_url');

    // Submit form.
    $this->drupalPostForm($webform_ajax_confirmation_url->toUrl(), [], t('Submit'));
    $assert_session->waitForElement('css', '.path-front');

    // Check current page is <front>.
    $this->assertSession()->addressEquals('/');

    /**************************************************************************/
    // Test Ajax confirmation url with message. (test_ajax_confirmation_url_msg)
    /**************************************************************************/

    $webform_ajax_confirmation_url_msg = Webform::load('test_ajax_confirmation_url_msg');

    // Submit form.
    $this->drupalPostForm($webform_ajax_confirmation_url_msg->toUrl(), [], t('Submit'));
    $assert_session->waitForElement('css', '.path-front');

    // Check current page is <front>.
    $this->assertSession()->addressEquals('/');

    // Check confirmation message.
    $assert_session->responseContains('This is a custom confirmation message.');
  }

}
