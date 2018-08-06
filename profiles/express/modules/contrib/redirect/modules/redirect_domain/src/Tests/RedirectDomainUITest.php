<?php

namespace Drupal\redirect_domain\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI for domain redirect.
 *
 * @group redirect_domain
 */
class RedirectDomainUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'redirect_domain',
  ];

  /**
   * Tests domain redirect.
   */
  public function testDomainRedirect() {
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer redirects'
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/search/redirect/domain');

    // Assert that there are 2 domain redirect fields.
    $this->assertFieldByName('redirects[0][from]');
    $this->assertFieldByName('redirects[0][sub_path]');
    $this->assertFieldByName('redirects[0][destination]');

    // Add another field for new domain redirect.
    $this->drupalPostAjaxForm(NULL, [], ['op' => t('Add another')]);

    // Add two new domain redirects.
    $edit = [
      'redirects[0][from]' => 'foo.example.org',
      'redirects[0][sub_path]' => '//sub-path',
      'redirects[0][destination]' => 'www.example.org/foo',
      'redirects[1][from]' => 'bar.example.org',
      'redirects[1][sub_path]' => '',
      'redirects[1][destination]' => 'www.example.org/bar',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check the new domain redirects.
    $this->assertFieldByName('redirects[0][from]', 'foo.example.org');
    $this->assertFieldByName('redirects[0][destination]', 'www.example.org/foo');
    $this->assertFieldByName('redirects[1][from]', 'bar.example.org');
    $this->assertFieldByName('redirects[1][destination]', 'www.example.org/bar');

    // Ensure that the sub paths are correct.
    $this->assertFieldByName('redirects[0][sub_path]', '/sub-path');
    $this->assertFieldByName('redirects[1][sub_path]', '/');
  }
}
