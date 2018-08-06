<?php

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of user links.
 *
 * @group xmlsitemap
 */
class XmlSitemapUserFunctionalTest extends XmlSitemapTestBase {

  protected $accounts = array();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // allow anonymous user to view user profiles
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('access user profiles');
    $user_role->save();

    xmlsitemap_link_bundle_enable('user', 'user');

    // Create the users
    $this->admin_user = $this->drupalCreateUser(array('administer users', 'administer permissions', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));

    // Update the normal user to make its sitemap link visible.
    $account = clone $this->normal_user;
    $account->save();
  }

  /**
   * Test sitemap link for a blocked user.
   */
  public function testBlockedUser() {
    $this->drupalLogin($this->admin_user);
    $this->assertSitemapLinkNotVisible('user', $this->normal_user->id());

    // Mark the user as blocked.
    $edit = array(
      'xmlsitemap[status]' => 1,
    );

    // This will pass when http://drupal.org/node/360925 is fixed.
    $this->drupalPostForm('user/' . $this->normal_user->id() . '/edit', $edit, t('Save'));
    $this->assertText('The changes have been saved.');
    $this->assertSitemapLinkVisible('user', $this->normal_user->id());
  }

}
