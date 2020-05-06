<?php

namespace Drupal\Tests\webform\Kernel\Utility;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Tests webform dialog utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformDialogHelper
 */
class WebformDialogHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'webform'];

  /**
   * Test get modal dialog attributes.
   *
   * @see \Drupal\webform\Utility\WebformDialogHelper::getModalDialogAttributes
   */
  public function testGetModalDialogAttributes() {
    // Enable dialogs.
    $this->config('webform.settings')
      ->set('ui.dialog_disabled', FALSE)
      ->save();

    // Check default attributes.
    $this->assertEquals(WebformDialogHelper::getModalDialogAttributes(), [
      'class' => ['webform-ajax-link'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => '{"width":800,"dialogClass":"webform-ui-dialog"}',
    ]);

    // Check custom width and attributes.
    $this->assertEquals(WebformDialogHelper::getModalDialogAttributes(400, ['custom']), [
      'class' => ['custom', 'webform-ajax-link'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => '{"width":400,"dialogClass":"webform-ui-dialog"}',
    ]);

    // Disable dialogs.
    $this->config('webform.settings')
      ->set('ui.dialog_disabled', TRUE)
      ->save();

    // Check default attributes.
    $this->assertEquals(WebformDialogHelper::getModalDialogAttributes(), []);

    // Check custom attributes.
    $this->assertEquals(WebformDialogHelper::getModalDialogAttributes(400, ['custom']), ['class' => ['custom']]);
  }

}
