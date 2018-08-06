<?php

namespace Drupal\Tests\video_embed_field\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the video embed field widget.
 *
 * @group video_embed_field
 */
class WidgetTest extends BrowserTestBase {

  use EntityDisplaySetupTrait;
  use AdminUserTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'node',
    'video_embed_field',
  ];

  /**
   * Test the input widget.
   */
  public function testVideoEmbedFieldDefaultWidget() {
    $this->setupEntityDisplays();
    $this->setFormComponentSettings('video_embed_field_textfield');

    $this->drupalLogin($this->createAdminUser());
    $node_title = $this->randomMachineName();

    // Test an invalid input.
    $this->drupalGet(Url::fromRoute('node.add', ['node_type' => $this->contentTypeName])->toString());
    $this->submitForm([
      'title[0][value]' => $node_title,
      $this->fieldName . '[0][value]' => 'Some useless value.',
    ], t('Save and publish'));
    $this->assertSession()->pageTextContains('Could not find a video provider to handle the given URL.');

    // Test a valid input.
    $valid_input = 'https://vimeo.com/80896303';
    $this->submitForm([
      $this->fieldName . '[0][value]' => $valid_input,
    ], t('Save and publish'));
    $this->assertSession()->pageTextContains(sprintf('%s %s has been created.', $this->contentTypeName, $node_title));

    // Load the saved node and assert the valid value was saved into the field.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $node_title]);
    $node = array_shift($nodes);
    $this->assertEquals($node->{$this->fieldName}[0]->value, $valid_input);
  }

}
