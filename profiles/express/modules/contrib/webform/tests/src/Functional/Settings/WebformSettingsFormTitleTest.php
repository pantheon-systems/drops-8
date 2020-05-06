<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform form title.
 *
 * @group Webform
 */
class WebformSettingsFormTitleTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'node'];

  /**
   * Tests form title.
   */
  public function testTitle() {
    $node = $this->drupalCreateNode(['title' => 'test_node']);

    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_webform',
      'title' => 'test_webform',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
      'settings' => [
        'form_prepopulate_source_entity' => TRUE,
      ],
    ]);
    $webform->save();

    $options = ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]];

    /**************************************************************************/

    // Check webform title.
    $this->drupalGet('/webform/test_webform');
    $this->assertRaw('<title>test_webform | Drupal</title>');

    // Check (default) both title.
    $this->drupalGet('/webform/test_webform', $options);
    $this->assertRaw('<title>test_node: test_webform | Drupal</title>');

    // Check webform and source entity title.
    $webform
      ->setSetting('form_title', WebformInterface::TITLE_WEBFORM_SOURCE_ENTITY)
      ->save();
    $this->drupalGet('/webform/test_webform', $options);
    $this->assertRaw('<title>test_webform: test_node | Drupal</title>');

    // Check source entity title.
    $webform
      ->setSetting('form_title', WebformInterface::TITLE_SOURCE_ENTITY)
      ->save();
    $this->drupalGet('/webform/test_webform', $options);
    $this->assertRaw('<title>test_node | Drupal</title>');

    // Check webform title.
    $webform
      ->setSetting('form_title', WebformInterface::TITLE_WEBFORM)
      ->save();
    $this->drupalGet('/webform/test_webform', $options);
    $this->assertRaw('<title>test_webform | Drupal</title>');

    // Check duplicate titles.
    $webform
      ->setSetting('form_title', WebformInterface::TITLE_SOURCE_ENTITY_WEBFORM)
      ->save();
    $this->drupalGet('/webform/test_webform', $options);
    $this->assertRaw('<title>test_node: test_webform | Drupal</title>');
    $webform->set('title', 'test_node')
      ->save();
    $this->drupalGet('/webform/test_webform', $options);
    $this->assertRaw('<title>test_node | Drupal</title>');
  }

}
