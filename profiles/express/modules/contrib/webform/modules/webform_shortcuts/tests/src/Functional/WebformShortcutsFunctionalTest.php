<?php

namespace Drupal\Tests\webform_shortcuts\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform shortcuts test.
 *
 * @group webform_browser
 */
class WebformShortcutsFunctionalTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'webform',
    'webform_ui',
    'webform_shortcuts',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->placeBlocks();
  }

  /**
   * Test shortcuts.
   */
  public function testShortcuts() {
    $this->drupalLogin($this->rootUser);

    // Check default shortcuts.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Keyboard shortcuts&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;&lt;hr /&gt;CTRL+E = Add element&lt;br /&gt;CTRL+P = Add page&lt;br /&gt;CTRL+L = Add layout&lt;br /&gt;&lt;hr /&gt;CTRL+S = Save element or elements&lt;br /&gt;CTRL+R = Reset elements&lt;br /&gt;&lt;hr /&gt;CTRL+W = Show/hide row weights&lt;br /&gt;&lt;hr /&gt;&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Customize the shortcuts.
    $edit = [
      'webform_shortcuts[add_element]' => 'crtl+z',
      'webform_shortcuts[toggle_weights]' => '',
    ];
    $this->drupalPostForm('/admin/structure/webform/config/advanced', $edit, t('Save configuration'));

    // Check customized shortcuts.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Keyboard shortcuts&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;&lt;hr /&gt;CRTL+Z = Add element&lt;br /&gt;CTRL+P = Add page&lt;br /&gt;CTRL+L = Add layout&lt;br /&gt;&lt;hr /&gt;CTRL+S = Save element or elements&lt;br /&gt;CTRL+R = Reset elements&lt;br /&gt;&lt;hr /&gt;&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
  }

}
