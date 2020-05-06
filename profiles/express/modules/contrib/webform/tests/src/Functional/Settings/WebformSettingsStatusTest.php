<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform default status.
 *
 * @group Webform
 */
class WebformSettingsStatusTest extends WebformBrowserTestBase {

  /**
   * Tests default status.
   */
  public function testStatus() {
    $this->drupalLogin($this->rootUser);

    // Check add form status = open.
    $this->drupalGet('/admin/structure/webform/add');
    $this->assertFieldChecked('edit-status-open');
    $this->assertNoFieldChecked('edit-status-closed');

    // Check duplicate form status = open.
    $this->drupalGet('/admin/structure/webform/manage/contact/duplicate');
    $this->assertFieldChecked('edit-status-open');
    $this->assertNoFieldChecked('edit-status-closed');

    // Set default status to closed.
    $this->config('webform.settings')
      ->set('settings.default_status', WebformInterface::STATUS_CLOSED)
      ->save();

    // Check add form status = closed.
    $this->drupalGet('/admin/structure/webform/add');
    $this->assertNoFieldChecked('edit-status-open');
    $this->assertFieldChecked('edit-status-closed');

    // Check duplicate form status = closed.
    $this->drupalGet('/admin/structure/webform/manage/contact/duplicate');
    $this->assertNoFieldChecked('edit-status-open');
    $this->assertFieldChecked('edit-status-closed');
  }

}
