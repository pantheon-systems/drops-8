<?php

namespace Drupal\entity_embed\Tests;

use Drupal\editor\Entity\Editor;

/**
 * Tests the entity_embed dialog controller and route.
 *
 * @group entity_embed
 */
class EntityEmbedDialogTest extends EntityEmbedTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['image'];

  /**
   * Tests the entity embed dialog.
   */
  public function testEntityEmbedDialog() {
    // Ensure that the route is not accessible without specifying all the
    // parameters.
    $this->getEmbedDialog();
    $this->assertResponse(404, 'Embed dialog is not accessible without specifying filter format and embed button.');
    $this->getEmbedDialog('custom_format');
    $this->assertResponse(404, 'Embed dialog is not accessible without specifying embed button.');

    // Ensure that the route is not accessible with an invalid embed button.
    $this->getEmbedDialog('custom_format', 'invalid_button');
    $this->assertResponse(404, 'Embed dialog is not accessible without specifying filter format and embed button.');

    // Ensure that the route is not accessible with text format without the
    // button configured.
    $this->getEmbedDialog('plain_text', 'node');
    $this->assertResponse(404, 'Embed dialog is not accessible with a filter that does not have an editor configuration.');

    // Add an empty configuration for the plain_text editor configuration.
    $editor = Editor::create([
      'format' => 'plain_text',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
    $this->getEmbedDialog('plain_text', 'node');
    $this->assertResponse(403, 'Embed dialog is not accessible with a filter that does not have the embed button assigned to it.');

    // Ensure that the route is accessible with a valid embed button.
    // 'Node' embed button is provided by default by the module and hence the
    // request must be successful.
    $this->getEmbedDialog('custom_format', 'node');
    $this->assertResponse(200, 'Embed dialog is accessible with correct filter format and embed button.');

    // Ensure form structure of the 'select' step and submit form.
    $this->assertFieldByName('entity_id', '', 'Entity ID/UUID field is present.');

    // $edit = ['attributes[data-entity-id]' => $this->node->id()];
    // $this->drupalPostAjaxForm(NULL, $edit, 'op');
    // Ensure form structure of the 'embed' step and submit form.
    // $this->assertFieldByName('attributes[data-entity-embed-display]', 'Entity Embed Display plugin field is present.');.
  }

  /**
   * Tests the entity embed button markup.
   */
  public function testEntityEmbedButtonMarkup() {
    // Ensure that the route is not accessible with text format without the
    // button configured.
    $this->getEmbedDialog('plain_text', 'node');
    $this->assertResponse(404, 'Embed dialog is not accessible with a filter that does not have an editor configuration.');

    // Add an empty configuration for the plain_text editor configuration.
    $editor = Editor::create([
      'format' => 'plain_text',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
    $this->getEmbedDialog('plain_text', 'node');
    $this->assertResponse(403, 'Embed dialog is not accessible with a filter that does not have the embed button assigned to it.');

    // Ensure that the route is accessible with a valid embed button.
    // 'Node' embed button is provided by default by the module and hence the
    // request must be successful.
    $this->getEmbedDialog('custom_format', 'node');
    $this->assertResponse(200, 'Embed dialog is accessible with correct filter format and embed button.');

    // Ensure form structure of the 'select' step and submit form.
    $this->assertFieldByName('entity_id', '', 'Entity ID/UUID field is present.');

    // Check that 'Next' is a primary button.
    $this->assertFieldByXPath('//input[contains(@class, "button--primary")]', 'Next', 'Next is a primary button');

    $title = $this->node->getTitle() . ' (' . $this->node->id() . ')';
    $edit = ['entity_id' => $title];
    $response = $this->drupalPostAjaxForm(NULL, $edit, 'op');
    $plugins = [
      'entity_reference:entity_reference_label',
      'entity_reference:entity_reference_entity_id',
      'view_mode:node.full',
      'view_mode:node.rss',
      'view_mode:node.search_index',
      'view_mode:node.search_result',
      'view_mode:node.teaser',
    ];
    foreach ($plugins as $plugin) {
      $this->assertTrue(strpos($response[2]['data'], $plugin), 'Plugin ' . $plugin . ' is available in selection.');
    }

    $this->container->get('config.factory')->getEditable('entity_embed.settings')
      ->set('rendered_entity_mode', TRUE)->save();
    $this->container->get('plugin.manager.entity_embed.display')->clearCachedDefinitions();

    $this->getEmbedDialog('custom_format', 'node');
    $title = $this->node->getTitle() . ' (' . $this->node->id() . ')';
    $edit = ['entity_id' => $title];
    $response = $this->drupalPostAjaxForm(NULL, $edit, 'op');

    $plugins = [
      'entity_reference:entity_reference_label',
      'entity_reference:entity_reference_entity_id',
      'entity_reference:entity_reference_entity_view',
    ];
    foreach ($plugins as $plugin) {
      $this->assertTrue(strpos($response[2]['data'], $plugin), 'Plugin ' . $plugin . ' is available in selection.');
    }
    /*$this->drupalPostForm(NULL, $edit, 'Next');
    // Ensure form structure of the 'embed' step and submit form.
    $this->assertFieldByName('attributes[data-entity-embed-display]', 'Entity Embed Display plugin field is present.');

    // Check that 'Embed' is a primary button.
    $this->assertFieldByXPath('//input[contains(@class, "button--primary")]', 'Embed', 'Embed is a primary button');*/
  }

  /**
   * Tests entity embed functionality.
   */
  public function testEntityEmbedFunctionality() {
    $edit = [
      'entity_id' => $this->node->getTitle() . ' (' . $this->node->id() . ')',
    ];
    $this->getEmbedDialog('custom_format', 'node');
    $this->drupalPostForm(NULL, $edit, t('Next'));
    // Tests that the embed dialog doesn't trow a fatal in
    // ImageFieldFormatter::isValidImage()
    $this->assertResponse(200);
  }

  /**
   * Retrieves an embed dialog based on given parameters.
   *
   * @param string $filter_format_id
   *   ID of the filter format.
   * @param string $embed_button_id
   *   ID of the embed button.
   *
   * @return string
   *   The retrieved HTML string.
   */
  public function getEmbedDialog($filter_format_id = NULL, $embed_button_id = NULL) {
    $url = 'entity-embed/dialog';
    if (!empty($filter_format_id)) {
      $url .= '/' . $filter_format_id;
      if (!empty($embed_button_id)) {
        $url .= '/' . $embed_button_id;
      }
    }
    return $this->drupalGet($url);
  }

}
