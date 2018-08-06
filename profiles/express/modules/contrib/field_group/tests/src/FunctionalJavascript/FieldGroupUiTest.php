<?php

namespace Drupal\Tests\field_group\FunctionalJavascript;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field_group\Tests\FieldGroupTestTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Test field_group user interface.
 *
 * @group field_group
 */
class FieldGroupUiTest extends JavascriptTestBase {

  use FieldGroupTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'field_group');

  /**
   * @var string
   */
  protected $nodeType;

  public function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'bypass node access'));
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name =  Unicode::strtolower($this->randomMachineName(8)) . '_test';
    $type = NodeType::create([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $type->save();
    $this->nodeType = $type->id();
  }

  public function testCreateAndEdit() {
    foreach (['test_1', 'test_2'] as $name) {
      $group = array(
        'group_formatter' => 'details',
        'label' => 'Test 1',
        'group_name' => $name,
      );

      // Add new group on the 'Manage form display' page.
      $this->drupalPostForm('admin/structure/types/manage/' . $this->nodeType . '/form-display/add-group', $group, t('Save and continue'));
      $this->drupalPostForm(NULL, [], t('Create group'));
    }

    // Update title in group 1
    $page = $this->getSession()->getPage();
    $page->pressButton('group_test_1_group_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->fillField('fields[group_test_1][settings_edit_form][settings][label]', 'Test 1 - Update');
    $page->pressButton('Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Update title in group 2
    $page->pressButton('group_test_2_group_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->fillField('fields[group_test_2][settings_edit_form][settings][label]', 'Test 2 - Update');
    $page->pressButton('Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Open group 1 again
    $page->pressButton('group_test_1_group_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('fields[group_test_1][settings_edit_form][settings][label]', 'Test 1 - Update');
    $page->pressButton('Cancel');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->pressButton('Save');

    /** @var EntityFormDisplayInterface $display */
    $display = EntityFormDisplay::load("node.{$this->nodeType}.default");
    $this->assertSame('Test 1 - Update', $display->getThirdPartySetting('field_group', 'group_test_1')['label']);
    $this->assertSame('Test 1 - Update', $display->getThirdPartySetting('field_group', 'group_test_1')['format_settings']['label']);

    $this->assertSame('Test 2 - Update', $display->getThirdPartySetting('field_group', 'group_test_2')['label']);
    $this->assertSame('Test 2 - Update', $display->getThirdPartySetting('field_group', 'group_test_2')['format_settings']['label']);
  }

}
