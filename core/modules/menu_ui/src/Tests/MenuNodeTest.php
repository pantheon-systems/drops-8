<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\MenuNodeTest.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Add, edit, and delete a node with menu link.
 *
 * @group menu_ui
 */
class MenuNodeTest extends WebTestBase {

  /**
   * An editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_ui', 'test_page_test', 'node', 'block');

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:main');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->editor = $this->drupalCreateUser(array(
      'access administration pages',
      'administer content types',
      'administer menu',
      'create page content',
      'edit any page content',
      'delete any page content',
    ));
    $this->drupalLogin($this->editor);
  }

  /**
   * Test creating, editing, deleting menu links via node form widget.
   */
  function testMenuNodeFormWidget() {
    // Verify that cacheability metadata is bubbled from the menu link tree
    // access checking that is performed when determining the "default parent
    // item" options in menu_ui_form_node_type_form_alter(). The "log out" link
    // adds the "user.roles:authenticated" cache context.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertCacheContext('user.roles:authenticated');

    // Disable the default main menu, so that no menus are enabled.
    $edit = array(
      'menu_options[main]' => FALSE,
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));

    // Verify that no menu settings are displayed and nodes can be created.
    $this->drupalGet('node/add/page');
    $this->assertText(t('Create Basic page'));
    $this->assertNoText(t('Menu settings'));
    $node_title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $node_title,
      'body[0][value]' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($node_title);
    $this->assertEqual($node->getTitle(), $edit['title[0][value]']);

    // Test that we cannot set a menu item from a menu that is not set as
    // available.
    $edit = array(
      'menu_options[tools]' => 1,
      'menu_parent' => 'main:',
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertText(t('The selected menu item is not under one of the selected menus.'));
    $this->assertNoRaw(t('The content type %name has been updated.', array('%name' => 'Basic page')));

    // Enable Tools menu as available menu.
    $edit = array(
      'menu_options[main]' => 1,
      'menu_options[tools]' => 1,
      'menu_parent' => 'main:',
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %name has been updated.', array('%name' => 'Basic page')));

    // Test that we can preview a node that will create a menu item.
    $edit = array(
      'title[0][value]' => $node_title,
      'menu[enabled]' => 1,
      'menu[title]' => 'Test preview',
    );
    $this->drupalPostForm('node/add/page', $edit, t('Preview'));

    // Create a node.
    $node_title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $node_title,
      'body[0][value]' => $this->randomString(),
    );
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($node_title);
    // Assert that there is no link for the node.
    $this->drupalGet('test-page');
    $this->assertNoLink($node_title);

    // Edit the node, enable the menu link setting, but skip the link title.
    $edit = array(
      'menu[enabled]' => 1,
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    // Assert that there is no link for the node.
    $this->drupalGet('test-page');
    $this->assertNoLink($node_title);

    // Use not only the save button, but also the two special buttons:
    // 'Save and publish' as well as 'Save and keep published'.
    // These buttons just appear for 'administer nodes' users.
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer nodes',
      'administer menu',
      'create page content',
      'edit any page content',
    ]);
    $this->drupalLogin($admin_user);
    foreach (['Save and unpublish' => FALSE, 'Save and keep unpublished' => FALSE, 'Save and publish' => TRUE, 'Save and keep published' => TRUE] as $submit => $visible) {
      $edit = [
        'menu[enabled]' => 1,
        'menu[title]' => $node_title,
      ];
      $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, $submit);
      // Assert that the link exists.
      $this->drupalGet('test-page');
      if ($visible) {
        $this->assertLink($node_title, 0, 'Found a menu link after submitted with ' . $submit);
      }
      else {
        $this->assertNoLink($node_title, 'Found no menu link after submitted with ' . $submit);
      }
    }

    // Log back in as normal user.
    $this->drupalLogin($this->editor);
    // Edit the node and create a menu link.
    $edit = array(
      'menu[enabled]' => 1,
      'menu[title]' => $node_title,
      'menu[weight]' => 17,
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    // Assert that the link exists.
    $this->drupalGet('test-page');
    $this->assertLink($node_title);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldById('edit-menu-weight', 17, 'Menu weight correct in edit form');

    // Disable the menu link, then edit the node--the link should stay disabled.
    $link_id = menu_ui_get_menu_link_defaults($node)['entity_id'];
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $link */
    $link = MenuLinkContent::load($link_id);
    $link->set('enabled', FALSE);
    $link->save();
    $this->drupalPostForm($node->urlInfo('edit-form'), $edit, t('Save'));
    $link = MenuLinkContent::load($link_id);
    $this->assertFalse($link->isEnabled(), 'Saving a node with a disabled menu link keeps the menu link disabled.');

    // Edit the node and remove the menu link.
    $edit = array(
      'menu[enabled]' => FALSE,
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    // Assert that there is no link for the node.
    $this->drupalGet('test-page');
    $this->assertNoLink($node_title);

    // Add a menu link to the Administration menu.
    $item = entity_create('menu_link_content', array(
      'link' => [['uri' => 'entity:node/' . $node->id()]],
      'title' => $this->randomMachineName(16),
      'menu_name' => 'admin',
    ));
    $item->save();

    // Assert that disabled Administration menu is not shown on the
    // node/$nid/edit page.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertText('Provide a menu link', 'Link in not allowed menu not shown in node edit form');
    // Assert that the link is still in the Administration menu after save.
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $link = MenuLinkContent::load($item->id());
    $this->assertTrue($link, 'Link in not allowed menu still exists after saving node');

    // Move the menu link back to the Tools menu.
    $item->menu_name->value = 'tools';
    $item->save();
    // Create a second node.
    $child_node = $this->drupalCreateNode(array('type' => 'article'));
    // Assign a menu link to the second node, being a child of the first one.
    $child_item = entity_create('menu_link_content', array(
      'link' => [['uri' => 'entity:node/' . $child_node->id()]],
      'title' => $this->randomMachineName(16),
      'parent' => $item->getPluginId(),
      'menu_name' => $item->getMenuName(),
    ));
    $child_item->save();
    // Edit the first node.
    $this->drupalGet('node/'. $node->id() .'/edit');
    // Assert that it is not possible to set the parent of the first node to itself or the second node.
    $this->assertNoOption('edit-menu-menu-parent', 'tools:'. $item->getPluginId());
    $this->assertNoOption('edit-menu-menu-parent', 'tools:'. $child_item->getPluginId());
    // Assert that unallowed Administration menu is not available in options.
    $this->assertNoOption('edit-menu-menu-parent', 'admin:');
  }
}
