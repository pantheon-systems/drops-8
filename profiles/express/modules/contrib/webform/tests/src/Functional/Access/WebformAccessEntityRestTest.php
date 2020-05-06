<?php

namespace Drupal\Tests\webform\Functional\Access;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform entity REST access.
 *
 * @group Webform
 */
class WebformAccessEntityRestTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_rest'];

  /**
   * Tests webform entity REST acces.
   */
  public function testRestAccess() {
    $webform = Webform::load('contact');

    $account = $this->drupalCreateUser();

    $configuration_account = $this->drupalCreateUser([
      'access any webform configuration',
    ]);

    /**************************************************************************/

    // Check anonymous access denied to webform via _format=hal_json.
    $this->drupalGet('/webform/contact', ['query' => ['_format' => 'hal_json']]);
    $this->assertRaw('{"message":"Access to webform configuration is required."}');

    // Login authenticated user.
    $this->drupalLogin($account);

    // Check authenticated access allowed to webform via _format=html.
    $this->drupalGet('/webform/contact');
    $this->assertFieldByName('subject');

    // Check authenticated access denied to webform via _format=hal_json.
    $this->drupalGet('/webform/contact', ['query' => ['_format' => 'hal_json']]);
    $this->assertRaw('{"message":"Access to webform configuration is required."}');

    // Login rest (permission) user.
    $this->drupalLogin($configuration_account);

    // Check rest access allowed to webform via _format=hal_json.
    $this->drupalGet('/webform/contact', ['query' => ['_format' => 'hal_json']]);
    $this->assertNoRaw('{"message":"Access to webform configuration is required."}');
    $this->assertRaw('"id":"contact","title":"Contact"');

    // Allow anonymous role to access webform configuration.
    $access_rules = $webform->getAccessRules();
    $access_rules['configuration']['roles'] = ['anonymous', 'authenticated'];
    $webform->setAccessRules($access_rules);
    $webform->save();

    // Login out and switch to anonymous user.
    $this->drupalLogout();

    // Check anonymous access allowed to webform via _format=hal_json.
    $this->drupalGet('/webform/contact', ['query' => ['_format' => 'hal_json']]);
    $this->assertNoRaw('{"message":"Access to webform configuration is required."}');

    // Login authenticated user.
    $this->drupalLogin($account);

    // Check authenticated access allowed to webform via _format=hal_json.
    $this->drupalGet('/webform/contact', ['query' => ['_format' => 'hal_json']]);
    $this->assertNoRaw('{"message":"Access to webform configuration is required."}');
  }

}
