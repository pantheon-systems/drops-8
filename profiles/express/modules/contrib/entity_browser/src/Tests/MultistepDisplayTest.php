<?php

namespace Drupal\entity_browser\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the multistep display selection display.
 *
 * @group entity_browser
 */
class MultistepDisplayTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_browser', 'ctools', 'block', 'node', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests multistep display.
   */
  public function testMultistepDisplay() {
    $account = $this->drupalCreateUser([
      'administer entity browsers',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->clickLink('Add Entity browser');
    $edit = [
      'label' => 'Test entity browser',
      'id' => 'test_entity_browser',
      'display' => 'iframe',
      'widget_selector' => 'tabs',
      'selection_display' => 'multi_step_display',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');
    $this->drupalPostForm(NULL, [], 'Next');
    $this->drupalPostForm(NULL, [], 'Next');

    $this->assertText('Selection display', 'Trail is shown.');
    $this->assertText('Select button text', 'Title is correct.');
    $this->assertText('Text to display on the entity browser select button.', 'Description is correct.');
    $this->assertRaw('Use selected', 'Default text is correct.');
    $edit = [
      'entity_type' => 'file',
      'display' => 'label',
      'selection_hidden' => 0,
      'select_text' => 'Use blah selected',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');
    $this->drupalPostAjaxForm(NULL, ['widget' => 'upload'], 'widget');
    $this->drupalPostForm(NULL, [], 'Finish');

    $account = $this->drupalCreateUser([
      'access test_entity_browser entity browser pages',
    ]);
    $this->drupalLogin($account);
    // Go to the entity browser iframe link.
    $this->drupalGet('/entity-browser/iframe/test_entity_browser');
    $this->assertNoRaw('Use blah selected');

    $image = current($this->drupalGetTestFiles('image'));
    $edit = [
      'files[upload][]' => $this->container->get('file_system')->realpath($image->uri),
    ];
    $this->drupalPostForm(NULL, $edit, 'Select files');
    $this->assertRaw('Use blah selected', 'Select button is displayed if something is selected.');
  }

}
