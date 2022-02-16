<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the installation of modules.
 *
 * @group Module
 */
class ExperimentalModuleTest extends BrowserTestBase {


  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests installing experimental modules and dependencies in the UI.
   */
  public function testExperimentalConfirmForm() {

    // First, test installing a non-experimental module with no dependencies.
    // There should be no confirmation form and no experimental module warning.
    $edit = [];
    $edit["modules[test_page_test][enable]"] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('Module Test page has been enabled.');
    $this->assertSession()->pageTextNotContains('Experimental modules are provided for testing purposes only.');

    // Uninstall the module.
    \Drupal::service('module_installer')->uninstall(['test_page_test']);

    // Next, test installing an experimental module with no dependencies.
    // There should be a confirmation form with an experimental warning, but no
    // list of dependencies.
    $edit = [];
    $edit["modules[experimental_module_test][enable]"] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    // The module should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertSession()->pageTextNotContains('Experimental Test has been enabled.');
    $this->assertSession()->pageTextContains('Experimental modules are provided for testing purposes only.');
    $this->assertSession()->pageTextContains('The following modules are experimental: Experimental Test');

    // There should be no message about enabling dependencies.
    $this->assertSession()->pageTextNotContains('You must enable');

    // Enable the module and confirm that it worked.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('Experimental Test has been enabled.');

    // Uninstall the module.
    \Drupal::service('module_installer')->uninstall(['experimental_module_test']);

    // Test enabling a module that is not itself experimental, but that depends
    // on an experimental module.
    $edit = [];
    $edit["modules[experimental_module_dependency_test][enable]"] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    // The module should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertSession()->pageTextNotContains('2 modules have been enabled: Experimental Dependency Test, Experimental Test');
    $this->assertSession()->pageTextContains('Experimental modules are provided for testing purposes only.');

    $this->assertSession()->pageTextContains('The following modules are experimental: Experimental Test');

    // Ensure the non-experimental module is not listed as experimental.
    $this->assertSession()->pageTextNotContains('The following modules are experimental: Experimental Test, Experimental Dependency Test');
    $this->assertSession()->pageTextNotContains('The following modules are experimental: Experimental Dependency Test');

    // There should be a message about enabling dependencies.
    $this->assertSession()->pageTextContains('You must enable the Experimental Test module to install Experimental Dependency Test');

    // Enable the module and confirm that it worked.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('2 modules have been enabled: Experimental Dependency Test, Experimental Test');

    // Uninstall the modules.
    \Drupal::service('module_installer')->uninstall(['experimental_module_test', 'experimental_module_dependency_test']);

    // Finally, check both the module and its experimental dependency. There is
    // still a warning about experimental modules, but no message about
    // dependencies, since the user specifically enabled the dependency.
    $edit = [];
    $edit["modules[experimental_module_test][enable]"] = TRUE;
    $edit["modules[experimental_module_dependency_test][enable]"] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    // The module should not be enabled and there should be a warning and a
    // list of the experimental modules with only this one.
    $this->assertSession()->pageTextNotContains('2 modules have been enabled: Experimental Dependency Test, Experimental Test');
    $this->assertSession()->pageTextContains('Experimental modules are provided for testing purposes only.');

    $this->assertSession()->pageTextContains('The following modules are experimental: Experimental Test');

    // Ensure the non-experimental module is not listed as experimental.
    $this->assertSession()->pageTextNotContains('The following modules are experimental: Experimental Dependency Test, Experimental Test');
    $this->assertSession()->pageTextNotContains('The following modules are experimental: Experimental Dependency Test');

    // There should be no message about enabling dependencies.
    $this->assertSession()->pageTextNotContains('You must enable');

    // Enable the module and confirm that it worked.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('2 modules have been enabled: Experimental Dependency Test, Experimental Test');

    // Try to enable an experimental module that can not be due to
    // hook_requirements().
    \Drupal::state()->set('experimental_module_requirements_test_requirements', TRUE);
    $edit = [];
    $edit["modules[experimental_module_requirements_test][enable]"] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    // Verify that if the module can not be installed, we are not taken to the
    // confirm form.
    $this->assertSession()->addressEquals('admin/modules');
    $this->assertSession()->pageTextContains('The Experimental Test Requirements module can not be installed.');
  }

}
