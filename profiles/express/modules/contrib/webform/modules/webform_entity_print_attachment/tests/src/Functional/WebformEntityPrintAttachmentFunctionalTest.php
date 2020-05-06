<?php

namespace Drupal\Tests\webform_entity_print_attachment\Functional;

use Drupal\Tests\webform_entity_print\Functional\WebformEntityPrintFunctionalTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform entity print attachment test.
 *
 * @group webform_browser
 */
class WebformEntityPrintAttachmentFunctionalTest extends WebformEntityPrintFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webform_entity_print_attachment_test'];

  /**
   * Test entity print attachment.
   */
  public function testEntityPrintAttachment() {
    $webform = Webform::load('test_entity_print_attachment');

    $this->drupalLogin($this->rootUser);

    /**************************************************************************/

    // Check that the PDF attachment is added to the sent email.
    $this->postSubmission($webform);
    $sent_email = $this->getLastEmail();
    $this->assertEquals('entity_print_pdf_html.pdf', $sent_email['params']['attachments'][0]['filename'], "The PDF attachment's file name");
    $this->assertEquals('application/pdf', $sent_email['params']['attachments'][0]['filemime'], "The PDF attachment's file mime type");
    $this->assertEquals('Using testprintengine', $sent_email['params']['attachments'][0]['filecontent'], "The attachment's file content");
  }

}
