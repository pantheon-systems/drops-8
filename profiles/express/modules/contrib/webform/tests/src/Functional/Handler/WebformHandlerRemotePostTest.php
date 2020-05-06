<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for remote post webform handler functionality.
 *
 * @group Webform
 */
class WebformHandlerRemotePostTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform', 'webform_test_handler_remote_post'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_handler_remote_post',
    'test_handler_remote_put',
    'test_handler_remote_get',
    'test_handler_remote_post_file',
    'test_handler_remote_post_cast',
  ];

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

    // Check POST response.
    $this->assertRaw("method: post
status: success
message: 'Processed completed request.'
options:
  headers:
    Accept-Language: en
    custom_header: 'true'
  form_params:
    custom_completed: true
    custom_data: true
    response_type: '200'
    first_name: John
    last_name: Smith");

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
    $this->assertRaw('{&quot;headers&quot;:{&quot;Accept-Language&quot;:&quot;en&quot;,&quot;custom_header&quot;:&quot;true&quot;}');

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
  custom_draft_created: true
  custom_data: true
  response_type: '200'
  first_name: John
  last_name: Smith");
    $this->assertRaw('Processed draft_created request.');

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
    $this->assertNoRaw('Unable to process this submission. Please contact the site administrator.');

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
    $this->assertNoRaw('Unable to process this submission. Please contact the site administrator.');

    // Check 500 Internal Server Error.
    $this->postSubmission($webform, ['response_type' => '500']);
    $this->assertRaw('Failed to process completed request.');
    $this->assertRaw('Unable to process this submission. Please contact the site administrator.');

    // Check default custom response message.
    $handler = $webform->getHandler('remote_post');
    $configuration = $handler->getConfiguration();
    $configuration['settings']['message'] = 'This is a custom response message';
    $handler->setConfiguration($configuration);
    $webform->save();
    $this->postSubmission($webform, ['response_type' => '500']);
    $this->assertRaw('Failed to process completed request.');
    $this->assertNoRaw('Unable to process this submission. Please contact the site administrator.');
    $this->assertRaw('This is a custom response message');

    // Check 404 Not Found with custom message.
    $this->postSubmission($webform, ['response_type' => '404']);
    $this->assertRaw('File not found');
    $this->assertNoRaw('Unable to process this submission. Please contact the site administrator.');
    $this->assertRaw('This is a custom 404 not found message.');

    // Check 401 Unauthorized with custom message and token.
    $this->postSubmission($webform, ['response_type' => '401']);
    $this->assertRaw('Unauthorized');
    $this->assertNoRaw('Unable to process this submission. Please contact the site administrator.');
    $this->assertRaw('This is a message token <strong>Unauthorized to process completed request.</strong>');

    // Disable saving of results.
    $webform->setSetting('results_disabled', TRUE);
    $webform->save();

    // Check confirmation number when results disabled.
    $sid = $this->postSubmission($webform);
    $this->assertNull($sid);

    // Get confirmation number from JSON packet.
    preg_match('/&quot;confirmation_number&quot;:&quot;([a-zA-z0-9]+)&quot;/', $this->getRawContent(), $match);
    $this->assertRaw('Your confirmation number is ' . $match[1] . '.');

    // Set remote post error URL to homepage.
    $handler = $webform->getHandler('remote_post');
    $configuration = $handler->getConfiguration();
    $configuration['settings']['error_url'] = $webform->toUrl('canonical', ['query' => ['error' => '1']])->toString();
    $handler->setConfiguration($configuration);
    $webform->save();

    // Check 404 Not Found with custom error uri.
    $this->postSubmission($webform, ['response_type' => '404']);
    $this->assertRaw('This is a custom 404 not found message.');
    $this->assertUrl($webform->toUrl('canonical', ['query' => ['error' => '1']])->setAbsolute()->toString());

    /**************************************************************************/
    // PUT.
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_put');

    $this->postSubmission($webform);

    // Check PUT response.
    $this->assertRaw("method: put
status: success
message: 'Processed completed request.'
options:
  headers:
    custom_header: 'true'
  form_params:
    custom_completed: true
    custom_data: true
    response_type: '200'
    first_name: John
    last_name: Smith");

    /**************************************************************************/
    // GET.
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_get');

    $this->postSubmission($webform);

    // Check GET response.
    $this->assertRaw("method: get
status: success
message: 'Processed completed request.'
options:
  headers:
    custom_header: 'true'");

    // Check request URL contains query string.
    $this->assertRaw("http://webform-test-handler-remote-post/completed?custom_completed=1&amp;custom_data=1&amp;response_type=200&amp;first_name=John&amp;last_name=Smith");

    // Check response data.
    $this->assertRaw("message: 'Processed completed request.'");

    // Get confirmation number from JSON packet.
    preg_match('/&quot;confirmation_number&quot;:&quot;([a-zA-z0-9]+)&quot;/', $this->getRawContent(), $match);
    $this->assertRaw('Your confirmation number is ' . $match[1] . '.');

    /**************************************************************************/
    // POST File.
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_post_file');

    $sid = $this->postSubmissionTest($webform);
    $webform_submission = WebformSubmission::load($sid);

    $file_data = $webform_submission->getElementData('file');
    $file = File::load($file_data);
    $file_id = $file->id();
    $file_uuid = $file->uuid();

    $files_data = $webform_submission->getElementData('files');
    $file = File::load(reset($files_data));
    $files_id = $file->id();
    $files_uuid = $file->uuid();

    // Check the file name, uri, and data is appended to form params.
    $this->assertRaw("form_params:
  file: 1
  files:
    - 2
  _file:
    id: $file_id
    name: file.txt
    uri: 'private://webform/test_handler_remote_post_file/$sid/file.txt'
    mime: text/plain
    uuid: $file_uuid
    data: dGhpcyBpcyBhIHNhbXBsZSB0eHQgZmlsZQppdCBoYXMgdHdvIGxpbmVzCg==
  file__id: $file_id
  file__name: file.txt
  file__uri: 'private://webform/test_handler_remote_post_file/$sid/file.txt'
  file__mime: text/plain
  file__uuid: $file_uuid
  file__data: dGhpcyBpcyBhIHNhbXBsZSB0eHQgZmlsZQppdCBoYXMgdHdvIGxpbmVzCg==
  _files:
    -
      id: $files_id
      name: files.txt
      uri: 'private://webform/test_handler_remote_post_file/$sid/files.txt'
      mime: text/plain
      uuid: $files_uuid
      data: dGhpcyBpcyBhIHNhbXBsZSB0eHQgZmlsZQppdCBoYXMgdHdvIGxpbmVzCg==");

    /**************************************************************************/
    // POST cast.
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_post_cast');

    $edit = [
      'checkbox' => TRUE,
      'number' => '10',
      'number_multiple[items][0][_item_]' => '10.5',
      'custom_composite[items][0][textfield]' => 'text',
      'custom_composite[items][0][checkbox]' => TRUE,
      'custom_composite[items][0][number]' => '20.5',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw("form_params:
    checkbox: true
    number: 10
    number_multiple:
      - 10.5
    custom_composite:
      -
        textfield: text
        checkbox: true
        number: 20.5");

  }

}
