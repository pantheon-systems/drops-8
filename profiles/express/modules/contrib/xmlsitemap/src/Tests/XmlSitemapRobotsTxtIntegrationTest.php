<?php

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Url;

/**
 * Tests the robots.txt file existence.
 *
 * @group xmlsitemap
 * @dependencies robotstxt
 */
class XmlSitemapRobotsTxtIntegrationTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['robotstxt'];

  /**
   * Test if sitemap link is included in robots.txt file.
   */
  public function testRobotsTxt() {
    // Request the un-clean robots.txt path so this will work in case there is
    // still the robots.txt file in the root directory.
    if (file_exists(DRUPAL_ROOT . '/robots.txt')) {
      $this->error(t('Unable to proceed with configured robots.txt tests: A local file already exists at @s, so the menu override in this module will never run.', array('@s' => DRUPAL_ROOT . '/robots.txt')));
      return;
    }
    $this->drupalGet('/robots.txt');
    $this->assertRaw('Sitemap: ' . Url::fromRoute('xmlsitemap.sitemap_xml', [], ['absolute' => TRUE])->toString());
  }

}
