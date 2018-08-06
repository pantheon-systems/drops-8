<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for remote post webform handler functionality.
 *
 * @group Webform
 */
class WebformHandlerRemotePostTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_handler_remote_post'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_remote_post'];

  /**
   * Test remote post handler.
   */
  public function testRemotePostHandler() {
    $this->drupalLogin($this->rootUser);

    /**************************************************************************/
    // POST.
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_post');

    // Check 'completed' operation.
    $sid = $this->postSubmission($webform);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertRaw("form_params:
  custom_completed: true
  custom_data: true
  response_type: '200'
  first_name: John
  last_name: Smith");
    $this->assertRaw('Processed completed request.');

    // Check confirmation number is set via the
    // [webform:handler:remote_post:completed:confirmation_number] token.
    $this->assertRaw('Your confirmation number is ' . $webform_submission->getElementData('confirmation_number') . '.');

    // Check custom header.
    $this->assertRaw('{&quot;custom_header&quot;:&quot;true&quot;}');

    // Sleep for 1 second to make sure submission timestamp is updated.
    sleep(1);

    // Check 'updated' operation.
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_remote_post/submission/$sid/edit", [], t('Save'));
    $this->assertRaw("form_params:
  custom_updated: true
  custom_data: true
  response_type: '200'
  first_name: John
  last_name: Smith");
    $this->assertRaw('Processed updated request.');

    // Check 'deleted`' operation.
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_remote_post/submission/$sid/delete", [], t('Delete'));
    $this->assertRaw("form_params:
  custom_deleted: true
  custom_data: true
  first_name: John
  last_name: Smith
  response_type: '200'");
    $this->assertRaw('Processed deleted request.');

    // Switch anonymous user.
    $this->drupalLogout();

    // Check 'draft' operation.
    $this->postSubmission($webform, [], t('Save Draft'));
    $this->assertRaw("form_params:
  custom_draft: true
  custom_data: true
  response_type: '200'
  first_name: John
  last_name: Smith");
    $this->assertRaw('Processed draft request.');

    // Login root user.
    $this->drupalLogin($this->rootUser);

    // Check 'convert' operation.
    $this->assertRaw("form_params:
  custom_converted: true
  custom_data: true
  first_name: John
  last_name: Smith
  response_type: '200'");
    $this->assertRaw('Processed converted request.');

    // Check excluded data.
    $handler = $webform->getHandler('remote_post');
    $configuration = $handler->getConfiguration();
    $configuration['settings']['excluded_data'] = [
      'last_name' => 'last_name',
    ];
    $handler->setConfiguration($configuration);
    $webform->save();
    $sid = $this->postSubmission($webform);
    $this->assertRaw('first_name: John');
    $this->assertNoRaw('last_name: Smith');
    $this->assertRaw("sid: '$sid'");

    // Check 500 Internal Server Error.
    $this->postSubmission($webform, ['response_type' => '500']);
    $this->assertRaw('Failed to process completed request.');

    // Check 404 Not Found.
    $this->postSubmission($webform, ['response_type' => '404']);
    $this->assertRaw('File not found');

    // Disable saving of results
    $webform->setSetting('results_disabled', TRUE);
    $webform->save();

    // Check confiramtion number when results disabled.
    $sid = $this->postSubmission($webform);
    $this->assertNull($sid);

    // Get confirmation number from JSON packet.
    preg_match('/&quot;confirmation_number&quot;:&quot;([a-zA-z0-9]+)&quot;/', $this->getRawContent(), $match);
    $this->assertRaw('Your confirmation number is ' . $match[1] . '.');

    /**************************************************************************/
    // GET.
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_get');

    $this->postSubmission($webform);

    // Check request URL contains query string.
    $this->assertRaw("http://webform-test-handler-remote-post/completed?custom_completed=1&amp;custom_data=1&amp;response_type=200&amp;first_name=John&amp;last_name=Smith");

    // Check response data.
    $this->assertRaw("message: 'Processed completed?custom_completed=1&amp;custom_data=1&amp;response_type=200&amp;first_name=John&amp;last_name=Smith request.'");

    // Get confirmation number from JSON packet.
    preg_match('/&quot;confirmation_number&quot;:&quot;([a-zA-z0-9]+)&quot;/', $this->getRawContent(), $match);
    $this->assertRaw('Your confirmation number is ' . $match[1] . '.');
  }

}
