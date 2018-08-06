<?php

namespace Drupal\webform_node\Tests;

/**
 * Tests for webform node submission log.
 *
 * @group WebformNode
 */
class WebformNodeSubmissionLogTest extends WebformNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform', 'webform_node'];

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
    $log = $this->getLastSubmissionLog();
    $this->assertEqual($log->lid, 1);
    $this->assertEqual($log->sid, 1);
    $this->assertEqual($log->uid, 0);
    $this->assertEqual($log->handler_id, '');
    $this->assertEqual($log->operation, 'submission created');
    $this->assertEqual($log->message, t('@title: Submission #1 created.', ['@title' => $node->label()]));
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
    $this->assertRaw(t('@title: Submission #1 created.', ['@title' => $node->label()]));

    // Check webform node submission log tab.
    $this->drupalGet("node/$nid/webform/submission/$sid/log");
    $this->assertResponse(200);
  }

}
