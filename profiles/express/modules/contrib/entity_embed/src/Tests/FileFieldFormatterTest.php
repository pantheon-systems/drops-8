<?php

namespace Drupal\entity_embed\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormState;

/**
 * Tests the file field formatter provided by entity_embed.
 *
 * @group entity_embed
 */
class FileFieldFormatterTest extends EntityEmbedTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'image'];

  /**
   * Created file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   *
   */
  protected function setUp() {
    parent::setUp();
    $this->file = $this->getTestFile('text');
  }

  /**
   * Tests file field formatter Entity Embed Display plugins.
   */
  public function testFileFieldFormatter() {
    // Ensure that file field formatters are available as plugins.
    $this->assertAvailableDisplayPlugins($this->file, [
      'entity_reference:entity_reference_label',
      'entity_reference:entity_reference_entity_id',
      'file:file_default',
      'file:file_table',
      'file:file_url_plain',
    ]);

    // Ensure that correct form attributes are returned for the file field
    // formatter plugins.
    $form = array();
    $form_state = new FormState();
    $plugins = array(
      'file:file_table',
      'file:file_default',
      'file:file_url_plain',
    );
    // Ensure that description field is available for all the 'file' plugins.
    foreach ($plugins as $plugin) {
      $display = $this->container->get('plugin.manager.entity_embed.display')
        ->createInstance($plugin, []);
      $display->setContextValue('entity', $this->file);
      $conf_form = $display->buildConfigurationForm($form, $form_state);
      $this->assertIdentical(array_keys($conf_form), array('description'));
      $this->assertIdentical($conf_form['description']['#type'], 'textfield');
      $this->assertIdentical((string) $conf_form['description']['#title'], 'Description');
    }

    // Test entity embed using 'Generic file' Entity Embed Display plugin.
    $embed_settings = array('description' => "This is sample description");
    $content = '<drupal-entity data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" data-entity-embed-display="file:file_default" data-entity-embed-display-settings=\'' . Json::encode($embed_settings) . '\'>This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with file:file_default';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($embed_settings['description'], 'Description of the embedded file exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appears in the output when embed is successful.');
    $this->assertLinkByHref(file_create_url($this->file->getFileUri()), 0, 'Link to the embedded file exists.');
  }

}
