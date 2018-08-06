<?php

namespace Drupal\config_update_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verify config reports, reverts, and diffs with profile overrides.
 *
 * @group config
 */
class ConfigProfileOverridesTest extends WebTestBase {

  /**
   * Use the Standard profile, so that there are profile config overrides.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'config',
    'config_update',
    'config_update_ui',
  ];

  /**
   * The admin user that will be created.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user and log in.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
      'view config updates report',
      'synchronize configuration',
      'export configuration',
      'import configuration',
      'revert configuration',
      'delete configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that config overrides work as expected.
   */
  public function testConfigOverrides() {

    // The Standard install profile contains a system.theme.yml file that
    // sets up bartik/seven as the default/admin theme. The default config
    // from the System module has no admin theme and stark as the default
    // theme. So first, run the report on simple configuration and verify
    // that system.theme is not shown (it should not be missing, or added,
    // or overridden).
    $this->drupalGet('admin/config/development/configuration/report/type/system.simple');
    $this->assertNoRaw('system.theme');

    // Go to the Appearance page and change the theme to whatever is currently
    // disabled. Return to the report and verify that system.theme is there,
    // since it is now overridden.
    $this->drupalGet('admin/appearance');
    $this->clickLink('Install and set as default');
    $this->drupalGet('admin/config/development/configuration/report/type/system.simple');
    $this->assertText('system.theme');

    // Look at the differences for system.theme and verify it's against
    // the standard profile version, not default version. The line for
    // default should show bartik as the source; if it's against the system
    // version, the word bartik would not be there.
    $this->drupalGet('admin/config/development/configuration/report/diff/system.simple/system.theme');
    $this->assertText('bartik');

    // Revert and verify that it reverted to the profile version, not the
    // system module version.
    $this->drupalGet('admin/config/development/configuration/report/revert/system.simple/system.theme');
    $this->drupalPostForm(NULL, [], 'Revert');
    $this->drupalGet('admin/config/development/configuration/single/export/system.simple/system.theme');
    $this->assertText('admin: seven');
    $this->assertText('default: bartik');
  }

}
