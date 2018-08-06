<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for webform submission API.
 *
 * @group Webform
 */
class WebformSubmissionApiTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_advanced', 'test_form_limit'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test webform API.
   */
  public function testApi() {

    /**************************************************************************/
    // Basic form.
    /**************************************************************************/

    $contact_webform = Webform::load('contact');

    // Check submitting a simple webform.
    $values = [
      'webform_id' => 'contact',
      'data' => [
        'name' => 'Dixisset',
        'company' => 'Dixisset',
        'email' => 'test@test.com',
        'subject' => 'Testing contact webform from [site:name]',
        'message' => 'Please ignore this email.',
      ],
    ];
    $webform_submission = WebformSubmissionForm::submitValues($values);
    $this->assertEqual($webform_submission->id(), $this->getLastSubmissionId($contact_webform));

    // Check validating a simple webform.
    $values = [
      'webform_id' => 'contact',
      'data' => [
        'email' => 'invalid',
      ],
    ];
    $errors = WebformSubmissionForm::validateValues($values);
    WebformElementHelper::convertRenderMarkupToStrings($errors);
    $this->assertEqual($errors, [
      'name' => 'Your Name field is required.',
      'email' => 'The email address <em class="placeholder">invalid</em> is not valid.',
      'subject' => 'Subject field is required.',
      'message' => 'Message field is required.',
    ]);

    // Check validation occurs for drafts simple webform.
    $values = [
      'webform_id' => 'contact',
      'in_draft' => TRUE,
      'data' => [],
    ];
    $errors = WebformSubmissionForm::validateValues($values);
    if ($errors) {
      WebformElementHelper::convertRenderMarkupToStrings($errors);
    }
    $this->assertEqual($errors, [
      'name' => 'Your Name field is required.',
      'email' => 'Your Email field is required.',
      'subject' => 'Subject field is required.',
      'message' => 'Message field is required.',
    ]);

    // Check validation is skipped when saving drafts simple webform.
    $values = [
      'webform_id' => 'contact',
      'in_draft' => TRUE,
      'data' => [],
    ];
    $webform_submission = WebformSubmissionForm::submitValues($values);
    $this->assert($webform_submission instanceof WebformSubmissionInterface);

    /**************************************************************************/
    // Multistep form.
    /**************************************************************************/

    $test_form_wizard_advanced_webform = Webform::load('test_form_wizard_advanced');

    // Check submitting a multistep form with required fields.
    $values = [
      'webform_id' => 'test_form_wizard_advanced',
      'data' => [
        'first_name' => 'Ringo',
        'last_name' => 'Starr',
        'gender' => 'Male',
        'email' => 'example@example.com',
        'phone' => '123-456-7890',
        'comments' => 'Huius, Lyco, oratione locuples, rebus ipsis ielunior. Duo Reges: constructio interrete. Sed haec in pueris; Sed utrum hortandus es nobis, Luci, inquit, an etiam tua sponte propensus es? Sapiens autem semper beatus est et est aliquando in dolore; Immo videri fortasse. Paulum, cum regem Persem captum adduceret, eodem flumine invectio? Et ille ridens: Video, inquit, quid agas;',
      ],
    ];
    $webform_submission = WebformSubmissionForm::submitValues($values);
    $this->assertEqual($webform_submission->id(), $this->getLastSubmissionId($test_form_wizard_advanced_webform));

    // Check validating a multistep form with required fields.
    $values = [
      'webform_id' => 'test_form_wizard_advanced',
      'data' => [
        'email' => 'invalid',
      ],
    ];
    $errors = WebformSubmissionForm::validateValues($values);
    WebformElementHelper::convertRenderMarkupToStrings($errors);
    // $this->debug($errors);
    $this->assertEqual($errors, [
      'email' => 'The email address <em class="placeholder">invalid</em> is not valid.',
    ]);

    // Check validating a multistep form with invalid #options.
    $values = [
      'webform_id' => 'test_form_wizard_advanced',
      'data' => [
        'first_name' => 'Ringo',
        'last_name' => 'Starr',
        'gender' => 'INVALID',
        'email' => 'example@example.com',
        'phone' => '123-456-7890',
        'comments' => 'Huius, Lyco, oratione locuples, rebus ipsis ielunior. Duo Reges: constructio interrete. Sed haec in pueris; Sed utrum hortandus es nobis, Luci, inquit, an etiam tua sponte propensus es? Sapiens autem semper beatus est et est aliquando in dolore; Immo videri fortasse. Paulum, cum regem Persem captum adduceret, eodem flumine invectio? Et ille ridens: Video, inquit, quid agas;',
      ],
    ];
    $errors = WebformSubmissionForm::validateValues($values);
    WebformElementHelper::convertRenderMarkupToStrings($errors);
    // $this->debug($errors);
    $this->assertEqual($errors, [
        'gender' => 'An illegal choice has been detected. Please contact the site administrator.',
    ]);

    /**************************************************************************/
    // Submission limit form.
    /**************************************************************************/

    $this->drupalLogin($this->normalUser);

    $test_form_limit_webform = Webform::load('test_form_limit');

    // Check that the form is open.
    $this->assertTrue(WebformSubmissionForm::isOpen($test_form_limit_webform));

    // Check submitting a form limited to 1 submission per user.
    $values = [
      'webform_id' => 'test_form_limit',
      'data' => [
        'name' => 'Oratione',
      ],
    ];
    $webform_submission = WebformSubmissionForm::submitValues($values);
    $this->assertEqual($webform_submission->id(), $this->getLastSubmissionId($test_form_limit_webform));

    // Check that user limit is reached.
    $result = WebformSubmissionForm::isOpen($test_form_limit_webform);
    $this->assertEqual($result['#markup'], 'You are only allowed to have 1 submission for this webform.');

    // Submit the form 3 more times to trigger the form total limit.
    $this->drupalLogin($this->rootUser);
    WebformSubmissionForm::submitValues($values);
    WebformSubmissionForm::submitValues($values);
    WebformSubmissionForm::submitValues($values);

    // Check that total limit is reached.
    $result = WebformSubmissionForm::isOpen($test_form_limit_webform);
    $this->assertEqual($result['#markup'], 'Only 4 submissions are allowed.');

    // Check form closed message.
    $test_form_limit_webform->setStatus(FALSE)->save();
    $result = WebformSubmissionForm::isOpen($test_form_limit_webform);
    $this->assertEqual($result['#markup'], 'Sorry...This form is closed to new submissions.');
  }

}
