<?php

namespace Drupal\xmlsitemap\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\xmlsitemap\Entity\XmlSitemap;

/**
 * Common base test class for XML sitemap internationalization tests.
 */
abstract class XmlSitemapMultilingualTestBase extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'locale', 'content_translation'];

  /**
   * Set up an administrative user account and testing keys.
   */
  protected function setUp() {
    // Call parent::setUp() allowing test cases to pass further modules.
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'administer site configuration', 'administer xmlsitemap', 'access content'));
    $this->drupalLogin($this->admin_user);

    if (!$this->languageManager->getLanguage('fr')) {
      // Add a new language.
      ConfigurableLanguage::createFromLangcode('fr')->save();
    }

    if (!$this->languageManager->getLanguage('en')) {
      // Add a new language.
      ConfigurableLanguage::createFromLangcode('en')->save();
    }

    // Create the two different language-context sitemaps.
    $previous_sitemaps = XmlSitemap::loadMultiple();
    foreach ($previous_sitemaps as $previous_sitemap) {
      $previous_sitemap->delete();
    }

    $sitemap = XmlSitemap::create();
    $sitemap->context = array('language' => 'en');
    xmlsitemap_sitemap_save($sitemap);
    $sitemap = XmlSitemap::create();
    $sitemap->context = array('language' => 'fr');
    xmlsitemap_sitemap_save($sitemap);
  }

}
