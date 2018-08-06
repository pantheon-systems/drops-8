<?php

namespace Drupal\xmlsitemap_engines\Tests;

use Drupal\xmlsitemap\Entity\XmlSitemap;
use Drupal\xmlsitemap\Tests\XmlSitemapTestBase;
use Drupal\Core\Url;

/**
 * Test xmlsitemap_engines functionality.
 *
 * @group xmlsitemap
 */
class XmlSitemapEnginesFunctionalTest extends XmlSitemapTestBase {

  /**
   * The path of the custom link.
   *
   * @var string
   */
  protected $submit_url;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['path', 'dblog', 'xmlsitemap_engines', 'xmlsitemap_engines_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('access content', 'administer xmlsitemap'));
    $this->drupalLogin($this->admin_user);

    // @todo For some reason the test client does not have clean URLs while
    // the test runner does, so it causes mismatches in watchdog assertions
    // later.
    $this->submit_url = Url::fromUri('base://ping', array('absolute' => TRUE, 'query' => array('sitemap' => '')))->toString() . '[sitemap]';
  }

  /**
   * Check if sitemaps are sent to searching engines.
   */
  public function submitEngines() {
    $this->state->setMultiple(array(
      'xmlsitemap_engines_submit_last' => REQUEST_TIME - 10000,
      'xmlsitemap_generated_last' => REQUEST_TIME - 100,
    ));
    \Drupal::configFactory()->getEditable('xmlsitemap_engines.settings')->set('minimum_lifetime', 0)->save();
    xmlsitemap_engines_cron();
    $this->assertTrue($this->state->get('xmlsitemap_engines_submit_last') > (REQUEST_TIME - 100), 'Submitted the sitemaps to search engines.');
  }

  /**
   * Check if an url is correctly prepared.
   */
  public function testPrepareURL() {
    $sitemap = 'http://example.com/sitemap.xml';
    $input = 'http://example.com/ping?sitemap=[sitemap]&foo=bar';
    $output = 'http://example.com/ping?sitemap=http://example.com/sitemap.xml&foo=bar';
    $this->assertEqual(xmlsitemap_engines_prepare_url($input, $sitemap), $output);
  }

  /**
   * Create sitemaps and send them to search engines.
   */
  public function testSubmitSitemaps() {
    $sitemaps = array();

    $context = array(1);
    $sitemap = XmlSitemap::create(array(
      'id' => xmlsitemap_sitemap_get_context_hash($context),
    ));
    $sitemap->setContext(serialize($context));
    $sitemap->setLabel('http://example.com');
    $sitemap->save();
    $sitemap->uri = array(
      'path' => 'http://example.com/sitemap.xml',
      'options' => array(),
    );
    $sitemaps[] = $sitemap;

    $context = array(2);
    $sitemap = XmlSitemap::create(array(
      'id' => xmlsitemap_sitemap_get_context_hash($context),
    ));
    $sitemap->setContext(serialize($context));
    $sitemap->setLabel('http://example.com');
    $sitemap->uri = array(
      'path' => 'http://example.com/sitemap-2.xml',
      'options' => array(),
    );
    $sitemaps[] = $sitemap;

    xmlsitemap_engines_submit_sitemaps($this->submit_url, $sitemaps);

    $this->assertWatchdogMessage(array('type' => 'xmlsitemap', 'message' => 'Recieved ping for @sitemap.', 'variables' => array('@sitemap' => 'http://example.com/sitemap.xml')));
    $this->assertWatchdogMessage(array('type' => 'xmlsitemap', 'message' => 'Recieved ping for @sitemap.', 'variables' => array('@sitemap' => 'http://example.com/sitemap-2.xml')));
  }

  /**
   * Check if ping works.
   */
  public function testPing() {
    $edit = array('engines[simpletest]' => TRUE);
    $this->drupalPostForm('admin/config/search/xmlsitemap/engines', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->submitEngines();
    $this->assertWatchdogMessage(array('type' => 'xmlsitemap', 'message' => 'Submitted the sitemap to %url and received response @code.'));
    $this->assertWatchdogMessage(array('type' => 'xmlsitemap', 'message' => 'Recieved ping for @sitemap.'));
  }

  /**
   * Check if custom urls are functional.
   */
  public function testCustomURL() {
    $edit = array('custom_urls' => 'an-invalid-url');
    $this->drupalPostForm('admin/config/search/xmlsitemap/engines', $edit, t('Save configuration'));
    $this->assertText('Invalid URL an-invalid-url.');
    $this->assertNoText('The configuration options have been saved.');

    $url = Url::fromUri('base://ping', array('absolute' => TRUE))->toString();
    $edit = array('custom_urls' => $url);
    $this->drupalPostForm('admin/config/search/xmlsitemap/engines', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $edit = array('custom_urls' => $this->submit_url);
    $this->drupalPostForm('admin/config/search/xmlsitemap/engines', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->submitEngines();
    $url = xmlsitemap_engines_prepare_url($this->submit_url, Url::fromRoute('xmlsitemap.sitemap_xml', [], array('absolute' => TRUE))->toString());
    $this->assertWatchdogMessage(array('type' => 'xmlsitemap', 'message' => 'Submitted the sitemap to %url and received response @code.', 'variables' => array('%url' => $url, '@code' => 200)));
    $this->assertWatchdogMessage(array('type' => 'xmlsitemap', 'message' => 'Recieved ping for @sitemap.', 'variables' => array('@sitemap' => Url::fromRoute('xmlsitemap.sitemap_xml', [], array('absolute' => TRUE))->toString())));
  }

}
