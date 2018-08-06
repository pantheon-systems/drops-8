<?php

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of a random content entity links.
 *
 * @group xmlsitemap
 */
class XmlSitemapEntityFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer entity_test content', 'administer xmlsitemap'));

    // Allow anonymous users to view test entities.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('view test entity');
    $user_role->save();
  }

  /**
   * Test the form at admin/config/search/xmlsitemap/entities/settings.
   */
  public function testEntitiesSettingsForms() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/xmlsitemap/entities/settings');
    $this->assertResponse(200);
    $this->assertField('entity_types[entity_test_mul]');
    $this->assertField('settings[entity_test_mul][types][entity_test_mul]');
    $edit = array(
      'entity_types[entity_test_mul]' => 1,
      'settings[entity_test_mul][types][entity_test_mul]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The configuration options have been saved.'));
    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), array('status' => 0, 'priority' => 0.5, 'changefreq' => 0, 'access' => 1));
  }

  /**
   * Test the form at admin/config/search/xmlsitemap/settings/{entity_type_id}/{bundle_id}.
   */
  public function testEntityLinkBundleSettingsForm() {
    xmlsitemap_link_bundle_enable('entity_test_mul', 'entity_test_mul');
    $this->drupalLogin($this->admin_user);
    // set priority and inclusion for entity_test_mul - entity_test_mul
    $this->drupalGet('admin/config/search/xmlsitemap/settings/entity_test_mul/entity_test_mul');
    $this->assertResponse(200);
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');
    $this->assertField('xmlsitemap[changefreq]');
    $edit = array(
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => 0.3,
      'xmlsitemap[changefreq]' => XMLSITEMAP_FREQUENCY_WEEKLY,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), array('status' => 0, 'priority' => 0.3, 'changefreq' => XMLSITEMAP_FREQUENCY_WEEKLY, 'access' => 1));

    $this->regenerateSitemap();
    $this->drupalGet('sitemap.xml');
    $this->assertResponse(200);
    $this->assertNoText($entity->url());

    $entity->delete();
    $this->assertNoSitemapLink('entity_test_mul');

    $edit = array(
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => 0.6,
      'xmlsitemap[changefreq]' => XMLSITEMAP_FREQUENCY_YEARLY,
    );
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings/entity_test_mul/entity_test_mul', $edit, t('Save configuration'));
    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), array('status' => 1, 'priority' => 0.6, 'changefreq' => XMLSITEMAP_FREQUENCY_YEARLY, 'access' => 1));

    $this->regenerateSitemap();
    $this->drupalGet('sitemap.xml');
    $this->assertResponse(200);
    $this->assertText($entity->url());

    $id = $entity->id();
    $entity->delete();
    $this->assertNoSitemapLink('entity_test_mul', $id);
  }

  public function testUserCannotViewEntity() {
    // Disallow anonymous users to view test entities.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->revokePermission('view test entity');
    $user_role->save();

    xmlsitemap_link_bundle_enable('entity_test_mul', 'entity_test_mul');

    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), array('status' => 0, 'priority' => 0.5, 'changefreq' => 0, 'access' => 0));
  }

}
