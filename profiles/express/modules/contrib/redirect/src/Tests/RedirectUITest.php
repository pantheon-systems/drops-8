<?php

namespace Drupal\redirect\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Language\Language;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * UI tests for redirect module.
 *
 * @group redirect
 */
class RedirectUITest extends WebTestBase {

  use AssertRedirectTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $repository;

  /**
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
   protected $storage;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['redirect', 'node', 'path', 'dblog', 'views', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    $this->adminUser = $this->drupalCreateUser(array(
      'administer redirects',
      'administer redirect settings',
      'access site reports',
      'access content',
      'bypass node access',
      'create url aliases',
      'administer taxonomy',
      'administer url aliases',
    ));

    $this->repository = \Drupal::service('redirect.repository');

    $this->storage = $this->container->get('entity.manager')->getStorage('redirect');
  }

  /**
   * Test the redirect UI.
   */
  public function testRedirectUI() {
    $this->drupalLogin($this->adminUser);

    // Test populating the redirect form with predefined values.
    $this->drupalGet('admin/config/search/redirect/add', array('query' => array(
      'source' => 'non-existing',
      'source_query' => array('key' => 'val', 'key1' => 'val1'),
      'redirect' => 'node',
      'redirect_options' => array('query' => array('key' => 'val', 'key1' => 'val1')),
    )));
    $this->assertFieldByName('redirect_source[0][path]', 'non-existing?key=val&key1=val1');
    $this->assertFieldByName('redirect_redirect[0][uri]', '/node?key=val&key1=val1');

    // Test creating a new redirect via UI.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'non-existing',
      'redirect_redirect[0][uri]' => '/node',
    ), t('Save'));

    // Try to find the redirect we just created.
    $redirect = $this->repository->findMatchingRedirect('non-existing');
    $this->assertEqual($redirect->getSourceUrl(), Url::fromUri('base:non-existing')->toString());
    $this->assertEqual($redirect->getRedirectUrl()->toString(), Url::fromUri('base:node')->toString());

    // After adding the redirect we should end up in the list. Check if the
    // redirect is listed.
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText('non-existing');
    $this->assertLink(Url::fromUri('base:node')->toString());
    $this->assertText(t('Not specified'));

    // Test the edit form and update action.
    $this->clickLink(t('Edit'));
    $this->assertFieldByName('redirect_source[0][path]', 'non-existing');
    $this->assertFieldByName('redirect_redirect[0][uri]', '/node');
    $this->assertFieldByName('status_code', $redirect->getStatusCode());

    // Append a query string to see if we handle query data properly.
    $this->drupalPostForm(NULL, array(
      'redirect_source[0][path]' => 'non-existing?key=value',
    ), t('Save'));

    // Check the location after update and check if the value has been updated
    // in the list.
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText('non-existing?key=value');

    // The path field should not contain the query string and therefore we
    // should be able to load the redirect using only the url part without
    // query.
    $this->storage->resetCache();
    $redirects = $this->repository->findBySourcePath('non-existing');
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), Url::fromUri('base:non-existing', ['query' => ['key' => 'value']])->toString());

    // Test the source url hints.
    // The hint about an existing base path.
    $this->drupalPostAjaxForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'non-existing?key=value',
    ), 'redirect_source[0][path]');
    $this->assertRaw(t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
      array('%source' => 'non-existing?key=value', '@edit-page' => $redirect->url('edit-form'))));

    // The hint about a valid path.
    $this->drupalPostAjaxForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'node',
    ), 'redirect_source[0][path]');
    $this->assertRaw(t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.',
      array('%path' => 'node', '@url-alias' => Url::fromRoute('path.admin_add')->toString())));

    // Test validation.
    // Duplicate redirect.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'non-existing?key=value',
      'redirect_redirect[0][uri]' => '/node',
    ), t('Save'));
    $this->assertRaw(t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
      array('%source' => 'non-existing?key=value', '@edit-page' => $redirect->url('edit-form'))));

    // Redirecting to itself.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'node',
      'redirect_redirect[0][uri]' => '/node',
    ), t('Save'));
    $this->assertRaw(t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));

    // Redirecting the front page.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => '<front>',
      'redirect_redirect[0][uri]' => '/node',
    ), t('Save'));
    $this->assertRaw(t('It is not allowed to create a redirect from the front page.'));

    // Redirecting a url with fragment.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'page-to-redirect#content',
      'redirect_redirect[0][uri]' => '/node',
    ), t('Save'));
    $this->assertRaw(t('The anchor fragments are not allowed.'));

    // Adding path that starts with /
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => '/page-to-redirect',
      'redirect_redirect[0][uri]' => '/node',
    ), t('Save'));
    $this->assertRaw(t('The url to redirect from should not start with a forward slash (/).'));

    // Test filters.
    // Add a new redirect.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'test27',
      'redirect_redirect[0][uri]' => '/node',
    ), t('Save'));

    // Filter  with non existing value.
    $this->drupalGet('admin/config/search/redirect', array(
      'query' => array(
        'status_code' => '3',
      ),
    ));

    $rows = $this->xpath('//tbody/tr');
    // Check if the list has no rows.
    $this->assertTrue(count($rows) == 0);

    // Filter with existing values.
    $this->drupalGet('admin/config/search/redirect', array(
      'query' => array(
        'redirect_source__path' => 'test',
        'status_code' => '2',
      ),
    ));

    $rows = $this->xpath('//tbody/tr');
    // Check if the list has 1 row.
    $this->assertTrue(count($rows) == 1);

    $this->drupalGet('admin/config/search/redirect', array(
      'query' => array(
        'redirect_redirect__uri' => 'nod',
      ),
    ));

    $rows = $this->xpath('//tbody/tr');
    // Check if the list has 2 rows.
    $this->assertTrue(count($rows) == 2);

    // Test the plural form of the bulk delete action.
    $this->drupalGet('admin/config/search/redirect');
    $edit = [
      'redirect_bulk_form[0]' => TRUE,
      'redirect_bulk_form[1]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText('Are you sure you want to delete these redirects?');
    $this->clickLink('Cancel');

    // Test the delete action.
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the URL redirect from %source to %redirect?',
      array('%source' => Url::fromUri('base:non-existing', ['query' => ['key' => 'value']])->toString(), '%redirect' => Url::fromUri('base:node')->toString())));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertUrl('admin/config/search/redirect');

    // Test the bulk delete action.
    $this->drupalPostForm(NULL, ['redirect_bulk_form[0]' => TRUE], t('Apply to selected items'));
    $this->assertText('Are you sure you want to delete this redirect?');
    $this->assertText('test27');
    $this->drupalPostForm(NULL, [], t('Delete'));

    $this->assertText(t('There is no redirect yet.'));
  }

  /**
   * Tests redirects being automatically created upon path alias change.
   */
  public function testAutomaticRedirects() {
    $this->drupalLogin($this->adminUser);

    // Create a node and update its path alias which should result in a redirect
    // being automatically created from the old alias to the new one.
    $node = $this->drupalCreateNode(array(
      'type' => 'article',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => array('alias' => '/node_test_alias'),
    ));
    $this->drupalPostForm('node/' . $node->id() . '/edit', array('path[0][alias]' => '/node_test_alias_updated'), t('Save'));

    $redirect = $this->repository->findMatchingRedirect('node_test_alias', array(), Language::LANGCODE_NOT_SPECIFIED);
    $this->assertEqual($redirect->getRedirectUrl()->toString(), Url::fromUri('base:node_test_alias_updated')->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('node_test_alias', 'node_test_alias_updated');

    // Test that changing the path back deletes the first redirect, creates
    // a new one and does not result in a loop.
    $this->drupalPostForm('node/' . $node->id() . '/edit', array('path[0][alias]' => '/node_test_alias'), t('Save'));
    $redirect = $this->repository->findMatchingRedirect('node_test_alias', array(), Language::LANGCODE_NOT_SPECIFIED);
    $this->assertTrue(empty($redirect));

    \Drupal::service('path.alias_manager')->cacheClear();
    $redirect = $this->repository->findMatchingRedirect('node_test_alias_updated', array(), Language::LANGCODE_NOT_SPECIFIED);

    $this->assertEqual($redirect->getRedirectUrl()->toString(), Url::fromUri('base:node_test_alias')->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('node_test_alias_updated', 'node_test_alias');

    // Test that the redirect will be deleted upon node deletion.
    $this->drupalPostForm('node/' . $node->id() . '/delete', array(), t('Delete'));
    $redirect = $this->repository->findMatchingRedirect('node_test_alias_updated', array(), Language::LANGCODE_NOT_SPECIFIED);
    $this->assertTrue(empty($redirect));

    // Create a term and update its path alias and check if we have a redirect
    // from the previous path alias to the new one.
    $term = $this->createTerm($this->createVocabulary());
    $this->drupalPostForm('taxonomy/term/' . $term->id() . '/edit', array('path[0][alias]' => '/term_test_alias_updated'), t('Save'));
    $redirect = $this->repository->findMatchingRedirect('term_test_alias');
    $this->assertEqual($redirect->getRedirectUrl()->toString(), Url::fromUri('base:term_test_alias_updated')->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('term_test_alias', 'term_test_alias_updated');

    // Test the path alias update via the admin path form.
    $this->drupalPostForm('admin/config/search/path/add', array(
      'source' => '/node',
      'alias' => '/aaa_path_alias',
    ), t('Save'));
    // Note that here we rely on fact that we land on the path alias list page
    // and the default sort is by the alias, which implies that the first edit
    // link leads to the edit page of the aaa_path_alias.
    $this->clickLink(t('Edit'));
    $this->drupalPostForm(NULL, array('alias' => '/aaa_path_alias_updated'), t('Save'));
    $redirect = $this->repository->findMatchingRedirect('aaa_path_alias', array(), 'en');
    $this->assertEqual($redirect->getRedirectUrl()->toString(), Url::fromUri('base:aaa_path_alias_updated')->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('aaa_path_alias', 'aaa_path_alias_updated');

    // Test the automatically created redirect shows up in the form correctly.
    $this->drupalGet('admin/config/search/redirect/edit/' . $redirect->id());
    $this->assertFieldByName('redirect_source[0][path]', 'aaa_path_alias');
    $this->assertFieldByName('redirect_redirect[0][uri]', '/node');
  }

  /**
   * Test the redirect loop protection and logging.
   */
  function testRedirectLoop() {
    // Redirect loop redirection only works when page caching is disabled.
    \Drupal::service('module_installer')->uninstall(['page_cache']);

    /** @var \Drupal\redirect\Entity\Redirect $redirect1 */
    $redirect1 = $this->storage->create();
    $redirect1->setSource('node');
    $redirect1->setRedirect('admin');
    $redirect1->setStatusCode(301);
    $redirect1->save();

    /** @var \Drupal\redirect\Entity\Redirect $redirect2 */
    $redirect2 = $this->storage->create();
    $redirect2->setSource('admin');
    $redirect2->setRedirect('node');
    $redirect2->setStatusCode(301);
    $redirect2->save();

    $this->maximumRedirects = 10;
    $this->drupalGet('node');
    $this->assertText('Service unavailable');
    $this->assertResponse(503);

    $log = db_select('watchdog')->fields('watchdog')->condition('type', 'redirect')->execute()->fetchAll();
    if (count($log) == 0) {
      $this->fail('Redirect loop has not been logged');
    }
    else {
      $log = reset($log);
      $this->assertEqual($log->severity, RfcLogLevel::WARNING);
      $this->assertEqual(SafeMarkup::format($log->message, unserialize($log->variables)),
        SafeMarkup::format('Redirect loop identified at %path for redirect %id', array('%path' => '/node', '%id' => $redirect1->id())));
    }
  }

  /**
   * Returns a new vocabulary with random properties.
   */
  function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  function createTerm($vocabulary) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'description' => array(
        'value' => $this->randomMachineName(),
        // Use the first available text format.
        'format' => $format->id(),
      ),
      'vid' => $vocabulary->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => array('alias' => '/term_test_alias'),
    ));
    $term->save();
    return $term;
  }

  /**
   * Test cache tags.
   *
   * @todo Not sure this belongs in a UI test, but a full web test is needed.
   */
  public function testCacheTags() {
    /** @var \Drupal\redirect\Entity\Redirect $redirect1 */
    $redirect1 = $this->storage->create();
    $redirect1->setSource('test-redirect');
    $redirect1->setRedirect('node');
    $redirect1->setStatusCode(301);
    $redirect1->save();

    $this->assertRedirect('test-redirect', 'node');
    $headers = $this->drupalGetHeaders(TRUE);
    // Note, self::assertCacheTag() cannot be used here since it only looks at
    // the final set of headers.
    $expected = 'http_response ' . implode(' ', $redirect1->getCacheTags());
    $this->assertEqual($expected, $headers[0]['x-drupal-cache-tags'], 'Redirect cache tags properly set.');

    // First request should be a cache MISS.
    $this->assertEqual($headers[0]['x-drupal-cache'], 'MISS', 'First request to the redirect was not cached.');

    // Second request should be cached.
    $this->assertRedirect('test-redirect', 'node');
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual($headers[0]['x-drupal-cache'], 'HIT', 'The second request to the redirect was cached.');

    // Ensure that the redirect has been cleared from cache when deleted.
    $redirect1->delete();
    $this->drupalGet('test-redirect');
    $this->assertResponse(404, 'Deleted redirect properly clears the internal page cache.');
  }

  /**
   * Test external destinations.
   */
  public function testExternal() {
    $redirect = $this->storage->create();
    $redirect->setSource('a-path');
    // @todo Redirect::setRedirect() assumes that all redirects are internal.
    $redirect->redirect_redirect->set(0, ['uri' => 'https://www.example.org']);
    $redirect->setStatusCode(301);
    $redirect->save();
    $this->assertRedirect('a-path', 'https://www.example.org');
    $this->drupalLogin($this->adminUser);
  }

}
