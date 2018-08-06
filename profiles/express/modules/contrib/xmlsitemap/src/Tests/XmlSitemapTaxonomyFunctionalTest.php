<?php

namespace Drupal\xmlsitemap\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of taxonomy links.
 *
 * @group xmlsitemap
 */
class XmlSitemapTaxonomyFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    xmlsitemap_link_bundle_enable('taxonomy_vocabulary', 'taxonomy_vocabulary');

    // allow anonymous user to view user profiles
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('administer taxonomy');
    $user_role->save();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Test xmlsitemap settings for taxonomies.
   */
  public function testTaxonomySettings() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');
    $edit = array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText("Created new vocabulary {$edit['name']}.");

    $vocabulary = Vocabulary::load($edit['vid']);

    xmlsitemap_link_bundle_enable('taxonomy_term', $vocabulary->id());

    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertResponse(200);
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');
    $this->assertField('xmlsitemap[changefreq]');

    $edit = array(
      'name[0][value]' => $this->randomMachineName(),
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

}
