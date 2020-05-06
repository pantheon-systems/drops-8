<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\webform\Element\WebformSelectOther;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for basic email webform handler functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailBasicTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email'];

  /**
   * Test basic email handler.
   */
  public function testBasicEmailHandler() {
    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email');

    /**************************************************************************/

    // Create a submission using the test webform's default values.
    $this->postSubmission($webform);

    // Check sending a basic email via a submission.
    $sent_email = $this->getLastEmail();
    $this->assertEqual($sent_email['key'], 'test_handler_email_email');
    $this->assertEqual($sent_email['reply-to'], "John Smith <from@example.com>");
    $this->assertContains('Submitted by: Anonymous', $sent_email['body']);
    $this->assertContains('First name: John', $sent_email['body']);
    $this->assertContains('Last name: Smith', $sent_email['body']);
    $this->assertEqual($sent_email['headers']['From'], 'John Smith <from@example.com>');
    $this->assertEqual($sent_email['headers']['Cc'], 'cc@example.com');
    $this->assertEqual($sent_email['headers']['Bcc'], 'bcc@example.com');

    // Check sending a basic email via a submission.
    $sent_email = $this->getLastEmail();
    $this->assertEqual($sent_email['reply-to'], "John Smith <from@example.com>");

    // Check sending with the saving of results disabled.
    $webform->setSetting('results_disabled', TRUE)->save();
    $this->postSubmission($webform, ['first_name' => 'Jane', 'last_name' => 'Doe']);
    $sent_email = $this->getLastEmail();
    $this->assertContains('First name: Jane', $sent_email['body']);
    $this->assertContains('Last name: Doe', $sent_email['body']);
    $webform->setSetting('results_disabled', FALSE)->save();

    // Check sending a custom email using tokens.
    $this->drupalLogin($admin_user);
    $body = implode(PHP_EOL, [
      'full name: [webform_submission:values:first_name] [webform_submission:values:last_name]',
      'uuid: [webform_submission:uuid]',
      'sid: [webform_submission:sid]',
      'date: [webform_submission:created]',
      'ip-address: [webform_submission:ip-address]',
      'user: [webform_submission:user]',
      'url: [webform_submission:url]',
      'edit-url: [webform_submission:url:edit-form]',
      'Test that "double quotes" are not encoded.',
    ]);

    $this->drupalPostForm('/admin/structure/webform/manage/test_handler_email/handlers/email/edit', ['settings[body]' => WebformSelectOther::OTHER_OPTION, 'settings[body_custom_text]' => $body], t('Save'));

    $sid = $this->postSubmission($webform);
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::load($sid);

    $sent_email = $this->getLastEmail();
    $this->assertContains('full name: John Smith', $sent_email['body']);
    $this->assertContains('uuid: ' . $webform_submission->uuid->value, $sent_email['body']);
    $this->assertContains('sid: ' . $sid, $sent_email['body']);
    $date_value = \Drupal::service('date.formatter')->format($webform_submission->created->value, 'medium');
    $this->assertContains('date: ' . $date_value, $sent_email['body']);
    $this->assertContains('ip-address: ' . $webform_submission->remote_addr->value, $sent_email['body']);
    $this->assertContains('user: ' . $admin_user->label(), $sent_email['body']);
    $this->assertContains("url:", $sent_email['body']);
    $this->assertContains($webform_submission->toUrl('canonical', ['absolute' => TRUE])
      ->toString(), $sent_email['body']);
    $this->assertContains("edit-url:", $sent_email['body']);
    $this->assertContains($webform_submission->toUrl('edit-form', ['absolute' => TRUE])
      ->toString(), $sent_email['body']);
    $this->assertContains('Test that "double quotes" are not encoded.', $sent_email['body']);

    // Create a submission using HTML is subject and message.
    $edit = [
      'settings[subject][select]' => '[webform_submission:values:subject:raw]',
      'settings[body]' => '_other_',
      'settings[body_custom_text]' => '[webform_submission:values][webform_submission:values:message:value]',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_handler_email/handlers/email/edit', $edit, t('Save'));

    // Check special characters in message value.
    $edit = [
      'first_name' => '"<first_name>"',
      'last_name' => '"<last_name>"',
      // Drupal strip_tags() from mail subject.
      // @see \Drupal\Core\Mail\MailManager::doMail
      // @see http://cgit.drupalcode.org/drupal/tree/core/lib/Drupal/Core/Mail/MailManager.php#n285
      'subject' => 'This has <removed> & "special" \'characters\'',
      'message' => 'This has <not_removed> & "special" \'characters\'',
    ];
    $this->postSubmission($webform, $edit);
    $sent_email = $this->getLastEmail();
    $this->assertEqual($sent_email['reply-to'], '"first_name\\" \\"last_name" <from@example.com>');
    $this->assertEqual($sent_email['subject'], 'This has  & "special" \'characters\'');
    // NOTE:
    // Drupal's PhpMail::format function calls
    // MailFormatHelper::htmlToText which strips out all unrecognized HTML tags.
    // @see \Drupal\Core\Mail\Plugin\Mail\PhpMail
    //
    // The Webform module provides its own Mail handler which does
    // convert and strip HTML tags.
    // @see \Drupal\webform\Plugin\Mail\WebformPhpMail
    $this->assertEqual($sent_email['body'], 'First name: ""
Last name: ""
Email: from@example.com
Subject: This has  & "special" \'characters\'
Message:
This has  & "special" \'characters\'

This has  & "special" \'characters\'
');
    // Instead we are going to check params body.
    $this->assertEqual($sent_email['params']['body'], 'First name: "<first_name>"
Last name: "<last_name>"
Email: from@example.com
Subject: This has <removed> & "special" \'characters\'
Message:
This has <not_removed> & "special" \'characters\'

This has <not_removed> & "special" \'characters\'');
  }

}
