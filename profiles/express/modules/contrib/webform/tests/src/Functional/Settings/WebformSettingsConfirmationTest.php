<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission form confirmation.
 *
 * @group Webform
 */
class WebformSettingsConfirmationTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_confirmation_message',
    'test_confirmation_modal',
    'test_confirmation_inline',
    'test_confirmation_page',
    'test_confirmation_page_custom',
    'test_confirmation_url',
    'test_confirmation_url_message',
    'test_confirmation_none',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set page.front (aka <front>) to /node instead of /user/login.
    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node')->save();
  }

  /**
   * Tests webform submission form confirmation.
   */
  public function testConfirmation() {
    // Login the admin user.
    $this->drupalLogin($this->rootUser);

    /* Test confirmation message (confirmation_type=message) */

    $webform_confirmation_message = Webform::load('test_confirmation_message');

    // Check confirmation message.
    $this->postSubmission($webform_confirmation_message);
    $this->assertRaw('This is a <b>custom</b> confirmation message.');
    $this->assertNoRaw('New submission added to <em class="placeholder">Test: Confirmation: Message</em>');
    $this->assertUrl('webform/test_confirmation_message');

    // Check confirmation page with custom query parameters.
    $sid = $this->postSubmission($webform_confirmation_message, [], NULL, ['query' => ['custom' => 'param']]);
    $this->assertUrl('webform/test_confirmation_message', ['query' => ['custom' => 'param']]);

    // Sleep for 1 second to ensure the submission's timestamp indicates
    // it was update.
    sleep(1);

    // Check default message when submission is updated.
    $this->drupalPostForm("/admin/structure/webform/manage/test_confirmation_message/submission/$sid/edit", [], t('Save'));
    $this->assertNoRaw('This is a <b>custom</b> confirmation message. (test: )');
    $this->assertRaw('Submission updated in <em class="placeholder">Test: Confirmation: Message</em>.');

    // Set display confirmation when submission is updated.
    $webform_confirmation_message->setSetting('confirmation_update', TRUE)
      ->save();

    // Check default message when submission is updated.
    $this->drupalPostForm("/admin/structure/webform/manage/test_confirmation_message/submission/$sid/edit", [], t('Save'));
    $this->assertRaw('This is a <b>custom</b> confirmation message. (test: )');
    $this->assertNoRaw('Submission updated in <em class="placeholder">Test: Confirmation: Message</em>.');

    /* Test confirmation message (confirmation_type=modal) */

    $webform_confirmation_modal = Webform::load('test_confirmation_modal');

    // Check confirmation modal.
    $sid = $this->postSubmission($webform_confirmation_modal, ['test' => 'value']);
    $this->assertRaw('This is a <b>custom</b> confirmation modal.');
    $this->assertRaw('<div class="js-hide webform-confirmation-modal js-webform-confirmation-modal webform-message js-webform-message js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-confirmation-modal" id="edit-webform-confirmation-modal">');
    $this->assertRaw('<div role="contentinfo" aria-label="Status message" class="messages messages--status">');
    $this->assertRaw('<b class="webform-confirmation-modal--title">Custom confirmation modal</b><br />');
    $this->assertRaw('<div class="webform-confirmation-modal--content">This is a <b>custom</b> confirmation modal. (test: value)</div>');
    $this->assertUrl('webform/test_confirmation_modal');

    // Check confirmation modal update does not display modal.
    $this->drupalPostForm("/admin/structure/webform/manage/test_confirmation_modal/submission/$sid/edit", [], t('Save'));
    $this->assertRaw('Submission updated in <em class="placeholder">Test: Confirmation: Modal</em>.');

    // Set display confirmation modal when submission is updated.
    $webform_confirmation_modal->setSetting('confirmation_update', TRUE)
      ->save();

    // Check confirmation modal update does display modal.
    $this->drupalPostForm("/admin/structure/webform/manage/test_confirmation_modal/submission/$sid/edit", [], t('Save'));
    $this->assertRaw('<b class="webform-confirmation-modal--title">Custom confirmation modal</b><br /><div class="webform-confirmation-modal--content">This is a <b>custom</b> confirmation modal. (test: value)</div>');

    /* Test confirmation inline (confirmation_type=inline) */

    $webform_confirmation_inline = Webform::load('test_confirmation_inline');

    // Check confirmation inline.
    $this->drupalPostForm('/webform/test_confirmation_inline', [], t('Submit'));
    $this->assertRaw('<a href="' . $webform_confirmation_inline->toUrl('canonical', ['absolute' => TRUE])->toString() . '" rel="prev" title="Back to form">Back to form</a>');
    $this->assertUrl('webform/test_confirmation_inline');

    // Check confirmation inline with custom query parameters.
    $this->drupalPostForm('/webform/test_confirmation_inline', [], t('Submit'), ['query' => ['custom' => 'param']]);
    $this->assertRaw('<a href="' . $webform_confirmation_inline->toUrl('canonical', ['absolute' => TRUE, 'query' => ['custom' => 'param']])->toString() . '" rel="prev" title="Back to form">Back to form</a>');
    $this->assertUrl('webform/test_confirmation_inline', ['query' => ['custom' => 'param']]);

    /* Test confirmation page (confirmation_type=page) */

    $webform_confirmation_page = Webform::load('test_confirmation_page');

    // Check confirmation page.
    $sid = $this->postSubmission($webform_confirmation_page);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertRaw('This is a custom confirmation page.');
    $this->assertRaw('<a href="' . $webform_confirmation_page->toUrl('canonical', ['absolute' => TRUE])->toString() . '" rel="prev" title="Back to form">Back to form</a>');
    $this->assertUrl('webform/test_confirmation_page/confirmation', ['query' => ['token' => $webform_submission->getToken()]]);

    // Check that the confirmation page's 'Back to form 'link includes custom
    // query parameters.
    $this->drupalGet('/webform/test_confirmation_page/confirmation', ['query' => ['custom' => 'param']]);

    // Check confirmation page with custom query parameters.
    $sid = $this->postSubmission($webform_confirmation_page, [], NULL, ['query' => ['custom' => 'param']]);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertUrl('webform/test_confirmation_page/confirmation', ['query' => ['custom' => 'param', 'token' => $webform_submission->getToken()]]);

    // Check confirmation page with token excluded.
    $webform_confirmation_page->setSetting('confirmation_exclude_token', TRUE);
    $webform_confirmation_page->save();
    $this->postSubmission($webform_confirmation_page, [], NULL, ['query' => ['custom' => 'param']]);
    $this->assertUrl('webform/test_confirmation_page/confirmation', ['query' => ['custom' => 'param']]);

    // Check confirmation page with token and query excluded.
    $webform_confirmation_page->setSetting('confirmation_exclude_query', TRUE);
    $webform_confirmation_page->save();
    $this->postSubmission($webform_confirmation_page);
    $this->assertUrl('webform/test_confirmation_page/confirmation');

    // TODO: (TESTING) Figure out why the inline confirmation link is not including the query string parameters.
    // $this->assertRaw('<a href="' . $webform_confirmation_page->toUrl()->toString() . '?custom=param">Back to form</a>');.

    /* Test confirmation page custom (confirmation_type=page) */

    $webform_confirmation_page_custom = Webform::load('test_confirmation_page_custom');

    // Check custom confirmation page.
    $this->postSubmission($webform_confirmation_page_custom);
    $this->assertRaw('<h1 class="page-title">Custom confirmation page title</h1>');
    $this->assertRaw('<div style="border: 10px solid red; padding: 1em;" class="webform-confirmation">');
    $this->assertRaw('<a href="' . $webform_confirmation_page_custom->toUrl()->setAbsolute()->toString() . '" rel="prev" title="Custom back to link" class="button">Custom back to link</a>');

    // Check back link is hidden.
    $webform_confirmation_page_custom->setSetting('confirmation_back', FALSE);
    $webform_confirmation_page_custom->save();
    $this->postSubmission($webform_confirmation_page_custom);
    $this->assertNoRaw('<a href="' . $webform_confirmation_page_custom->toUrl()->toString() . '" rel="prev" title="Custom back to link" class="button">Custom back to link</a>');

    /* Test confirmation URL (confirmation_type=url) */

    $webform_confirmation_url = Webform::load('test_confirmation_url');

    // Check confirmation URL.
    $this->postSubmission($webform_confirmation_url);
    $this->assertNoRaw('<h2 class="visually-hidden">Status message</h2>');
    $this->assertUrl('/');

    // Check confirmation URL using an internal: URI.
    $webform_confirmation_url
      ->setSetting('confirmation_url', 'internal:/some-internal-path')
      ->save();
    $this->postSubmission($webform_confirmation_url);
    $this->assertUrl('/some-internal-path');

    /* Test confirmation URL (confirmation_type=url_message) */

    $webform_confirmation_url_message = Webform::load('test_confirmation_url_message');

    // Check confirmation URL.
    $this->postSubmission($webform_confirmation_url_message);
    $this->assertRaw('<h2 class="visually-hidden">Status message</h2>');
    $this->assertRaw('This is a custom confirmation message.');
    $this->assertUrl('/');

    /* Test confirmation none (confirmation_type=none) */

    $this->drupalLogout();
    $webform_confirmation_url_message = Webform::load('test_confirmation_none');

    // Check no confirmation message.
    $this->postSubmission($webform_confirmation_url_message);
    $this->assertNoRaw('<h2 class="visually-hidden">Status message</h2>');

  }

}
