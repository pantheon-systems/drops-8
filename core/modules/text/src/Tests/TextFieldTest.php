<?php

/**
 * @file
 * Contains \Drupal\text\Tests\TextFieldTest.
 */

namespace Drupal\text\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Tests\String\StringFieldTest;

/**
 * Tests the creation of text fields.
 *
 * @group text
 */
class TextFieldTest extends StringFieldTest {

  /**
   * A user with relevant administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer filters'));
  }

  // Test fields.

  /**
   * Test text field validation.
   */
  function testTextFieldValidation() {
    // Create a field with settings to validate.
    $max_length = 3;
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'text',
      'settings' => array(
        'max_length' => $max_length,
      )
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ))->save();

    // Test validation with valid and invalid values.
    $entity = entity_create('entity_test');
    for ($i = 0; $i <= $max_length + 2; $i++) {
      $entity->{$field_name}->value = str_repeat('x', $i);
      $violations = $entity->{$field_name}->validate();
      if ($i <= $max_length) {
        $this->assertEqual(count($violations), 0, "Length $i does not cause validation error when max_length is $max_length");
      }
      else {
        $this->assertEqual(count($violations), 1, "Length $i causes validation error when max_length is $max_length");
      }
    }
  }

  /**
   * Test required long text with file upload.
   */
  function testRequiredLongTextWithFileUpload() {
    // Create a text field.
    $text_field_name = 'text_long';
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $text_field_name,
      'entity_type' => 'entity_test',
      'type' => 'text_with_summary',
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
      'required' => TRUE,
    ))->save();

    // Create a file field.
    $file_field_name = 'file_field';
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $file_field_name,
      'entity_type' => 'entity_test',
      'type' => 'file'
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
    ))->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($text_field_name, array(
        'type' => 'text_textarea_with_summary',
      ))
      ->setComponent($file_field_name, array(
        'type' => 'file_generic',
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($text_field_name)
      ->setComponent($file_field_name)
      ->save();

    $test_file = current($this->drupalGetTestFiles('text'));
    $edit['files[file_field_0]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('entity_test/add', $edit, 'Upload');
    $this->assertResponse(200);
    $edit = array(
      'text_long[0][value]' => 'Long text'
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
    $this->drupalGet('entity_test/1');
    $this->assertText('Long text');
  }

  /**
   * Test widgets.
   */
  function testTextfieldWidgets() {
    $this->_testTextfieldWidgets('text', 'text_textfield');
    $this->_testTextfieldWidgets('text_long', 'text_textarea');
  }

  /**
   * Test widgets + 'formatted_text' setting.
   */
  function testTextfieldWidgetsFormatted() {
    $this->_testTextfieldWidgetsFormatted('text', 'text_textfield');
    $this->_testTextfieldWidgetsFormatted('text_long', 'text_textarea');
  }

  /**
   * Helper function for testTextfieldWidgetsFormatted().
   */
  function _testTextfieldWidgetsFormatted($field_type, $widget_type) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Create a field.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $field_type
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
    ))->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => $widget_type,
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name)
      ->save();

    // Disable all text formats besides the plain text fallback format.
    $this->drupalLogin($this->adminUser);
    foreach (filter_formats() as $format) {
      if (!$format->isFallbackFormat()) {
        $this->drupalPostForm('admin/config/content/formats/manage/' . $format->id() . '/disable', array(), t('Disable'));
      }
    }
    $this->drupalLogin($this->webUser);

    // Display the creation form. Since the user only has access to one format,
    // no format selector will be displayed.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertNoFieldByName("{$field_name}[0][format]", '', 'Format selector is not displayed');

    // Submit with data that should be filtered.
    $value = '<em>' . $this->randomMachineName() . '</em>';
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');

    // Display the entity.
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->setRawContent($renderer->renderRoot($content));
    $this->assertNoRaw($value, 'HTML tags are not displayed.');
    $this->assertEscaped($value, 'Escaped HTML is displayed correctly.');

    // Create a new text format that does not escape HTML, and grant the user
    // access to it.
    $this->drupalLogin($this->adminUser);
    $edit = array(
      'format' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
    );
    $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
    filter_formats_reset();
    $format = entity_load('filter_format', $edit['format']);
    $format_id = $format->id();
    $permission = $format->getPermissionName();
    $roles = $this->webUser->getRoles();
    $rid = $roles[0];
    user_role_grant_permissions($rid, array($permission));
    $this->drupalLogin($this->webUser);

    // Display edition form.
    // We should now have a 'text format' selector.
    $this->drupalGet('entity_test/manage/' . $id);
    $this->assertFieldByName("{$field_name}[0][value]", NULL, 'Widget is displayed');
    $this->assertFieldByName("{$field_name}[0][format]", NULL, 'Format selector is displayed');

    // Edit and change the text format to the new one that was created.
    $edit = array(
      "{$field_name}[0][format]" => $format_id,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', array('@id' => $id)), 'Entity was updated');

    // Display the entity.
    $this->container->get('entity.manager')->getStorage('entity_test')->resetCache(array($id));
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->setRawContent($renderer->renderRoot($content));
    $this->assertRaw($value, 'Value is displayed unfiltered');
  }

}
