<?php

namespace Drupal\Tests\token\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\system\Entity\Menu;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests menu tokens.
 *
 * @group token
 */
class TokenMenuTest extends TokenTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'menu_ui',
    'node',
    'block',
    'language',
    'block_content',
    'content_translation',
  ];

  function testMenuTokens() {
    // Make sure we have a body field on the node type.
    $this->drupalCreateContentType(['type' => 'page']);
    // Add a menu.
    $menu = Menu::create([
      'id' => 'main-menu',
      'label' => 'Main menu',
      'description' => 'The <em>Main</em> menu is used on many sites to show the major sections of the site, often in a top navigation bar.',
    ]);
    $menu->save();

    // Place the menu block.
    $this->drupalPlaceBlock('system_menu_block:main-menu');

    // Add a root link.
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $root_link */
    $root_link = MenuLinkContent::create([
      'link' => ['uri' => 'internal:/admin'],
      'title' => 'Administration',
      'menu_name' => 'main-menu',
    ]);
    $root_link->save();

    // Add another link with the root link as the parent.
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $parent_link */
    $parent_link = MenuLinkContent::create([
      'link' => ['uri' => 'internal:/admin/config'],
      'title' => 'Configuration',
      'menu_name' => 'main-menu',
      'parent' => $root_link->getPluginId(),
    ]);
    $parent_link->save();

    // Test menu link tokens.
    $tokens = [
      'id' => $parent_link->getPluginId(),
      'title' => 'Configuration',
      'menu' => 'Main menu',
      'menu:name' => 'Main menu',
      'menu:machine-name' => $menu->id(),
      'menu:description' => 'The <em>Main</em> menu is used on many sites to show the major sections of the site, often in a top navigation bar.',
      'menu:menu-link-count' => '2',
      'menu:edit-url' => Url::fromRoute('entity.menu.edit_form', ['menu' => 'main-menu'], ['absolute' => TRUE])->toString(),
      'url' => Url::fromRoute('system.admin_config', [], ['absolute' => TRUE])->toString(),
      'url:absolute' => Url::fromRoute('system.admin_config', [], ['absolute' => TRUE])->toString(),
      'url:relative' => Url::fromRoute('system.admin_config', [], ['absolute' => FALSE])->toString(),
      'url:path' => '/admin/config',
      'url:alias' => '/admin/config',
      'edit-url' => Url::fromRoute('entity.menu_link_content.canonical', ['menu_link_content' => $parent_link->id()], ['absolute' => TRUE])->toString(),
      'parent' => 'Administration',
      'parent:id' => $root_link->getPluginId(),
      'parent:title' => 'Administration',
      'parent:menu' => 'Main menu',
      'parent:parent' => NULL,
      'parents' => 'Administration',
      'parents:count' => 1,
      'parents:keys' => $root_link->getPluginId(),
      'root' => 'Administration',
      'root:id' => $root_link->getPluginId(),
      'root:parent' => NULL,
      'root:root' => NULL,
    ];
    $this->assertTokens('menu-link', ['menu-link' => $parent_link], $tokens);

    // Add a node.
    $node = $this->drupalCreateNode();

    // Allow main menu for this node type.
    //$this->config('menu.entity.node.' . $node->getType())->set('available_menus', ['main-menu'])->save();

    // Add a node menu link.
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $node_link */
    $node_link = MenuLinkContent::create([
      'link' => ['uri' => 'entity:node/' . $node->id()],
      'title' => 'Node link',
      'parent' => $parent_link->getPluginId(),
      'menu_name' => 'main-menu',
    ]);
    $node_link->save();

    // Test [node:menu] tokens.
    $tokens = [
      'menu-link' => 'Node link',
      'menu-link:id' => $node_link->getPluginId(),
      'menu-link:title' => 'Node link',
      'menu-link:menu' => 'Main menu',
      'menu-link:url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      'menu-link:url:path' => '/node/' . $node->id(),
      'menu-link:edit-url' => $node_link->url('edit-form', ['absolute' => TRUE]),
      'menu-link:parent' => 'Configuration',
      'menu-link:parent:id' => $parent_link->getPluginId(),
      'menu-link:parents' => 'Administration, Configuration',
      'menu-link:parents:count' => 2,
      'menu-link:parents:keys' => $root_link->getPluginId() . ', ' . $parent_link->getPluginId(),
      'menu-link:root' => 'Administration',
      'menu-link:root:id' => $root_link->getPluginId(),
    ];
    $this->assertTokens('node', ['node' => $node], $tokens);

    // Reload the node which will not have $node->menu defined and re-test.
    $loaded_node = Node::load($node->id());
    $this->assertTokens('node', ['node' => $loaded_node], $tokens);

    // Regression test for http://drupal.org/node/1317926 to ensure the
    // original node object is not changed when calling menu_node_prepare().
    $this->assertTrue(!isset($loaded_node->menu), t('The $node->menu property was not modified during token replacement.'), 'Regression');

    // Now add a node with a menu-link from the UI and ensure it works.
    $this->drupalLogin($this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'administer menu',
      'administer nodes',
      'administer content types',
      'access administration pages',
    ]));
    // Setup node type menu options.
    $edit = [
      'menu_options[main-menu]' => 1,
      'menu_options[main]' => 1,
      'menu_parent' => 'main-menu:',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));

    // Use a menu-link token in the body.
    $this->drupalGet('node/add/page');
    $this->drupalPostForm(NULL, [
      // This should get replaced on save.
      // @see token_module_test_node_presave()
      'title[0][value]' => 'Node menu title test',
      'body[0][value]' => 'This is a [node:menu-link:title] token to the menu link title',
      'menu[enabled]' => 1,
      'menu[title]' => 'Test preview',
    ], t('Save'));
    $node = $this->drupalGetNodeByTitle('Node menu title test');
    $this->assertEquals('This is a Test preview token to the menu link title', $node->body->value);

    // Disable the menu link, save the node and verify that the menu link is
    // no longer displayed.
    $link = menu_ui_get_menu_link_defaults($node);
    $this->drupalPostForm('admin/structure/menu/manage/main-menu', ['links[menu_plugin_id:' . $link['id'] . '][enabled]' => FALSE], t('Save'));
    $this->assertText('Menu Main menu has been updated.');
    $this->drupalPostForm('node/' . $node->id() . '/edit', [], t('Save'));
    $this->assertNoLink('Test preview');

    // Now test a parent link and token.
    $this->drupalGet('node/add/page');
    // Make sure that the previous node save didn't result in two menu-links
    // being created by the computed menu-link ER field.
    // @see token_entity_base_field_info()
    // @see token_node_menu_link_submit()
    $selects = $this->cssSelect('select[name="menu[menu_parent]"]');
    $select = reset($selects);
    $options = $this->getAllOptions($select);
    // Filter to items with title containing 'Test preview'.
    $options = array_filter($options, function (NodeElement $element) {
      return strpos($element->getText(), 'Test preview') !== FALSE;
    });
    $this->assertCount(1, $options);
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Node menu title parent path test',
      'body[0][value]' => 'This is a [node:menu-link:parent:url:path] token to the menu link parent',
      'menu[enabled]' => 1,
      'menu[title]' => 'Child link',
      'menu[menu_parent]' => 'main-menu:' . $parent_link->getPluginId(),
    ], t('Save'));
    $node = $this->drupalGetNodeByTitle('Node menu title parent path test');
    $this->assertEquals('This is a /admin/config token to the menu link parent', $node->body->value);

    // Now edit the node and update the parent and title.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [
      'menu[menu_parent]' => 'main-menu:' . $node_link->getPluginId(),
      'title[0][value]' => 'Node menu title edit parent path test',
      'body[0][value]' => 'This is a [node:menu-link:parent:url:path] token to the menu link parent',
    ], t('Save'));
    $node = $this->drupalGetNodeByTitle('Node menu title edit parent path test', TRUE);
    $this->assertEquals(sprintf('This is a /node/%d token to the menu link parent', $loaded_node->id()), $node->body->value);

    // Make sure that the previous node edit didn't result in two menu-links
    // being created by the computed menu-link ER field.
    // @see token_entity_base_field_info()
    // @see token_node_menu_link_submit()
    $this->drupalGet('node/add/page');
    $selects = $this->cssSelect('select[name="menu[menu_parent]"]');
    $select = reset($selects);
    $options = $this->getAllOptions($select);
    // Filter to items with title containing 'Test preview'.
    $options = array_filter($options, function (NodeElement $item) {
      return strpos($item->getText(), 'Child link') !== FALSE;
    });
    $this->assertCount(1, $options);

    // Now add a new node with no menu.
    $this->drupalGet('node/add/page');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Node menu adding menu later test',
      'body[0][value]' => 'Going to add a menu link on edit',
      'menu[enabled]' => 0,
    ], t('Save'));
    $node = $this->drupalGetNodeByTitle('Node menu adding menu later test');
    // Now edit it and add a menu item.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Node menu adding menu later test',
      'body[0][value]' => 'This is a [node:menu-link:parent:url:path] token to the menu link parent',
      'menu[enabled]' => 1,
      'menu[title]' => 'Child link',
      'menu[menu_parent]' => 'main-menu:' . $parent_link->getPluginId(),
    ], t('Save'));
    $node = $this->drupalGetNodeByTitle('Node menu adding menu later test', TRUE);
    $this->assertEquals('This is a /admin/config token to the menu link parent', $node->body->value);
    // And make sure the menu link exists with the right URI.
    $link = menu_ui_get_menu_link_defaults($node);
    $this->assertTrue(!empty($link['entity_id']));
    $query = \Drupal::entityQuery('menu_link_content')
      ->condition('link.uri', 'entity:node/' . $node->id())
      ->sort('id', 'ASC')
      ->range(0, 1);
    $result = $query->execute();
    $this->assertTrue($result);

    // Create a node with a menu link and create 2 menu links linking to this
    // node after. Verify that the menu link provided by the node has priority.
    $node_title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $node_title,
      'menu[enabled]' => 1,
      'menu[title]' => 'menu link provided by node',
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertText('page ' . $node_title . ' has been created');
    $node = $this->drupalGetNodeByTitle($node_title);

    $menu_ui_link1 = MenuLinkContent::create([
      'link' => ['uri' => 'entity:node/' . $node->id()],
      'title' => 'menu link 1 provided by menu ui',
      'menu_name' => 'main-menu',
    ]);
    $menu_ui_link1->save();

    $menu_ui_link2 = MenuLinkContent::create([
      'link' => ['uri' => 'entity:node/' . $node->id()],
      'title' => 'menu link 2 provided by menu ui',
      'menu_name' => 'main-menu',
    ]);
    $menu_ui_link2->save();

    $tokens = [
      'menu-link' => 'menu link provided by node',
      'menu-link:title' => 'menu link provided by node',
    ];
    $this->assertTokens('node', ['node' => $node], $tokens);
  }

  /**
   * Tests that the module doesn't affect integrity of the menu, when
   * translating them and that menu links tokens are correct.
   */
  function testMultilingualMenu() {
    // Place the menu block.
    $this->drupalPlaceBlock('system_menu_block:main');

    // Add a second language.
    $language = ConfigurableLanguage::create([
      'id' => 'de',
      'label' => 'German',
    ]);
    $language->save();

    // Create the article content type.
    $node_type = NodeType::create([
      'type' => 'article',
    ]);
    $node_type->save();

    $permissions = [
      'access administration pages',
      'administer content translation',
      'administer content types',
      'administer languages',
      'create content translations',
      'create article content',
      'edit any article content',
      'translate any entity',
      'administer menu',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));

    // Enable translation for articles and menu links.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'entity_types[menu_link_content]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][fields][title]' => TRUE,
      'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText('Settings successfully updated.');

    // Create an english node with an english menu.
    $this->drupalGet('/node/add/article');
    $edit = [
      'title[0][value]' => 'English test node with menu',
      'menu[enabled]' => TRUE,
      'menu[title]' => 'English menu title',
    ];
    $this->drupalPostForm('/node/add/article', $edit, t('Save'));
    $this->assertText('English test node with menu has been created.');

    // Add a german translation.
    $this->drupalGet('node/1/translations');
    $this->clickLink('Add');
    $edit = [
      'title[0][value]' => 'German test node with menu',
      'menu[enabled]' => TRUE,
      'menu[title]' => 'German menu title',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $this->assertText('German test node with menu has been updated.');

    // Verify that the menu links are correct.
    $this->drupalGet('node/1');
    $this->assertLink('English menu title');
    $this->drupalGet('de/node/1');
    $this->assertLink('German menu title');

    // Verify that tokens are correct.
    $node = Node::load(1);
    $this->assertTokens('node', ['node' => $node], ['menu-link' => 'English menu title']);
    $this->assertTokens('node', ['node' => $node], [
      'menu-link' => 'German menu title',
      'menu-link:title' => 'German menu title',
    ], ['langcode' => 'de']);

    // Get the menu link and create a child menu link to assert parent and root
    // tokens.
    $url = $node->toUrl();
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $links = $menu_link_manager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters());
    $link = reset($links);

    $base_options = [
      'provider' => 'menu_test',
      'menu_name' => 'menu_test',
    ];
    $child_1 = $base_options + [
        'title' => 'child_1 title EN',
        'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child_1'],
        'parent' => $link->getPluginId(),
        'langcode' => 'en',
      ];
    $child_1 = MenuLinkContent::create($child_1);
    $child_1->save();

    // Add the german translation.
    $child_1->addTranslation('de', ['title' => 'child_1 title DE'] + $child_1->toArray());
    $child_1->save();

    $this->assertTokens('menu-link', ['menu-link' => $child_1], [
      'title' => 'child_1 title EN',
      'parents' => 'English menu title',
      'root' => 'English menu title',
    ]);
    $this->assertTokens('menu-link', ['menu-link' => $child_1], [
      'title' => 'child_1 title DE',
      'parents' => 'German menu title',
      'root' => 'German menu title',
    ], ['langcode' => 'de']);
  }

  /**
   * Tests menu link parents token.
   */
  public function testMenuLinkParentsToken() {
    // Create a menu with a simple link hierarchy :
    // - parent
    //   - child-1
    //      - child-1-1
    Menu::create([
      'id' => 'menu_test',
      'label' => 'Test menu',
    ])->save();
    $base_options = [
      'provider' => 'menu_test',
      'menu_name' => 'menu_test',
    ];
    $parent = $base_options + [
        'title' => 'parent title',
        'link' => ['uri' => 'internal:/menu-test/hierarchy/parent'],
    ];
    $parent = MenuLinkContent::create($parent);
    $parent->save();
    $child_1 = $base_options + [
        'title' => 'child_1 title',
        'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child_1'],
        'parent' => $parent->getPluginId(),
    ];
    $child_1 = MenuLinkContent::create($child_1);
    $child_1->save();
    $child_1_1 = $base_options + [
        'title' => 'child_1_1 title',
        'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child_1/child_1_1'],
        'parent' => $child_1->getPluginId(),
    ];
    $child_1_1 = MenuLinkContent::create($child_1_1);
    $child_1_1->save();

    $this->assertTokens('menu-link', ['menu-link' => $child_1_1], ['parents' => 'parent title, child_1 title']);

    // Change the parent of child_1_1 to 'parent' at the entity level.
    $child_1_1->parent->value = $parent->getPluginId();
    $child_1_1->save();

    $this->assertTokens('menu-link', ['menu-link' => $child_1_1], ['parents' => 'parent title']);

    // Change the parent of child_1_1 to 'main', at the entity level.
    $child_1_1->parent->value = '';
    $child_1_1->save();

    // The token shouldn't have been generated; the menu link has no parent.
    $this->assertNoTokens('menu-link', ['menu-link' => $child_1_1], ['parents']);
  }

}
