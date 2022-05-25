<?php

namespace Drupal\Tests\system\Functional\Bootstrap;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Messenger service.
 *
 * @group Bootstrap
 */
class DrupalMessengerServiceTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Messenger service.
   */
  public function testDrupalMessengerService() {
    // The page at system_test.messenger_service route sets two messages and
    // then removes the first before it is displayed.
    $this->drupalGet(Url::fromRoute('system_test.messenger_service'));
    $this->assertSession()->pageTextNotContains('First message (removed).');
    $this->assertSession()->responseContains('Second message with <em>markup!</em> (not removed).');

    // Ensure duplicate messages are handled as expected.
    $this->assertSession()->pageTextMatchesCount(1, '/Non Duplicated message/');
    $this->assertSession()->pageTextMatchesCount(3, '/Duplicated message/');

    // Ensure Markup objects are rendered as expected.
    $this->assertSession()->responseContains('Markup with <em>markup!</em>');
    $this->assertSession()->pageTextMatchesCount(1, '/Markup with markup!/');
    $this->assertSession()->responseContains('Markup2 with <em>markup!</em>');

    // Ensure when the same message is of different types it is not duplicated.
    $this->assertSession()->pageTextMatchesCount(1, '$Non duplicate Markup / string.$');
    $this->assertSession()->pageTextMatchesCount(2, '$Duplicate Markup / string.$');

    // Ensure that strings that are not marked as safe are escaped.
    $this->assertSession()->assertEscaped('<em>This<span>markup will be</span> escaped</em>.');

    // Ensure messages survive a container rebuild.
    $assert = $this->assertSession();
    $this->drupalLogin($this->rootUser);
    $edit = [];
    $edit["modules[help][enable]"] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $assert->pageTextContains('Help has been enabled');
    $assert->pageTextContains('system_test_preinstall_module called');
  }

}
