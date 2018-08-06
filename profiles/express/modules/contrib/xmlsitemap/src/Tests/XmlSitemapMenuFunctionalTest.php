<?php

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of menu links.
 *
 * @group xmlsitemap
 */
class XmlSitemapMenuFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // allow anonymous user to administer menu links
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('administer menu');
    $user_role->grantPermission('access content');
    $user_role->save();

    $bundles = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_enable('menu_link_content', $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_enable('menu', $bundle_id);
    }

    $this->admin_user = $this->drupalCreateUser(array('administer menu', 'administer xmlsitemap', 'access administration pages'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Test xmlsitemap settings for menu entity.
   */
  public function testMenuSettings() {
    $this->drupalLogin($this->admin_user);

    $edit = array(
      'label' => $this->randomMachineName(),
      'id' => Unicode::strtolower($this->randomMachineName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, 'Save');

    xmlsitemap_link_bundle_settings_save('menu', $edit['id'], array('status' => 0, 'priority' => 0.5, 'changefreq' => 0));

    $this->drupalGet('admin/structure/menu/manage/' . $edit['id']);

    $menu_id = $edit['id'];
    $this->clickLink('Add link');
    $edit = array(
      'link[0][uri]' => 'node',
      'title[0][value]' => $this->randomMachineName(),
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => FALSE,
      'menu_parent' => $menu_id . ':',
      'weight[0][value]' => 0,
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $bundles = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_delete('menu_link_content', $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_delete('menu', $bundle_id);
    }

    parent::tearDown();
  }

}
