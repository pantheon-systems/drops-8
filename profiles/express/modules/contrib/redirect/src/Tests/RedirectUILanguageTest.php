<?php

namespace Drupal\redirect\Tests;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * UI tests for redirect module with language and content translation modules.
 *
 * This runs the exact same tests as RedirectUITest, but with both the language
 * and content translation modules enabled.
 *
 * @group redirect
 */
class RedirectUILanguageTest extends RedirectUITest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['redirect', 'node', 'path', 'dblog', 'views', 'taxonomy', 'language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    $language = ConfigurableLanguage::createFromLangcode('es');
    $language->save();
  }

  /**
   * Test multilingual scenarios.
   */
  public function testLanguageSpecificRedirects() {
    $this->drupalLogin($this->adminUser);

    // Add a redirect for english.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '/user',
      'language[0][value]' => 'en',
    ), t('Save'));

    // Add a redirect for germany.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '<front>',
      'language[0][value]' => 'de',
    ), t('Save'));

    // Check redirect for english.
    $this->assertRedirect('langpath', '/user', 'HTTP/1.1 301 Moved Permanently');

    // Check redirect for germany.
    $this->assertRedirect('de/langpath', '/de', 'HTTP/1.1 301 Moved Permanently');

    // Check no redirect for spanish.
    $this->assertRedirect('es/langpath', NULL, 'HTTP/1.1 404 Not Found');
  }

  /**
   * Test non-language specific redirect.
   */
  public function testUndefinedLangugageRedirects() {
    $this->drupalLogin($this->adminUser);

    // Add a redirect for english.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '/user',
      'language[0][value]' => 'und',
    ), t('Save'));

    // Check redirect for english.
    $this->assertRedirect('langpath', '/user', 'HTTP/1.1 301 Moved Permanently');

    // Check redirect for spanish.
    $this->assertRedirect('es/langpath', '/es/user', 'HTTP/1.1 301 Moved Permanently');
  }

}
