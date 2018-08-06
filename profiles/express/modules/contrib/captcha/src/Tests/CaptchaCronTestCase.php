<?php

namespace Drupal\captcha\Tests;

use Drupal\Core\Database\Database;
use Drupal\simpletest\WebTestBase;

/**
 * Tests CAPTCHA cron.
 *
 * @group captcha
 */
class CaptchaCronTestCase extends WebTestBase {

  /**
   * Modules to install for this Test class.
   *
   * @var array
   */
  public static $modules = ['captcha'];

  /**
   * Temporary captcha sessions storage.
   *
   * @var [int]
   */
  public $captchaSessions;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add removed session.
    $time = REQUEST_TIME - 1 - 60 * 60 * 24;
    $this->captchaSessions['remove_sid'] = $this->addCaptchaSession('captcha_cron_test_remove', $time);
    // Add remain session.
    $this->captchaSessions['remain_sid'] = $this->addCaptchaSession('captcha_cron_test_remain', REQUEST_TIME);
  }

  /**
   * Add test CAPTCHA session data.
   *
   * @param string $form_id
   *   Form id.
   * @param int $request_time
   *   Timestamp.
   *
   * @return int
   *   CAPTCHA session id.
   */
  public function addCaptchaSession($form_id, $request_time) {
    // Initialize solution with random data.
    $solution = hash('sha256', mt_rand());

    // Insert an entry and thankfully receive the value
    // of the autoincrement field 'csid'.
    $connection = Database::getConnection();
    $captcha_sid = $connection->insert('captcha_sessions')->fields([
      'uid' => 0,
      'sid' => session_id(),
      'ip_address' => \Drupal::request()->getClientIp(),
      'timestamp' => $request_time,
      'form_id' => $form_id,
      'solution' => $solution,
      'status' => 1,
      'attempts' => 0,
    ])->execute();

    return $captcha_sid;
  }

  /**
   * Test CAPTCHA cron.
   */
  public function testCron() {
    $this->cronRun();

    $connection = Database::getConnection();
    $sids = $connection->select('captcha_sessions')
      ->fields('captcha_sessions', ['csid'])
      ->condition('csid', array_values($this->captchaSessions), 'IN')
      ->execute()
      ->fetchCol('csid');

    // Test if CAPTCHA cron appropriately removes sessions older than a day.
    $this->assertTrue(!in_array($this->captchaSessions['remove_sid'], $sids), 'CAPTCHA cron removes captcha session data older than 1 day.');

    // Test if CAPTCHA cron appropriately keeps sessions younger than a day.
    $this->assertTrue(in_array($this->captchaSessions['remain_sid'], $sids), 'CAPTCHA cron does not remove captcha session data younger than 1 day.');
  }

}
