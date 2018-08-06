<?php

namespace Drupal\entity_browser_entity_form\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests integration with Inline entity form.
 *
 * @group entity_browser_entity_form
 */
class InlineEntityIntegrationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser_entity_form',
    'node',
    'field_ui',
    'entity_browser_entity_form_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Delete unnecessary entity browser.
    $browser = $this->container->get('entity_type.manager')->getStorage('entity_browser')->load('entity_browser_test_entity_form');
    $this->container->get('entity_type.manager')->getStorage('entity_browser')->delete([$browser]);
  }

  /**
   * Tests integration with Inline entity form.
   */
  public function testInlineEntityIntegration() {
    $account = $this->drupalCreateUser([
      'administer node form display',
      'administer node display',
      'create article content',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $edit = [
      'fields[field_content_reference][region]' => 'content',
      'fields[field_content_reference][type]' => 'inline_entity_form_complex',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalPostAjaxForm(NULL, [], 'field_content_reference_settings_edit');
    $this->assertRaw('fields[field_content_reference][settings_edit_form][third_party_settings][entity_browser_entity_form][entity_browser_id]', 'Field to select entity browser is available.');
    $edit = [
      'fields[field_content_reference][settings_edit_form][third_party_settings][entity_browser_entity_form][entity_browser_id]' => 'entity_browser_entity_form_test',
      'fields[field_content_reference][settings_edit_form][settings][allow_existing]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_content_reference_plugin_settings_update');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Entity browser: Entity browser entity form test', 'Settings summary is working correctly.');

    $this->drupalGet('node/add');
    $elements = $this->xpath('//input[@type="submit" and @value="Add existing node"]');
    $button_name = $elements[0]->attributes()['name'];
    $this->drupalPostAjaxForm(NULL, [], $button_name);
    $this->assertLink('Select entities', 0, 'Entity browser is available.');

    $browsers = $this->container->get('entity_type.manager')->getStorage('entity_browser')->loadMultiple();
    $browser = current($browsers);
    $this->container->get('entity_type.manager')->getStorage('entity_browser')->delete([$browser]);
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->drupalPostAjaxForm(NULL, [], 'field_content_reference_settings_edit');
    $this->assertText(t('There are no entity browsers available. You can create one here'), 'Massage displays when no entity browser is available.');
  }

}
