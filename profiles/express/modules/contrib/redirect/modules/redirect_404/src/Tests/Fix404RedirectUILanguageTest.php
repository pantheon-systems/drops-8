<?php

namespace Drupal\redirect_404\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\redirect\Tests\AssertRedirectTrait;

/**
 * UI tests for redirect_404 module with language and content translation.
 *
 * This runs the exact same tests as Fix404RedirectUITest, but with both
 * language and content translation modules enabled.
 *
 * @group redirect_404
 */
class Fix404RedirectUILanguageTest extends Redirect404TestBase {

  use AssertRedirectTrait;

  /**
   * Additional modules to enable.
   *
   * @var array
   */
  public static $modules = ['language'];

  /**
   * Admin user's permissions for this test.
   *
   * @var array
   */
  protected $adminPermissions = [
    'administer redirects',
    'administer redirect settings',
    'access site reports',
    'access content',
    'bypass node access',
    'create url aliases',
    'administer url aliases',
    'administer languages',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Enable some languages for this test.
    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    $language = ConfigurableLanguage::createFromLangcode('es');
    $language->save();
    $language = ConfigurableLanguage::createFromLangcode('fr');
    $language->save();
  }

  /**
   * Tests the fix 404 pages workflow with language and content translation.
   */
  public function testFix404RedirectList() {
    // Visit a non existing page to have the 404 redirect_error entry.
    $this->drupalGet('fr/testing');

    $redirect = \Drupal::database()->select('redirect_404')
      ->fields('redirect_404')
      ->condition('path', '/testing')
      ->execute()
      ->fetchAll();
    if (count($redirect) == 0) {
      $this->fail('No record was added');
    }

    // Go to the "fix 404" page and check the listing.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('testing');
    $this->assertLanguageInTableBody('French');
    // Check the Language view filter uses the default language filter.
    $this->assertOption('edit-langcode', 'All');
    $this->assertOption('edit-langcode', 'en');
    $this->assertOption('edit-langcode', 'de');
    $this->assertOption('edit-langcode', 'es');
    $this->assertOption('edit-langcode', 'fr');
    $this->assertOption('edit-langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->clickLink(t('Add redirect'));

    // Check if we generate correct Add redirect url and if the form is
    // pre-filled.
    $destination = Url::fromRoute('redirect_404.fix_404')->getInternalPath();
    $options = [
      'query' => [
        'source' => 'testing',
        'language' => 'fr',
        'destination' => $destination,
      ]
    ];
    $this->assertUrl('admin/config/search/redirect/add', $options);
    $this->assertFieldByName('redirect_source[0][path]', 'testing');
    $this->assertOptionSelected('edit-language-0-value', 'fr');
    // Save the redirect.
    $edit = ['redirect_redirect[0][uri]' => '/node'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl('admin/config/search/redirect/404');
    $this->assertText('There are no 404 errors to fix.');
    // Check if the redirect works as expected.
    $this->assertRedirect('fr/testing', 'fr/node', 'HTTP/1.1 301 Moved Permanently');

    // Test removing a redirect assignment, visit again the non existing page.
    $this->drupalGet('admin/config/search/redirect');
    $this->assertText('testing');
    $this->assertLanguageInTableBody('French');
    $this->clickLink('Delete', 0);
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText('There is no redirect yet.');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('There are no 404 errors to fix.');
    // Should be listed again in the 404 overview.
    $this->drupalGet('fr/testing');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertLanguageInTableBody('French');
    // Check the error path visit count.
    $this->assertFieldByXPath('//table/tbody/tr/td[2]', 2);
    $this->clickLink('Add redirect');
    // Save the redirect with a different langcode.
    $this->assertFieldByName('redirect_source[0][path]', 'testing');
    $this->assertOptionSelected('edit-language-0-value', 'fr');
    $edit['language[0][value]'] = 'es';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertUrl('admin/config/search/redirect/404');
    // Should still be listed, redirecting to another language does not resolve
    // the path.
    $this->assertLanguageInTableBody('French');
    $this->drupalGet('admin/config/search/redirect');
    $this->assertLanguageInTableBody('Spanish');
    // Check if the redirect works as expected.
    $this->assertRedirect('es/testing', 'es/node', 'HTTP/1.1 301 Moved Permanently');

    // Visit multiple non existing pages to test the Redirect 404 View.
    $this->drupalGet('testing1');
    $this->drupalGet('de/testing2');
    $this->drupalGet('de/testing2?test=1');
    $this->drupalGet('de/testing2?test=2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertLanguageInTableBody('French');
    $this->assertLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->assertText('testing1');
    $this->assertText('testing2');
    $this->assertText('testing2?test=1');
    $this->assertText('testing2?test=2');

    // Test the Language view filter.
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['langcode' => 'de']]);
    $this->assertText('English');
    $this->assertNoLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->assertNoText('testing1');
    $this->assertText('testing2');
    $this->assertText('testing2?test=1');
    $this->assertText('testing2?test=2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->assertText('testing1');
    $this->assertText('testing2');
    $this->assertText('testing2?test=1');
    $this->assertText('testing2?test=2');
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['langcode' => 'en']]);
    $this->assertLanguageInTableBody('English');
    $this->assertNoLanguageInTableBody('German');
    $this->assertText('testing1');
    $this->assertNoText('testing2');
    $this->assertNoText('testing2?test=1');
    $this->assertNoText('testing2?test=2');

    // Assign a redirect to 'testing1'.
    $this->clickLink('Add redirect');
    $options = [
      'query' => [
        'source' => 'testing1',
        'language' => 'en',
        'destination' => $destination,
      ]
    ];
    $this->assertUrl('admin/config/search/redirect/add', $options);
    $this->assertFieldByName('redirect_source[0][path]', 'testing1');
    $this->assertOptionSelected('edit-language-0-value', 'en');
    $edit = ['redirect_redirect[0][uri]' => '/node'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl('admin/config/search/redirect/404');
    $this->assertNoLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->drupalGet('admin/config/search/redirect');
    $this->assertLanguageInTableBody('Spanish');
    $this->assertLanguageInTableBody('English');
    // Check if the redirect works as expected.
    $this->assertRedirect('/testing1', '/node', 'HTTP/1.1 301 Moved Permanently');
  }

}
