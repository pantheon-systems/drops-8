<?php

namespace Drupal\media_entity\Tests\Views;

use Drupal\views\Views;
use Drupal\views\Tests\Wizard\WizardTestBase;

/**
 * Tests the media entity type integration into the wizard.
 *
 * @group media_entity
 * @see \Drupal\media_entity\Plugin\views\wizard\Media
 * @see \Drupal\media_entity\Plugin\views\wizard\MediaRevision
 */
class WizardTest extends WizardTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['media_entity'];

  /**
   * Tests adding a view of media.
   */
  public function testMediaWizard() {
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['show[wizard_key]'] = 'media';
    $view['page[create]'] = TRUE;
    $view['page[path]'] = $this->randomMachineName(16);

    // Just triggering the saving should automatically choose a proper row
    // plugin.
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->assertUrl('admin/structure/views/view/' . $view['id'], [], 'Make sure the view saving was successful and the browser got redirected to the edit page.');

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $view = Views::getView($view['id']);
    $view->initHandlers();
    $row = $view->display_handler->getOption('row');
    $this->assertEqual($row['type'], 'fields');

    // Check for the default filters.
    $this->assertEqual($view->filter['status']->table, 'media_field_data');
    $this->assertEqual($view->filter['status']->field, 'status');
    $this->assertTrue($view->filter['status']->value);

    // Check for the default fields.
    $this->assertEqual($view->field['name']->table, 'media_field_data');
    $this->assertEqual($view->field['name']->field, 'name');
  }

  /**
   * Tests adding a view of media revisions.
   */
  public function testMediaRevisionWizard() {
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['show[wizard_key]'] = 'media_revision';
    $view['page[create]'] = TRUE;
    $view['page[path]'] = $this->randomMachineName(16);

    // Just triggering the saving should automatically choose a proper row
    // plugin.
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->assertUrl('admin/structure/views/view/' . $view['id'], [], 'Make sure the view saving was successful and the browser got redirected to the edit page.');

    $user = $this->drupalCreateUser(['view all revisions']);
    $this->drupalLogin($user);

    $view = Views::getView($view['id']);
    $view->initHandlers();
    $row = $view->display_handler->getOption('row');
    $this->assertEqual($row['type'], 'fields');

    // Check for the default filters.
    $this->assertEqual($view->filter['status']->table, 'media_field_revision');
    $this->assertEqual($view->filter['status']->field, 'status');
    $this->assertTrue($view->filter['status']->value);

    // Check for the default fields.
    $this->assertEqual($view->field['name']->table, 'media_field_revision');
    $this->assertEqual($view->field['name']->field, 'name');
    $this->assertEqual($view->field['changed']->table, 'media_field_revision');
    $this->assertEqual($view->field['changed']->field, 'changed');
  }

}
