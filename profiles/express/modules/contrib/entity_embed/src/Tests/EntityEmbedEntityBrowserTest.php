<?php

namespace Drupal\entity_embed\Tests;

use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\embed\Entity\EmbedButton;

/**
 * Tests the entity_embed entity_browser integration.
 *
 * @group entity_embed
 *
 * @dependencies entity_browser
 */
class EntityEmbedEntityBrowserTest extends EntityEmbedDialogTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_browser'];

  /**
   * Tests the entity browser integration.
   */
  public function testEntityEmbedEntityBrowserIntegration() {
    $this->getEmbedDialog('custom_format', 'node');
    $this->assertResponse(200, 'Embed dialog is accessible with custom filter format and default embed button.');

    // Verify that an autocomplete field is available by default.
    $this->assertFieldByName('entity_id', '', 'Entity ID/UUID field is present.');
    $this->assertNoText('Select entities to embed', 'Entity browser button is not present.');

    // Set up entity browser.
    $entity_browser = EntityBrowser::create([
      "name" => 'entity_embed_entity_browser_test',
      "label" => 'Test Entity Browser for Entity Embed',
      "display" => 'modal',
      "display_configuration" => [
        'width' => '650',
        'height' => '500',
        'link_text' => 'Select entities to embed',
      ],
      "selection_display" => 'no_display',
      "selection_display_configuration" => [],
      "widget_selector" => 'single',
      "widget_selector_configuration" => [],
      "widgets" => [],
    ]);
    $entity_browser->save();

    // Enable entity browser for the default entity embed button.
    $embed_button = EmbedButton::load('node');
    $embed_button->type_settings['entity_browser'] = 'entity_embed_entity_browser_test';
    $embed_button->save();

    $this->getEmbedDialog('custom_format', 'node');
    $this->assertResponse(200, 'Embed dialog is accessible with custom filter format and default embed button.');

    // Verify that the autocomplete field is replaced by an entity browser
    // button.
    $this->assertNoFieldByName('entity_id', '', 'Entity ID/UUID field is present.');
    $this->assertText('Select entities to embed', 'Entity browser button is present.');
  }

}
