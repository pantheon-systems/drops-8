<?php

namespace Drupal\honeypot\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test programmatic submission of forms protected by Honeypot.
 *
 * @group honeypot
 */
class HoneypotFormProgrammaticSubmissionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['honeypot', 'honeypot_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up required Honeypot configuration.
    $honeypot_config = \Drupal::configFactory()->getEditable('honeypot.settings');
    $honeypot_config->set('element_name', 'url');
    $honeypot_config->set('time_limit', 5);
    $honeypot_config->set('protect_all_forms', TRUE);
    $honeypot_config->set('log', FALSE);
    $honeypot_config->save();

    $this->drupalCreateUser([], 'robo-user');
  }

  /**
   * Trigger a programmatic form submission and verify the validation errors.
   */
  public function testProgrammaticFormSubmission() {
    $result = $this->drupalGet('/honeypot_test/submit_form');
    $form_errors = (array) json_decode($result);
    $this->assertNoRaw('There was a problem with your form submission. Please wait 6 seconds and try again.');
    $this->assertFalse($form_errors, 'The were no validation errors when submitting the form.');
  }

}
