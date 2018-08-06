<?php

namespace Drupal\entity_embed\Tests;

/**
 * Tests the entity_embed filter.
 *
 * @group entity_embed
 */
class EntityEmbedFilterTest extends EntityEmbedTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'image',
    'entity_embed',
    'entity_embed_test',
    'node',
    'ckeditor',
  ];

  /**
   * Tests the entity_embed filter.
   *
   * Ensures that entities are getting rendered when correct data attributes
   * are passed. Also tests situations when embed fails.
   */
  public function testFilter() {
    // Tests entity embed using entity ID and view mode.
    $content = '<drupal-entity data-entity-type="node" data-entity-id="' . $this->node->id() . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-id and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertText($this->node->body->value, 'Embedded node exists in page');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    $this->assertRaw('<article class="embedded-entity">', 'Embed container found.');

    // Tests that embedded entity is not rendered if not accessible.
    $this->node->setPublished(FALSE)->save();
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test un-accessible entity embed with entity-id and view-mode';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertNoText($this->node->body->value, 'Embedded node does not exist in the page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    // Tests that embedded entity is displayed to the user who has the view
    // unpublished content permission.
    $this->createRole(['view own unpublished content'], 'access_unpublished');
    $this->webUser->addRole('access_unpublished');
    $this->webUser->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertText($this->node->body->value, 'Embedded node exists in the page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    $this->assertRaw('<article class="embedded-entity">', 'Embed container found.');
    $this->webUser->removeRole('access_unpublished');
    $this->webUser->save();
    $this->node->setPublished(TRUE)->save();

    // Tests entity embed using entity UUID and view mode.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-uuid and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertText($this->node->body->value, 'Embedded node exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    $this->assertRaw('<article class="embedded-entity">', 'Embed container found.');
    $this->assertCacheTag('foo:' . $this->node->id());

    // Ensure that placeholder is not replaced when embed is unsuccessful.
    $content = '<drupal-entity data-entity-type="node" data-entity-id="InvalidID" data-view-mode="teaser">This placeholder should be rendered since specified entity does not exists.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test that placeholder is retained when specified entity does not exists';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is unsuccessful.');

    // Ensure that UUID is preferred over ID when both attributes are present.
    $sample_node = $this->drupalCreateNode();
    $content = '<drupal-entity data-entity-type="node" data-entity-id="' . $sample_node->id() . '" data-entity-uuid="' . $this->node->uuid() . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test that entity-uuid is preferred over entity-id when both attributes are present';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertText($this->node->body->value, 'Entity specifed with UUID exists in the page.');
    $this->assertNoText($sample_node->body->value, 'Entity specifed with ID does not exists in the page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    $this->assertRaw('<article class="embedded-entity"', 'Embed container found.');

    // Test deprecated 'default' Entity Embed Display plugin.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="default" data-entity-embed-display-settings=\'{"view_mode":"teaser"}\'>This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-embed-display and data-entity-embed-display-settings';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->body->value, 'Embedded node exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    $this->assertRaw('<article class="embedded-entity"', 'Embed container found.');

    // Ensure that Entity Embed Display plugin is preferred over view mode when
    // both attributes are present.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="default" data-entity-embed-display-settings=\'{"view_mode":"full"}\' data-view-mode="some-invalid-view-mode" data-align="left" data-caption="test caption">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-embed-display and data-entity-embed-display-settings';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->body->value, 'Embedded node exists in page with the view mode specified by entity-embed-settings.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    $this->assertRaw('<article class="embedded-entity"', 'Embed container found.');

    // Ensure the embedded node doesn't contain data tags on the full page.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoRaw('data-align="left"', 'Align data attribute not found.');
    $this->assertNoRaw('data-caption="test caption"', 'Caption data attribute not found.');

    // Test that tag of container element is not replaced when it's not
    // <drupal-entity>.
    $content = '<not-drupal-entity data-entity-type="node" data-entity-id="' . $this->node->id() . '" data-view-mode="teaser">this placeholder should not be rendered.</not-drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'test entity embed with entity-id and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalget('node/' . $node->id());
    $this->assertNoText($this->node->body->value, 'embedded node exists in page');
    $this->assertRaw('</not-drupal-entity>');
    $content = '<div data-entity-type="node" data-entity-id="' . $this->node->id() . '" data-view-mode="teaser">this placeholder should not be rendered.</div>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'test entity embed with entity-id and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalget('node/' . $node->id());
    $this->assertNoText($this->node->body->value, 'embedded node exists in page');
    $this->assertRaw('<div data-entity-type="node" data-entity-id');

    // Test that attributes are correctly added when image formatter is used.
    /** @var \Drupal\file\FileInterface $image */
    $image = $this->getTestFile('image');
    $image->setPermanent();
    $image->save();
    $content = '<drupal-entity data-entity-type="file" data-entity-uuid="' . $image->uuid() . '" data-entity-embed-display="image:image" data-entity-embed-display-settings=\'{"image_style":"","image_link":""}\' data-align="left" data-caption="test caption" alt="This is alt text" title="This is title text">This placeholder should not be rendered.</drupal-entity>';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'test entity image formatter';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalget('node/' . $node->id());
    $this->assertRaw('<img src', 'IMG tag found.');
    $this->assertRaw('data-caption="test caption"', 'Caption found.');
    $this->assertRaw('data-align="left"', 'Alignment information found.');
    $this->assertTrue((bool) $this->xpath("//img[@alt='This is alt text']"), 'Alt text found');
    $this->assertTrue((bool) $this->xpath("//img[@title='This is title text']"), 'Title text found');
    $this->assertRaw('<article class="embedded-entity"', 'Embed container found.');

    // data-entity-embed-settings is replaced with
    // data-entity-embed-display-settings. Check to see if
    // data-entity-embed-settings is still working.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="entity_reference:entity_reference_label" data-entity-embed-settings=\'{"link":"0"}\' data-align="left" data-caption="test caption">This placeholder should not be rendered.</drupal-entity>';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with data-entity-embed-settings';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->getTitle(), 'Embeded node title is displayed.');
    $this->assertNoLink($this->node->getTitle(), 'Embed settings are respected.');
    $this->assertNoText($this->node->body->value, 'Embedded node exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');
    $this->assertRaw('<article class="embedded-entity"', 'Embed container found.');
  }

}
