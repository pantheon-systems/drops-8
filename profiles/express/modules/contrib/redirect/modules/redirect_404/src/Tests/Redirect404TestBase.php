<?php

namespace Drupal\redirect_404\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * This class provides methods specifically for testing redirect 404 paths.
 */
abstract class Redirect404TestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'redirect_404',
    'node',
    'path',
  ];

  /**
   * Permissions for the admin user.
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
  ];

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser($this->adminPermissions);
    $this->drupalLogin($this->adminUser);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
  }

  /**
   * Passes if the language of the 404 path IS found on the loaded page.
   *
   * Because assertText() checks also in the Language select options, this
   * specific assertion in the redirect 404 table body is needed.
   *
   * @param string $language
   *   The language to assert in the redirect 404 table body.
   * @param string $body
   *   (optional) The table body xpath where to assert the language. Defaults
   *   to '//table/tbody'.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertLanguageInTableBody($language, $body = '//table/tbody', $message = '') {
    return $this->assertLanguageInTableBodyHelper($language, $body, $message, FALSE);
  }

  /**
   * Passes if the language of the 404 path is NOT found on the loaded page.
   *
   * Because assertText() checks also in the Language select options, this
   * specific assertion in the redirect 404 table body is needed.
   *
   * @param string $language
   *   The language to assert in the redirect 404 table body.
   * @param string $body
   *   (optional) The table body xpath where to assert the language. Defaults
   *   to '//table/tbody'.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoLanguageInTableBody($language, $body = '//table/tbody', $message = '') {
    return $this->assertLanguageInTableBodyHelper($language, $body, $message, TRUE);
  }

  /**
   * Helper for assertLanguageInTableBody and assertNoLanguageInTableBody.
   *
   * @param array $language
   *   The language to assert in the redirect 404 table body.
   * @param string $body
   *   (optional) The table body xpath where to assert the language. Defaults
   *   to '//table/tbody'.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $not_exists
   *   (optional) TRUE if this language should not exist, FALSE if it should.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertLanguageInTableBodyHelper($language, $body = '//table/tbody', $message = '', $not_exists = TRUE) {
    if (!$message) {
      if (!$not_exists) {
        $message = new FormattableMarkup('Language "@language" found in 404 table.', ['@language' => $language]);
      }
      else {
        $message = new FormattableMarkup('Language "@language" not found in 404 table.', ['@language' => $language]);
      }
    }

    if ($not_exists) {
      return $this->assertFalse(strpos($this->xpath($body)[0]->asXML(), $language), $message);
    }
    else {
      return $this->assertTrue(strpos($this->xpath($body)[0]->asXML(), $language), $message);
    }
  }

}
