<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileFieldDisplayTest.
 */

namespace Drupal\file\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;

/**
 * Tests the display of file fields in node and views.
 *
 * @group file
 */
class FileFieldDisplayTest extends FileFieldTestBase {

  /**
   * Tests normal formatter display on node display.
   */
  function testNodeDisplay() {
    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';
    $field_storage_settings = array(
      'display_field' => '1',
      'display_default' => '1',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );
    $field_settings = array(
      'description_field' => '1',
    );
    $widget_settings = array();
    $this->createFileField($field_name, 'node', $type_name, $field_storage_settings, $field_settings, $widget_settings);

    // Create a new node *without* the file field set, and check that the field
    // is not shown for each node display.
    $node = $this->drupalCreateNode(array('type' => $type_name));
    // Check file_default last as the assertions below assume that this is the
    // case.
    $file_formatters = array('file_table', 'file_url_plain', 'hidden', 'file_default');
    foreach ($file_formatters as $formatter) {
      $edit = array(
        "fields[$field_name][type]" => $formatter,
      );
      $this->drupalPostForm("admin/structure/types/manage/$type_name/display", $edit, t('Save'));
      $this->drupalGet('node/' . $node->id());
      $this->assertNoText($field_name, format_string('Field label is hidden when no file attached for formatter %formatter', array('%formatter' => $formatter)));
    }

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Check that the default formatter is displaying with the file name.
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $file_link = array(
      '#theme' => 'file_link',
      '#file' => $node_file,
    );
    $default_output = \Drupal::service('renderer')->renderRoot($file_link);
    $this->assertRaw($default_output, 'Default formatter displaying correctly on full node view.');

    // Turn the "display" option off and check that the file is no longer displayed.
    $edit = array($field_name . '[0][display]' => FALSE);
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save and keep published'));

    $this->assertNoRaw($default_output, 'Field is hidden when "display" option is unchecked.');

    // Add a description and make sure that it is displayed.
    $description = $this->randomMachineName();
    $edit = array(
      $field_name . '[0][description]' => $description,
      $field_name . '[0][display]' => TRUE,
    );
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save and keep published'));
    $this->assertText($description);

    // Test that fields appear as expected after during the preview.
    // Add a second file.
    $name = 'files[' . $field_name . '_1][]';
    $edit[$name] = drupal_realpath($test_file->getFileUri());

    // Uncheck the display checkboxes and go to the preview.
    $edit[$field_name . '[0][display]'] = FALSE;
    $edit[$field_name . '[1][display]'] = FALSE;
    $this->drupalPostForm("node/$nid/edit", $edit, t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->assertRaw($field_name . '[0][display]', 'First file appears as expected.');
    $this->assertRaw($field_name . '[1][display]', 'Second file appears as expected.');
  }

  /**
   * Tests default display of File Field.
   */
  function testDefaultFileFieldDisplay() {
    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';
    $field_storage_settings = array(
      'display_field' => '1',
      'display_default' => '0',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );
    $field_settings = array(
      'description_field' => '1',
    );
    $widget_settings = array();
    $this->createFileField($field_name, 'node', $type_name, $field_storage_settings, $field_settings, $widget_settings);

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    $this->drupalGet('node/' . $nid . '/edit');
    $this->assertFieldByXPath('//input[@type="checkbox" and @name="' . $field_name . '[0][display]"]', NULL, 'Default file display checkbox field exists.');
    $this->assertFieldByXPath('//input[@type="checkbox" and @name="' . $field_name . '[0][display]" and not(@checked)]', NULL, 'Default file display is off.');
  }

  /**
   * Tests description toggle for field instance configuration.
   */
  function testDescToggle() {
    $type_name = 'test';
    $field_type = 'file';
    $field_name = strtolower($this->randomMachineName());
    // Use the UI to add a new content type that also contains a file field.
    $edit = array(
      'name' => $type_name,
      'type' => $type_name,
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save and manage fields'));
    $edit = array(
      'new_storage_type' => $field_type,
      'field_name' => $field_name,
      'label' => $this->randomString(),
    );
    $this->drupalPostForm('/admin/structure/types/manage/' . $type_name . '/fields/add-field', $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, array(), t('Save field settings'));
    // Ensure the description field is selected on the field instance settings
    // form. That's what this test is all about.
    $edit = array(
      'settings[description_field]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    // Add a node of our new type and upload a file to it.
    $file = current($this->drupalGetTestFiles('text'));
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      'files[field_' . $field_name . '_0]' => drupal_realpath($file->uri),
    );
    $this->drupalPostForm('node/add/' . $type_name, $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertText(t('The description may be used as the label of the link to the file.'));
  }

}
