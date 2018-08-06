<?php

namespace Drupal\xmlsitemap_custom\Tests;

use Drupal\xmlsitemap\Tests\XmlSitemapTestBase;

/**
 * Tests the functionality of xmlsitemap_custom module.
 *
 * @group xmlsitemap
 */
class XmlSitemapCustomFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['xmlsitemap_custom', 'path'];

  /**
   * The alias storage handler.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->aliasStorage = \Drupal::service('path.alias_storage');
    $this->linkStorage = \Drupal::service('xmlsitemap.link_storage');
    $this->admin_user = $this->drupalCreateUser(array('access content', 'administer xmlsitemap'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test adding custom links with wrong/private/correct paths.
   */
  public function testCustomLinks() {
    $language = $this->languageManager->getCurrentLanguage();
    // Set a path alias for the node page.
    $this->aliasStorage->save('/system/files', '/public-files', $language->getId());

    $this->drupalGet('admin/config/search/xmlsitemap/custom');
    $this->clickLink(t('Add custom link'));

    // Test an invalid path.
    $edit['loc'] = 'invalid-testing-path';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $edit['loc'])));
    $this->assertNoSitemapLink(array('type' => 'custom', 'loc' => $edit['loc']));

    // Test a path not accessible to anonymous user.
    $edit['loc'] = 'admin/people/people';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $edit['loc'])));
    $this->assertNoSitemapLink(array('type' => 'custom', 'loc' => $edit['loc']));

    // Test that the current page, which should not give a false positive for
    // $menu_item['access'] since the result has been cached already.
    $edit['loc'] = 'admin/config/search/xmlsitemap/custom/add';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $edit['loc'])));
    $this->assertNoSitemapLink(array('type' => 'custom', 'loc' => $edit['loc']));
  }

  /**
   * Test adding files as custom links.
   */
  public function testCustomFileLinks() {
    // Test an invalid file.
    $edit['loc'] = $this->randomMachineName();
    $this->drupalPostForm('admin/config/search/xmlsitemap/custom/add', $edit, t('Save'));
    $this->assertText(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $edit['loc'])));
    $this->assertNoSitemapLink(array('type' => 'custom', 'loc' => $edit['loc']));

    // Test an unaccessible file .
    $edit['loc'] = '.htaccess';
    $this->drupalPostForm('admin/config/search/xmlsitemap/custom/add', $edit, t('Save'));
    $this->assertText(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $edit['loc'])));
    $this->assertNoSitemapLink(array('type' => 'custom', 'loc' => $edit['loc']));
    // Test a valid file.
    $edit['loc'] = 'core/misc/drupal.js';
    $this->drupalPostForm('admin/config/search/xmlsitemap/custom/add', $edit, t('Save'));
    $this->assertText(t('The custom link for @link was saved.', array('@link' => $edit['loc'])));
    $links = $this->linkStorage->loadMultiple(array('type' => 'custom', 'loc' => $edit['loc']));
    $this->assertEqual(count($links), 1, t('Custom link saved in the database.'));

    //Test a duplicate url.
    $edit['loc'] = 'core/misc/drupal.js';
    $this->drupalPostForm('admin/config/search/xmlsitemap/custom/add', $edit, t('Save'));
    $this->assertText(t('There is already an existing link in the sitemap with the path @link.', array('@link' => $edit['loc'])));
    $links = $this->linkStorage->loadMultiple(array('type' => 'custom', 'loc' => $edit['loc']));
    $this->assertEqual(count($links), 1, t('Custom link saved in the database.'));
  }

}
