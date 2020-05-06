<?php

namespace Drupal\Tests\webform\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\webform\Access\WebformSubmissionAccess;

/**
 * @coversDefaultClass \Drupal\webform\Access\WebformSubmissionAccess
 *
 * @group webform
 */
class WebformSubmissionAccessTest extends WebformAccessTestBase {

  /**
   * Tests the check webform submission access.
   *
   * @covers ::checkResendAccess
   * @covers ::checkWizardPagesAccess
   */
  public function testWebformSubmissionAccess() {
    // Mock anonymous account.
    $anonymous_account = $this->mockAccount();

    // Mock submission account.
    $submission_account = $this->mockAccount([
      'access webform overview' => TRUE,
      'view any webform submission' => TRUE,
    ]);

    // Mock webform.
    $webform = $this->createMock('Drupal\webform\WebformInterface');

    // Mock webform submission.
    $webform_submission = $this->createMock('Drupal\webform\WebformSubmissionInterface');
    $webform_submission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($webform));

    // Mock message handler.
    $message_handler = $this->createMock('\Drupal\webform\Plugin\WebformHandlerMessageInterface');

    // Mock email webform.
    $email_webform = $this->createMock('Drupal\webform\WebformInterface');
    $email_webform->expects($this->any())
      ->method('getHandlers')
      ->will($this->returnValue([$message_handler]));
    $email_webform->expects($this->any())
      ->method('access')
      ->with('submission_update_any')
      ->will($this->returnValue(TRUE));
    $email_webform->expects($this->any())
      ->method('hasMessageHandler')
      ->will($this->returnValue(TRUE));

    // Mock email webform submission.
    $email_webform_submission = $this->createMock('Drupal\webform\WebformSubmissionInterface');
    $email_webform_submission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($email_webform));

    // Mock webform wizard.
    $webform_wizard = $this->createMock('Drupal\webform\WebformInterface');
    $webform_wizard->expects($this->any())
      ->method('hasWizardPages')
      ->will($this->returnValue(TRUE));

    // Mock webform wizard submission.
    $webform_wizard_submission = $this->createMock('Drupal\webform\WebformSubmissionInterface');
    $webform_wizard_submission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($webform_wizard));

    /**************************************************************************/

    // Check resend (email) message access.
    $this->assertEquals(AccessResult::forbidden(), WebformSubmissionAccess::checkResendAccess($webform_submission, $anonymous_account));
    $this->assertEquals(AccessResult::allowed(), WebformSubmissionAccess::checkResendAccess($email_webform_submission, $submission_account));

    // Check wizard page access.
    $this->assertEquals(AccessResult::neutral(), WebformSubmissionAccess::checkWizardPagesAccess($webform_submission));
    $this->assertEquals(AccessResult::allowed(), WebformSubmissionAccess::checkWizardPagesAccess($webform_wizard_submission));

  }

}
