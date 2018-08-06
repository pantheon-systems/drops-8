<?php

namespace Drupal\redirect\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Global redirect test cases.
 *
 * @group redirect
 */
class GlobalRedirectTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'node',
    'redirect',
    'taxonomy',
    'forum',
    'views',
    'language',
    'content_translation',
  ];

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $normalUser;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $forumTerm;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $term;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->config = $this->config('redirect.settings');

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a users for testing the access.
    $this->normalUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'create url aliases',
      'access administration pages',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer languages',
      'administer content types',
      'administer content translation',
      'create page content',
      'edit own page content',
      'create content translations',
    ]);

    // Save the node.
    $this->node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Test Page Node',
      'path' => ['alias' => '/test-node'],
      'language' => Language::LANGCODE_NOT_SPECIFIED,
    ]);

    // Create an alias for the create story path - this is used in the
    // "redirect with permissions testing" test.
    \Drupal::service('path.alias_storage')->save('/admin/config/system/site-information', '/site-info');

    // Create a taxonomy term for the forum.
    $term = entity_create('taxonomy_term', [
      'name' => 'Test Forum Term',
      'vid' => 'forums',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    $this->forumTerm = $term;

    // Create another taxonomy vocabulary with a term.
    $vocab = entity_create('taxonomy_vocabulary', [
      'name' => 'test vocab',
      'vid' => 'test-vocab',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocab->save();
    $term = entity_create('taxonomy_term', [
      'name' => 'Test Term',
      'vid' => $vocab->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => ['alias' => '/test-term'],
    ]);
    $term->save();

    $this->term = $term;
  }

  /**
   * Will test the redirects.
   */
  public function testRedirects() {

    // First test that the good stuff can be switched off.
    $this->config->set('route_normalizer_enabled', FALSE)->save();
    $this->assertRedirect('index.php/node/' . $this->node->id(), NULL, 'HTTP/1.1 200 OK');
    $this->assertRedirect('index.php/test-node', NULL, 'HTTP/1.1 200 OK');
    $this->assertRedirect('test-node/', NULL, 'HTTP/1.1 200 OK');
    $this->assertRedirect('Test-node/', NULL, 'HTTP/1.1 200 OK');

    $this->config->set('route_normalizer_enabled', TRUE)->save();

    // Test alias normalization.
    $this->assertRedirect('node/' . $this->node->id(), 'test-node');
    $this->assertRedirect('Test-node', 'test-node');

    // Test redirects for non-clean urls.
    $this->assertRedirect('index.php/node/' . $this->node->id(), 'test-node');
    $this->assertRedirect('index.php/test-node', 'test-node');

    // Test deslashing.
    $this->assertRedirect('test-node/', 'test-node');

    // Test front page redirects.
    $this->config('system.site')->set('page.front', '/node')->save();
    $this->assertRedirect('node', '<front>');

    // Test front page redirects with an alias.
    \Drupal::service('path.alias_storage')->save('/node', '/node-alias');
    $this->assertRedirect('node-alias', '<front>');

    // Test post request.
    $this->drupalPost('Test-node', 'application/json', array());
    // Does not do a redirect, stays in the same path.
    $this->assertEqual(basename($this->getUrl()), 'Test-node');

    // Test the access checking.
    $this->config->set('access_check', TRUE)->save();
    $this->assertRedirect('admin/config/system/site-information', NULL, 'HTTP/1.1 403 Forbidden');

    $this->config->set('access_check', FALSE)->save();
    // @todo - here it seems that the access check runs prior to our redirecting
    //   check why so and enable the test.
    //$this->assertRedirect('admin/config/system/site-information', 'site-info');

    // Test original query string is preserved with alias normalization.
    $this->assertRedirect('Test-node?&foo&.bar=baz', 'test-node?&foo&.bar=baz');

    // Test alias normalization with trailing ?.
    $this->assertRedirect('test-node?', 'test-node');
    $this->assertRedirect('Test-node?', 'test-node');

    // Test alias normalization still works without trailing ?.
    $this->assertRedirect('test-node', NULL, 'HTTP/1.1 200 OK');
    $this->assertRedirect('Test-node', 'test-node');

    // Login as user with admin privileges.
    $this->drupalLogin($this->adminUser);

    // Test ignoring admin paths.
    $this->config->set('ignore_admin_path', FALSE)->save();
    $this->assertRedirect('admin/config/system/site-information', 'site-info');

    // Test alias normalization again with ignore_admin_path false.
    $this->assertRedirect('Test-node', 'test-node');

    $this->config->set('ignore_admin_path', TRUE)->save();
    $this->assertRedirect('admin/config/system/site-information', NULL, 'HTTP/1.1 200 OK');

    // Test alias normalization again with ignore_admin_path true.
    $this->assertRedirect('Test-node', 'test-node');
  }

  /**
   * Test that redirects work properly with content_translation enabled.
   */
  public function testLanguageRedirects() {
    $this->drupalLogin($this->adminUser);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('es')
      ->save();

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Set page content type to use multilingual support.
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Page')), 'Basic page content type has been updated.');

    $spanish_node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Spanish Test Page Node',
      'path' => ['alias' => '/spanish-test-node'],
      'langcode' => 'es',
    ]);

    // Test multilingual redirect.
    $this->assertRedirect('es/node/' . $spanish_node->id(), 'es/spanish-test-node');
  }

  /**
   * Asserts the redirect from $path to the $expected_ending_url.
   *
   * @param string $path
   *   The request path.
   * @param $expected_ending_url
   *   The path where we expect it to redirect. If NULL value provided, no
   *   redirect is expected.
   * @param string $expected_ending_status
   *   The status we expect to get with the first request.
   */
  public function assertRedirect($path, $expected_ending_url, $expected_ending_status = 'HTTP/1.1 301 Moved Permanently') {
    $this->drupalHead($GLOBALS['base_url'] . '/' . $path);
    $headers = $this->drupalGetHeaders(TRUE);

    $ending_url = isset($headers[0]['location']) ? $headers[0]['location'] : NULL;
    $message = SafeMarkup::format('Testing redirect from %from to %to. Ending url: %url', array(
      '%from' => $path,
      '%to' => $expected_ending_url,
      '%url' => $ending_url,
    ));


    if ($expected_ending_url == '<front>') {
      $expected_ending_url = $GLOBALS['base_url'] . '/';
    }
    elseif (!empty($expected_ending_url)) {
      $expected_ending_url = $GLOBALS['base_url'] . '/' . $expected_ending_url;
    }
    else {
      $expected_ending_url = NULL;
    }

    $this->assertEqual($expected_ending_url, $ending_url);

    $this->assertEqual($headers[0][':status'], $expected_ending_status);
  }
}
