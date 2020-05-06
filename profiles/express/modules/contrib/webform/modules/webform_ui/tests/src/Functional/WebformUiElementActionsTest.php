<?php

namespace Drupal\Tests\webform_ui\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform UI actions element.
 *
 * @group WebformUi
 */
class WebformUiElementActionsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform', 'webform_ui'];

  /**
   * Tests actions element.
   */
  public function testActionsElements() {
    $this->drupalLogin($this->rootUser);

    $values = ['id' => 'test'];
    $elements = [
      'text_field' => [
        '#type' => 'textfield',
        '#title' => 'textfield',
      ],
    ];
    $this->createWebform($values, $elements);

    // Confirm submit buttons are customizable.
    $this->drupalGet('/admin/structure/webform/manage/test');
    $this->assertLink('Customize');

    // Disable actions element.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.excluded_elements.webform_actions', 'webform_actions')
      ->save();

    // Confirm submit buttons are not customizable.
    $this->drupalGet('/admin/structure/webform/manage/test');
    $this->assertNoLink('Customize');
  }

}
