<?php

namespace Drupal\entity_embed\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormState;

/**
 * Tests the image field formatter provided by entity_embed.
 *
 * @group entity_embed
 */
class ImageFieldFormatterTest extends EntityEmbedTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'image', 'responsive_image'];

  /**
   * Created file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $image;

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
    $this->image = $this->getTestFile('image');
    $this->file = $this->getTestFile('text');
  }

  /**
   * Tests image field formatter Entity Embed Display plugin.
   */
  public function testImageFieldFormatter() {
    // Ensure that image field formatters are available as plugins.
    $this->assertAvailableDisplayPlugins($this->image, [
      'entity_reference:entity_reference_label',
      'entity_reference:entity_reference_entity_id',
      'file:file_default',
      'file:file_table',
      'file:file_url_plain',
      'image:responsive_image',
      'image:image',
    ]);

    // Ensure that correct form attributes are returned for the image plugin.
    $form = array();
    $form_state = new FormState();
    $display = $this->container->get('plugin.manager.entity_embed.display')
      ->createInstance('image:image', []);
    $display->setContextValue('entity', $this->image);
    $conf_form = $display->buildConfigurationForm($form, $form_state);
    $this->assertIdentical(array_keys($conf_form), array(
      'image_style',
      'image_link',
      'alt',
      'title',
    ));
    $this->assertIdentical($conf_form['image_style']['#type'], 'select');
    $this->assertIdentical((string) $conf_form['image_style']['#title'], 'Image style');
    $this->assertIdentical($conf_form['image_link']['#type'], 'select');
    $this->assertIdentical((string) $conf_form['image_link']['#title'], 'Link image to');
    $this->assertIdentical($conf_form['alt']['#type'], 'textfield');
    $this->assertIdentical((string) $conf_form['alt']['#title'], 'Alternate text');
    $this->assertIdentical($conf_form['title']['#type'], 'textfield');
    $this->assertIdentical((string) $conf_form['title']['#title'], 'Title');

    // Test entity embed using 'Image' Entity Embed Display plugin.
    $alt_text = "This is sample description";
    $title = "This is sample title";
    $embed_settings = array('image_link' => 'file');
    $content = '<drupal-entity data-entity-type="file" data-entity-uuid="' . $this->image->uuid() . '" data-entity-embed-display="image:image" data-entity-embed-display-settings=\'' . Json::encode($embed_settings) . '\' alt="' . $alt_text . '" title="' . $title . '">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with image:image';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($alt_text, 'Alternate text for the embedded image is visible when embed is successful.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appears in the output when embed is successful.');
    $this->assertLinkByHref(file_create_url($this->image->getFileUri()), 0, 'Link to the embedded image exists.');

    // Embed all three field types in one, to ensure they all render correctly.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="entity_reference:entity_reference_label"></drupal-entity>';
    $content .= '<drupal-entity data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" data-entity-embed-display="file:file_default"></drupal-entity>';
    $content .= '<drupal-entity data-entity-type="file" data-entity-uuid="' . $this->image->uuid() . '" data-entity-embed-display="image:image"></drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test node entity embedded first then a file entity';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
  }

}
