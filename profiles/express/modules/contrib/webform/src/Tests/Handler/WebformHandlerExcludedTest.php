<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for the webform handler excluded.
 *
 * @group Webform
 */
class WebformHandlerExcludedTest extends WebformTestBase {

  /**
   * Test excluded handlers.
   */
  public function testExcludeHandlers() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
    $handler_manager = $this->container->get('plugin.manager.webform.handler');

    // Check add mail and handler plugin.
    $this->drupalGet('admin/structure/webform/manage/contact/handlers');
    $this->assertLink('Add email');
    $this->assertLink('Add handler');

    // Check add mail accessible.
    $this->drupalGet('admin/structure/webform/manage/contact/handlers/add/email');
    $this->assertResponse(200);

    // Exclude the email handler.
    \Drupal::configFactory()->getEditable('webform.settings')->set('handler.excluded_handlers', ['email' => 'email'])->save();

    // Check add mail hidden.
    $this->drupalGet('admin/structure/webform/manage/contact/handlers');
    $this->assertNoLink('Add email');
    $this->assertLink('Add handler');

    // Check add mail access denied.
    $this->drupalGet('admin/structure/webform/manage/contact/handlers/add/email');
    $this->assertResponse(403);

    // Exclude the email handler.
    \Drupal::configFactory()->getEditable('webform.settings')->set('handler.excluded_handlers', ['broken' => 'broken', 'debug' => 'debug', 'email' => 'email', 'remote_post' => 'remote_post'])->save();

    // Check add mail and handler hidden.
    $this->drupalGet('admin/structure/webform/manage/contact/handlers');
    $this->assertNoLink('Add email');
    $this->assertNoLink('Add handler');

    // Check handler definitions.
    $definitions = $handler_manager->getDefinitions();
    $definitions = $handler_manager->removeExcludeDefinitions($definitions);
    $this->assertEqual(array_keys($definitions), []);
  }

}
