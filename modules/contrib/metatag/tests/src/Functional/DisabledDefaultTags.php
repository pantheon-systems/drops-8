<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that disabled metatag defaults do not load.
 *
 * @group metatag
 */
class DisabledDefaultTags extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'taxonomy',
    'user',

    // Need this so that the /node page exists.
    'views',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',

    // Use the custom route to verify the site works.
    'metatag_test_custom_route',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the front page to the main /node page, so that the front page is not
    // just the login page.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node')
      ->save(TRUE);
  }

  /**
   * Load a default metatag.
   *
   * @param string $id
   *   The id of the metatag default to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The default metatag.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadMetatagDefault($id) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $global_metatag_manager */
    $global_metatag_manager = \Drupal::entityTypeManager()
      ->getStorage('metatag_defaults');
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity_metatags */
    return $global_metatag_manager->load($id);
  }

  /**
   * Test that a disabled Frontpage metatag default doesn't load.
   */
  public function testFrontpage() {
    $metatag = $this->loadMetatagDefault('front');
    $metatag->overwriteTags(['canonical_url' => 'https://test.canonical']);
    $metatag->save();

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), 'https://test.canonical');

    // Now disable the default. Canonical should then fall back
    // to Global's default, which is page url.
    $metatag->set('status', 0);
    $metatag->save();
    drupal_flush_all_caches();

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $xpath = $this->xpath("//link[@rel='canonical']");
    // The page url in Global will be /node's.
    $this_page_url = $this->buildUrl('/node');
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test that a disabled 404 metatag default doesn't load.
   */
  public function test404() {
    $metatag = $this->loadMetatagDefault('404');
    $metatag->overwriteTags(['canonical_url' => 'https://test.canonical']);
    $metatag->save();

    $this->drupalGet('i-dont-exist');
    $this->assertSession()->statusCodeEquals(404);
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), 'https://test.canonical');

    // Now disable the default. Canonical should then fall back
    // to Global's default, which is page url.
    $metatag->set('status', 0);
    $metatag->save();
    drupal_flush_all_caches();

    $this->drupalGet('i-dont-exist');
    $this->assertSession()->statusCodeEquals(404);
    $xpath = $this->xpath("//link[@rel='canonical']");
    // The page url in Global will be /node's.
    $this_page_url = $this->buildUrl('<front>');
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url . 'i-dont-exist');
  }

  /**
   * Test that a disabled 403 metatag default doesn't load.
   */
  public function test403() {
    $metatag = $this->loadMetatagDefault('403');
    $metatag->overwriteTags(['canonical_url' => 'https://test.canonical']);
    $metatag->save();

    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(403);
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), 'https://test.canonical');

    // Now disable the default. Canonical should then fall back
    // to Global's default, which is page url.
    $metatag->set('status', 0);
    $metatag->save();
    drupal_flush_all_caches();

    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(403);
    $xpath = $this->xpath("//link[@rel='canonical']");
    // The page url in Global will be /node's.
    $this_page_url = $this->buildUrl('/admin/content');
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test that a disabled Node metatag default doesn't load.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEntityTypeDefaults() {
    $node = $this->createContentTypeNode();
    $this_page_url = $node->toUrl('canonical', ['absolute' => TRUE])
      ->toString();

    // Change the node type default's canonical to a hardcoded test string.
    // Will be inherited by node:page, as normally neither has canonical filled
    // in and inherit it anyway from Global.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $global_metatag_manager */
    $global_metatag_manager = \Drupal::entityTypeManager()
      ->getStorage('metatag_defaults');
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity_metatags */
    $entity_metatags = $global_metatag_manager->load('node');
    $entity_metatags->overwriteTags(['canonical_url' => 'https://test.canonical']);
    $entity_metatags->save();

    // Load the node's entity page.
    $this->drupalGet($this_page_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), 'https://test.canonical');

    // Now disable this metatag.
    $entity_metatags->set('status', 0);
    $entity_metatags->save();
    // Clear caches.
    drupal_flush_all_caches();

    // Load the node's entity page.
    $this->drupalGet($this_page_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    // Should now match global or content one, which is node URL.
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test that a disabled node bundle metatag default doesn't load.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEntityBundleDefaults() {
    $node = $this->createContentTypeNode();
    $this_page_url = $node->toUrl('canonical', ['absolute' => TRUE])
      ->toString();

    // Change the node bundle's default's canonical to a hardcoded test string.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $global_metatag_manager */
    $global_metatag_manager = \Drupal::entityTypeManager()
      ->getStorage('metatag_defaults');
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity_metatags */
    $entity_metatags = $global_metatag_manager->create(['id' => 'node__metatag_test']);
    $entity_metatags->overwriteTags(['canonical_url' => 'https://test.canonical']);
    $entity_metatags->save();

    // Load the node's entity page.
    $this->drupalGet($this_page_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), 'https://test.canonical');

    // Now disable this metatag.
    $entity_metatags->set('status', 0);
    $entity_metatags->save();
    // Clear caches.
    drupal_flush_all_caches();

    // Load the node's entity page.
    $this->drupalGet($this_page_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    // Should now match global or content one, which is node URL.
    $this->assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

}
