<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that the Metatag hook_metatags_attachments_alter() works.
 *
 * @group metatag
 */
class MetatagIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // The base module.
    'metatag',

    // Implements metatag_test_integration_metatags_attachments_alter().
    'metatag_test_integration',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests hook_metatags_attachments_alter() and title altering.
   *
   * @see metatag_test_integration_metatags_attachments_alter()
   */
  public function testHookMetatagsAttachmentsAlter() {
    // Get the front page and assert the page title.
    $this->drupalGet('');
    $this->assertSession()->titleEquals('This is the title I want | Drupal | Yeah!');
  }

}
