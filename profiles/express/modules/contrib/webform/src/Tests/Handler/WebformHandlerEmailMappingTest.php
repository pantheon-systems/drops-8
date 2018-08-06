<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for email webform handler #options mapping functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailMappingTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_mapping'];

  /**
   * Test email mapping handler.
   */
  public function testEmailMapping() {
    $site_name = \Drupal::config('system.site')->get('name');
    $site_mail = \Drupal::config('system.site')->get('mail');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email_mapping');

    $this->postSubmission($webform);

    // Check that empty select menu email sent.
    $this->assertText("Select empty sent to empty@example.com from $site_name [$site_mail].");

    // Check that default select menu email sent.
    $this->assertText("Select default sent to default@default.com from $site_name [$site_mail].");

    // Check that no email sent.
    $this->assertText('Email not sent for Select yes option handler because a To, CC, or BCC email was not provided.');
    $this->assertText('Email not sent for Checkboxes handler because a To, CC, or BCC email was not provided.');
    $this->assertText('Email not sent for Radios other handler because a To, CC, or BCC email was not provided.');

    // Check that single select menu option email sent.
    $edit = [
      'select' => 'Yes',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertText("Select yes option sent to yes@example.com from $site_name [$site_mail].");
    $this->assertText("Select default sent to default@default.com from $site_name [$site_mail].");
    $this->assertNoText("'Select empty' sent to empty@example.com from $site_name [$site_mail].");

    // Check that multiple radios checked email sent.
    $edit = [
      'checkboxes[Saturday]' => TRUE,
      'checkboxes[Sunday]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertText("Checkboxes sent to saturday@example.com,sunday@example.com from $site_name [$site_mail].");
    $this->assertNoText('Email not sent for Checkboxes handler because a To, CC, or BCC email was not provided.');

    // Check that checkbxoes other option email sent.
    $edit = [
      'radios_other[radios]' => '_other_',
      'radios_other[other]' => '{Other}',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertText("Radios other sent to other@example.com from $site_name [$site_mail].");
    $this->assertNoText('Email not sent for Radios other handler because a To, CC, or BCC email was not provided.');
  }

}
