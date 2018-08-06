<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
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
  public static $modules = ['webform', 'webform_test_handler'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_remote_post'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test remote post handler.
   */
  public function testRemotePostHandler() {
    /** @var \Drupal\webform\WebformInterface $webform_handler_remote */
    $webform_handler_remote = Webform::load('test_handler_remote_post');

    $this->drupalLogin($this->adminWebformUser);

    // Check remote post 'create' operation.
    $sid = $this->postSubmission($webform_handler_remote);
    $this->assertPattern('#<label>Remote operation</label>\s+insert#ms');
    $this->assertRaw('custom_insert: true');
    $this->assertRaw('custom_all: true');
    $this->assertRaw("custom_title: 'Test: Handler: Remote post: Submission #$sid'");
    $this->assertRaw('first_name: John');
    $this->assertRaw('last_name: Smith');
    $this->assertRaw('email: from@example.com');
    $this->assertRaw("subject: '{subject}'");
    $this->assertRaw("message: '{message}'");
    $this->assertNoRaw("sid: '$sid'");

    // Check remote post 'update' operation.
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_remote_post/submission/$sid/edit", [], t('Save'));
    $this->assertRaw('custom_update: true');
    $this->assertRaw('custom_all: true');
    $this->assertRaw("custom_title: 'Test: Handler: Remote post: Submission #$sid'");
    $this->assertRaw('first_name: John');
    $this->assertPattern('#<label>Remote operation</label>\s+update#ms');

    // Check remote post 'delete' operation.
    $this->drupalPostForm("admin/structure/webform/manage/test_handler_remote_post/submission/$sid/delete", [], t('Delete'));
    $this->assertRaw('custom_delete: true');
    $this->assertRaw('custom_all: true');
    $this->assertRaw("custom_title: 'Test: Handler: Remote post: Submission #$sid'");
    $this->assertRaw('first_name: John');
    $this->assertPattern('#<label>Remote operation</label>\s+delete#ms');

    // Check including data.
    $handler = $webform_handler_remote->getHandler('remote_post');
    $configuration = $handler->getConfiguration();
    $configuration['settings']['excluded_data'] = [
      'subject' => 'subject',
      'message' => 'message',
    ];
    $handler->setConfiguration($configuration);
    $webform_handler_remote->save();
    $sid = $this->postSubmission($webform_handler_remote);
    $this->assertRaw('first_name: John');
    $this->assertRaw('last_name: Smith');
    $this->assertRaw('email: from@example.com');
    $this->assertNoRaw("subject: '{subject}'");
    $this->assertNoRaw("message: '{message}'");
    $this->assertRaw("sid: '$sid'");

    // @todo Figure out why the below test is failing on Drupal.org.
    // Check remote post 'create' 500 error handling.
    // $this->postSubmission($webform_handler_remote, ['first_name' => 'FAIL']);
    // $this->assertPattern('#<label>Response status code</label>\s+500#ms');

    // @todo Figure out why the below test is failing on Drupal.org.
    // Update the remote post handlers insert url to return a 404 error.
    // /** @var \Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler $handler */
    // $handler = $webform_handler_remote->getHandler('remote_post');
    // $configuration = $handler->getConfiguration();
    // $configuration['settings']['insert_url'] .= '/broken';
    // $handler->setConfiguration($configuration);
    // $webform_handler_remote->save();

    // $this->postSubmission($webform_handler_remote, ['first_name' => 'FAIL']);
    // $this->assertPattern('#<label>Response status code</label>\s+404#ms');
  }

}
