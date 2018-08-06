<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for advanced email webform handler functionality with HTML and attachments.
 *
 * @group Webform
 */
class WebformHandlerEmailAdvancedTest extends WebformTestBase {

  public static $modules = ['filter', 'file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_advanced'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Create webform test users.
   */
  protected function createUsers() {
    // Create filter.
    $this->createFilters();

    $this->normalUser = $this->drupalCreateUser([
      'access user profiles',
      $this->basicHtmlFilter->getPermissionName(),
    ]);
    $this->adminSubmissionUser = $this->drupalCreateUser([
      'access user profiles',
      'administer webform submission',
      $this->basicHtmlFilter->getPermissionName(),
    ]);
  }

  /**
   * Test advanced email handler.
   *
   * Note:
   * The TestMailCollector extends PhpMail, therefore the HTML body
   * will still be escaped, which is why we are looking at the params.body.
   *
   * @see \Drupal\Core\Mail\Plugin\Mail\TestMailCollector
   */
  public function testAdvancedEmailHandler() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email_advanced');

    // Generate a test submission with a file upload.
    $this->drupalLogin($this->rootUser);

    // Check handler's custom reply to and return path.
    $this->postSubmissionTest($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertEqual($sent_mail['headers']['Return-Path'], 'return_path@example.com');
    $this->assertEqual($sent_mail['headers']['Sender'], 'sender_name <sender_mail@example.com>');
    $this->assertEqual($sent_mail['headers']['Reply-to'], 'reply_to@example.com');

    $email_handler = $webform->getHandler('email');
    $configuration = $email_handler->getConfiguration();
    $configuration['settings']['reply_to'] = '';
    $configuration['settings']['return_path'] = '';
    $configuration['settings']['sender_mail'] = '';
    $configuration['settings']['sender_name'] = '';
    $email_handler->setConfiguration($configuration);
    $webform->save();

    // Check no custom reply to and return path.
    $this->postSubmissionTest($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertNotEqual($sent_mail['headers']['Return-Path'], 'return_path@example.com');
    $this->assertNotEqual($sent_mail['headers']['Sender'], 'sender_name <sender_mail@example.com>');
    $this->assertNotEqual($sent_mail['headers']['Reply-to'], 'reply_to@example.com');
    $this->assertEqual($sent_mail['headers']['Return-Path'], $sent_mail['params']['from_mail']);
    $this->assertEqual($sent_mail['headers']['Sender'], $sent_mail['params']['from_mail']);
    $this->assertEqual($sent_mail['headers']['Reply-to'], $sent_mail['headers']['From']);

    // Check site wide reply to and return path.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('mail.default_reply_to', 'default_reply_to@example.com')
      ->set('mail.default_return_path', 'default_return_path@example.com')
      ->save();
    $this->postSubmissionTest($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertEqual($sent_mail['headers']['Return-Path'], 'default_return_path@example.com');
    $this->assertEqual($sent_mail['headers']['Sender'], 'default_return_path@example.com');
    $this->assertEqual($sent_mail['headers']['Reply-to'], 'default_reply_to@example.com');

    // Check site wide sender mail and name.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('mail.default_sender_mail', 'default_sender_mail@example.com')
      ->set('mail.default_sender_name', 'Default Sender Name')
      ->save();
    $this->postSubmissionTest($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertEqual($sent_mail['headers']['Sender'], 'Default Sender Name <default_sender_mail@example.com>');

    // Post a new submission using test webform which will automatically
    // upload file.txt.
    $edit = [
      'first_name' => 'John',
      'last_name' => 'Smith',
      'email' => 'from@example.com',
      // Drupal strip_tags() from mail subject.
      // @see \Drupal\Core\Mail\MailManager::doMail
      // @see http://cgit.drupalcode.org/drupal/tree/core/lib/Drupal/Core/Mail/MailManager.php#n285
      'subject' => 'This has <removed>"special" \'chararacters\'',
      'message[value]' => '<p><em>Please enter a message.</em> Test that double "quotes" are not encoded.</p>',
      'optional' => '',
    ];
    $this->postSubmissionTest($webform, $edit);
    $sid = $this->getLastSubmissionId($webform);
    $sent_mail = $this->getLastEmail();

    // Check email subject with special characters.
    $this->assertEqual($sent_mail['subject'], 'This has "special" \'chararacters\'');

    // Check email body is HTML.
    $this->assertContains($sent_mail['params']['body'], '<b>First name</b><br />John<br /><br />');
    $this->assertContains($sent_mail['params']['body'], '<b>Last name</b><br />Smith<br /><br />');
    $this->assertContains($sent_mail['params']['body'], '<b>Email</b><br /><a href="mailto:from@example.com">from@example.com</a><br /><br />');
    $this->assertContains($sent_mail['params']['body'], '<b>Subject</b><br />This has &lt;removed&gt;&quot;special&quot; &#039;chararacters&#039;<br /><br />');
    $this->assertContains($sent_mail['params']['body'], '<b>Message</b><br /><p><em>Please enter a message.</em> Test that double "quotes" are not encoded.</p><br /><br />');
    $this->assertContains($sent_mail['params']['body'], '<p style="color:yellow"><em>Custom styled HTML markup</em></p>');
    $this->assertNotContains($sent_mail['params']['body'], '<b>Optional</b><br />{Empty}<br /><br />');

    // Check email has attachment.
    $this->assertEqual($sent_mail['params']['attachments'][0]['filecontent'], "this is a sample txt file\nit has two lines\n");
    $this->assertEqual($sent_mail['params']['attachments'][0]['filename'], 'file.txt');
    $this->assertEqual($sent_mail['params']['attachments'][0]['filemime'], 'text/plain');

    // Check resend webform includes link to the attachment.
    $this->drupalGet("admin/structure/webform/manage/test_handler_email_advanced/submission/$sid/resend");
    $this->assertRaw('<span class="file file--mime-text-plain file--text">');
    $this->assertRaw('file.txt');

    // Check resend webform with custom message.
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_email_advanced/submission/$sid/resend", ['message[body][value]' => 'Testing 123...'], t('Resend message'));
    $sent_mail = $this->getLastEmail();
    $this->assertNotContains($sent_mail['params']['body'], '<b>First name</b><br />John<br /><br />');
    $this->assertEqual($sent_mail['params']['body'], 'Testing 123...');

    // Check resent email has the same attachment.
    $this->assertEqual($sent_mail['params']['attachments'][0]['filecontent'], "this is a sample txt file\nit has two lines\n");
    $this->assertEqual($sent_mail['params']['attachments'][0]['filename'], 'file.txt');
    $this->assertEqual($sent_mail['params']['attachments'][0]['filemime'], 'text/plain');

    $email_handler = $webform->getHandler('email');

    // Exclude file element.
    $configuration = $email_handler->getConfiguration();
    $configuration['settings']['excluded_elements'] = ['file' => 'file'];
    $email_handler->setConfiguration($configuration);
    $webform->save();

    // Check excluding files.
    $this->postSubmissionTest($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertFalse(isset($sent_mail['params']['attachments'][0]['filecontent']));

    // Check empty element is excluded.
    $this->postSubmission($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertNotContains($sent_mail['params']['body'], '<b>Optional</b><br />{Empty}<br /><br />');

    // Include empty.
    $configuration = $email_handler->getConfiguration();
    $configuration['settings']['exclude_empty'] = FALSE;
    $email_handler->setConfiguration($configuration);
    $webform->save();

    // Check empty included.
    $this->postSubmission($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertContains($sent_mail['params']['body'], '<b>Optional</b><br />{Empty}<br /><br />');

    // Logut and use anonymous user account.
    $this->drupalLogout();

    // Check that private is include in email because 'ignore_access' is TRUE.
    $this->postSubmission($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertContains($sent_mail['params']['body'], '<b>Notes</b><br />These notes are private.<br /><br />');

    // Disable ignore_access.
    $email_handler = $webform->getHandler('email');
    $configuration = $email_handler->getConfiguration();
    $configuration['settings']['ignore_access'] = FALSE;
    $email_handler->setConfiguration($configuration);
    $webform->save();

    // Check that private is excluded from email because 'ignore_access' is FALSE.
    $this->postSubmission($webform);
    $sent_mail = $this->getLastEmail();
    $this->assertNotContains($sent_mail['params']['body'], '<b>Notes</b><br />These notes are private.<br /><br />');
  }

}
