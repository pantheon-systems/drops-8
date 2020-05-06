<?php

namespace Drupal\Tests\webform_attachment\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform_attachment\Element\WebformAttachmentToken;

/**
 * Tests for webform example element.
 *
 * @group Webform
 */
class WebformAttachmentTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['token', 'webform_attachment', 'webform_attachment_test'];

  /**
   * Tests webform attachment.
   */
  public function testWebformAttachment() {
    global $base_url;

    $this->drupalLogin($this->rootUser);

    /**************************************************************************/
    // Email.
    /**************************************************************************/

    $webform_id = 'test_attachment_email';
    $webform_attachment_email = Webform::load($webform_id);
    $attachment_date = date('Y-m-d');

    // Check that the attachment is added to the sent email.
    $sid = $this->postSubmission($webform_attachment_email);
    $sent_email = $this->getLastEmail();
    $this->assertEqual($sent_email['params']['attachments'][0]['filename'], "attachment_token-$attachment_date.xml", "The attachment's file name");
    $this->assertEqual($sent_email['params']['attachments'][0]['filemime'], 'application/xml', "The attachment's file mime type");
    $this->assertEqual($sent_email['params']['attachments'][0]['filecontent'], "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<asx:abap xmlns:asx=\"http://www.sap.com/abapxml\" version=\"1.0\">
   <asx:values>
      <VERSION>1.0</VERSION>
      <SENDER>johnsmith@example.com</SENDER>
      <WEBFORM_ID>test_attachment_email</WEBFORM_ID>
      <SOURCE>
         <o2PARAVALU>
            <NAME>Lastname</NAME>
            <VALUE>Smith</VALUE>
         </o2PARAVALU>
         <o2PARAVALU>
            <NAME>Firstname</NAME>
            <VALUE>John</VALUE>
         </o2PARAVALU>
         <o2PARAVALU>
            <NAME>Emailaddress</NAME>
            <VALUE>johnsmith@example.com</VALUE>
         </o2PARAVALU>
      </SOURCE>
   </asx:values>
</asx:abap>", "The attachment's file content");

    // Check access to the attachment.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/attachment_token/attachment_token-$attachment_date.xml");
    $this->assertResponse(200, 'Access allowed to the attachment');

    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/attachment_token/any-file-name.text");
    $this->assertResponse(200, 'Access allowed to the attachment with any file name,');

    $this->drupalGet("/webform/not_a_webform/submissions/$sid/attachment/attachment/any-file-name.text");
    $this->assertResponse(404, 'Page not found to not a webform');

    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/email/attachment-$attachment_date.xml");
    $this->assertResponse(404, 'Page not found when not an attachment element is specified');

    /**************************************************************************/
    // Token.
    /**************************************************************************/

    $webform_id = 'test_attachment_token';
    $webform_attachment_token = Webform::load('test_attachment_token');

    $sid = $this->postSubmissionTest($webform_attachment_token, ['textfield' => 'Some text']);

    // Check that both attachments are displayed on the results page.
    $this->drupalGet('/admin/structure/webform/manage/test_attachment_token/results/submissions');
    $this->assertRaw('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token/test_token.txt">test_token.txt</a></td>');
    $this->assertRaw('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token_download/test_token.txt">Download</a></td>');

    // Check that only the download attachment is displayed on
    // the submission page.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_token/submission/$sid");
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token/test_token.txt">test_token.txt</a>');
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token_download/test_token.txt">Download</a>');

    // Check the attachment's content.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/webform_attachment_token_download/test_token.txt");
    $this->assertRaw('textfield: Some text');

    /**************************************************************************/
    // Twig.
    /**************************************************************************/

    $webform_id = 'test_attachment_twig';
    $webform_attachment_twig = Webform::load('test_attachment_twig');

    $sid = $this->postSubmissionTest($webform_attachment_twig, ['textfield' => 'Some text']);

    // Check that both attachments are displayed on the results page.
    $this->drupalGet('/admin/structure/webform/manage/test_attachment_twig/results/submissions');
    $this->assertRaw('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig/test_twig.xml">test_twig.xml</a></td>');
    $this->assertRaw('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig_download/test_twig.xml">Download</a></td>');

    // Check that only the download attachment is displayed on
    // the submission page.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_twig/submission/$sid");
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig/test_twig.txt">test_twig.xml</a>');
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig_download/test_twig.xml">Download</a>');

    // Check the attachment's content.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/webform_attachment_twig_download/test_twig.xml");
    $this->assertRaw('<?xml version="1.0"?>
<textfield>Some text</textfield>');

    /**************************************************************************/
    // URL.
    /**************************************************************************/

    $webform_id = 'test_attachment_url';
    $webform_attachment_url = Webform::load('test_attachment_url');

    $sid = $this->postSubmissionTest($webform_attachment_url);

    // Check that both attachments are displayed on the results page.
    $this->drupalGet('/admin/structure/webform/manage/test_attachment_url/results/submissions');
    $this->assertRaw('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url/MAINTAINERS.txt">MAINTAINERS.txt</a></td>');
    $this->assertRaw('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_path/durpalicon.png">durpalicon.png</a></td>');
    $this->assertRaw('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url_download/MAINTAINERS.txt">Download</a></td>');

    // Check that only the download attachment is displayed on
    // the submission page.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url/MAINTAINERS.txt">MAINTAINERS.txt</a>');
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_path/durpalicon.png">durpalicon.png</a>');
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url_download/MAINTAINERS.txt">Download</a>');

    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url/MAINTAINERS.txt">MAINTAINERS.txt</a>');
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url_download/MAINTAINERS.txt">Download</a>');

    // Check the attachment's content.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/webform_attachment_url_download/MAINTAINERS.txt");
    $this->assertRaw('https://www.drupal.org/contribute');

    /**************************************************************************/
    // Access.
    /**************************************************************************/

    // Switch to anonymous user.
    $this->drupalLogout();

    $webform_id = 'test_attachment_access';
    $webform_attachment_access = Webform::load('test_attachment_access');
    $sid = $this->postSubmission($webform_attachment_access);
    $webform_submission = WebformSubmission::load($sid);

    // Check access to anonymous attachment allowed via $element access rules.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/anonymous/anonymous.txt">anonymous.txt</a>');
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/authenticated/authenticated.txt">authenticated.txt</a>');
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/private/private.txt">private.txt</a>');

    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/anonymous/anonymous.txt");
    $this->assertResponse(200, 'Access allowed to anonymous.txt');
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/authenticated/authenticated.txt");
    $this->assertResponse(403, 'Access denied to authenticated.txt');
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/private/private.txt");
    $this->assertResponse(403, 'Access denied to private.txt');

    // Switch to authenticated user and set user as the submission's owner.
    $account = $this->createUser();
    $webform_submission->setOwnerId($account->id())->save();
    $this->drupalLogin($account);

    // Check access to authenticated attachment allowed via $element access rules.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/anonymous/anonymous.txt">anonymous.txt</a>');
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/authenticated/authenticated.txt">authenticated.txt</a>');
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/private/private.txt">private.txt</a>');

    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/anonymous/anonymous.txt");
    $this->assertResponse(403, 'Access denied to anonymous.txt');
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/authenticated/authenticated.txt");
    $this->assertResponse(200, 'Access allow to authenticated.txt');
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/private/private.txt");
    $this->assertResponse(403, 'Access denied to private.txt');

    // Switch to admin user.
    $this->drupalLogin($this->rootUser);

    // Check access to all attachment allowed for admin.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $this->assertNoRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/anonymous/anonymous.txt">anonymous.txt</a>');
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/authenticated/authenticated.txt">authenticated.txt</a>');
    $this->assertRaw('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/private/private.txt">private.txt</a>');

    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/anonymous/anonymous.txt");
    $this->assertResponse(403, 'Access denied to anonymous.txt');
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/authenticated/authenticated.txt");
    $this->assertResponse(200, 'Access allowed to authenticated.txt');
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/private/private.txt");
    $this->assertResponse(200, 'Access allowed to private.txt');

    /**************************************************************************/
    // Sanitize.
    /**************************************************************************/

    $webform_attachment_santize = Webform::load('test_attachment_sanitize');

    $sid = $this->postSubmissionTest($webform_attachment_santize, ['textfield' => 'Some text!@#$%^&*)']);
    $webform_submission = WebformSubmission::load($sid);
    $element = $webform_attachment_santize->getElement('webform_attachment_token');
    $this->assertEqual(WebformAttachmentToken::getFileName($element, $webform_submission), 'some-text.txt');

    /**************************************************************************/
    // States (enabled/disabled).
    /**************************************************************************/

    $webform_id = 'test_attachment_states';
    $webform_attachment_states = Webform::load($webform_id);

    // Check that attachment is enabled.
    $this->postSubmission($webform_attachment_states, ['attach' => TRUE]);
    $sent_email = $this->getLastEmail();
    $this->assert(isset($sent_email['params']['attachments'][0]), 'Attachment enabled via #states');

    // Check that attachment is disabled.
    $this->postSubmission($webform_attachment_states, ['attach' => FALSE]);
    $sent_email = $this->getLastEmail();
    $this->assert(!isset($sent_email['params']['attachments'][0]), 'Attachment disabled via #states');
  }

}
