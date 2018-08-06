<?php

namespace Drupal\xmlsitemap\Tests;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Tests the rebuild process of sitemaps.
 *
 * @group xmlsitemap
 */
class XmlSitemapRebuildTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['path', 'help', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer xmlsitemap', 'access administration pages', 'access site reports', 'administer users', 'administer permissions'));

    $this->drupalPlaceBlock('help_block', array('region' => 'help'));

    // allow anonymous user to view user profiles
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('access user profiles');
    $user_role->save();
  }

  /**
   * Test sitemap rebuild process.
   */
  public function testSimpleRebuild() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/xmlsitemap/rebuild');
    $this->assertResponse(200);
    $this->assertText(t("This action rebuilds your site's XML sitemap and regenerates the cached files, and may be a lengthy process. If you just installed XML sitemap, this can be helpful to import all your site's content into the sitemap. Otherwise, this should only be used in emergencies."));

    $this->drupalPostForm(NULL, array(), t('Save configuration'));
    $this->assertText('The sitemap links were rebuilt.');
  }

  /**
   * Test if user links are included in sitemap after rebuild.
   */
  public function testUserLinksRebuild() {
    xmlsitemap_link_bundle_settings_save('user', 'user', array('status' => 1, 'priority' => 0.4, 'changefreq' => XMLSITEMAP_FREQUENCY_MONTHLY));

    $dummy_user = $this->drupalCreateUser(array());
    $this->drupalLogin($this->admin_user);
    $this->drupalPostForm('admin/config/search/xmlsitemap/rebuild', array(), t('Save configuration'));
    $this->assertText('The sitemap links were rebuilt.');
    $this->assertSitemapLinkValues('user', $dummy_user->id(), array('status' => 1, 'priority' => 0.4, 'changefreq' => XMLSITEMAP_FREQUENCY_MONTHLY, 'access' => 1));
    $this->drupalGet('sitemap.xml');
    $this->assertRaw("user/{$dummy_user->id()}");
  }

}
