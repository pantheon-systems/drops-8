<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for email webform handler Twig functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailTwigTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_twig'];

  /**
   * Test email twig handler.
   */
  public function testEmailTwigHandler() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email_twig');

    // Create a submission using the test webform's default values.
    $this->postSubmission($webform);

    // Check sending a basic email via a submission.
    $sent_email = $this->getLastEmail();
    $this->assertEqual($sent_email['params']['body'], '<p>Submitted values are:</p>
  <b>First name</b><br />John<br /><br />

  <b>Last name</b><br />Smith<br /><br />

  <b>Email</b><br /><a href="mailto:from@example.com">from@example.com</a><br /><br />

  <b>Subject</b><br />{subject}<br /><br />

  <b>Message</b><br />{message}<br /><br />');

  }

}
