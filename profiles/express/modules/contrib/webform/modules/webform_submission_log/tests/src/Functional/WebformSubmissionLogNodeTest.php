<?php

namespace Drupal\Tests\webform_submission_log\Functional;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\Tests\webform_submission_log\Traits\WebformSubmissionLogTrait;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform node submission log.
 *
 * @group WebformSubmissionLog
 */
class WebformSubmissionLogNodeTest extends WebformNodeBrowserTestBase {

  use WebformSubmissionLogTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform', 'webform_node', 'webform_submission_log'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submission_log'];

  /**
   * Tests webform submission log.
   */
  public function testSubmissionLog() {
    global $base_path;
    $node = $this->createWebformNode('test_submission_log');
    $nid = $node->id();

    $sid = $this->postNodeSubmission($node);
    $submission = WebformSubmission::load($sid);
    $log = $this->getLastSubmissionLog();
    $this->assertEqual($log->lid, 1);
    $this->assertEqual($log->sid, 1);
    $this->assertEqual($log->uid, 0);
    $this->assertEqual($log->handler_id, '');
    $this->assertEqual($log->operation, 'submission created');
    $this->assertEqual($log->message, '@title created.');
    $this->assertEqual($log->variables, ['@title' => $submission->label()]);
    $this->assertEqual($log->webform_id, 'test_submission_log');
    $this->assertEqual($log->entity_type, 'node');
    $this->assertEqual($log->entity_id, $node->id());

    // Login.
    $this->drupalLogin($this->rootUser);

    // Check webform node results log table has record.
    $this->drupalGet("node/$nid/webform/results/log");
    $this->assertResponse(200);
    $this->assertNoRaw('No log messages available.');
    $this->assertRaw('<a href="' . $base_path . 'node/' . $nid . '/webform/submission/' . $sid . '/log">' . $sid . '</a>');
    $this->assertRaw(t('@title created.', ['@title' => $submission->label()]));

    // Check webform node submission log tab.
    $this->drupalGet("node/$nid/webform/submission/$sid/log");
    $this->assertResponse(200);
  }

}
