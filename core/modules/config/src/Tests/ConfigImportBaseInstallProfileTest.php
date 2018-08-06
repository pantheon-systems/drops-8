<?php

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the importing/exporting configuration based on install sub-profile.
 *
 * @group config
 */
class ConfigImportBaseInstallProfileTest extends WebTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing_inherited';

  /**
   * A user with the 'synchronize configuration' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['synchronize configuration']);
    $this->drupalLogin($this->webUser);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
  }

  /**
   * Tests config importer cannot uninstall parent install profiles and
   * dependencies of parent profiles can be uninstalled.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testInstallParentProfileValidation() {
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);
    $core = $sync->read('core.extension');

    // Ensure that parent profile can not be uninstalled.
    unset($core['module']['testing']);
    $sync->write('core.extension', $core);

    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertText('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertText('Unable to uninstall the Testing profile since it is a parent of another installed profile.');

    // Uninstall dependencies of parent profile.
    $core['module']['testing'] = 0;
    unset($core['module']['dynamic_page_cache']);
    $sync->write('core.extension', $core);
    $sync->deleteAll('dynamic_page_cache.');
    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertText('The configuration was imported successfully.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('dynamic_page_cache'), 'The dynamic_page_cache module has been uninstalled.');
  }

  /**
   * Tests config importer cannot uninstall sub-profiles and dependencies of
   * sub-profiles can be uninstalled.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testInstallSubProfileValidation() {
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);
    $core = $sync->read('core.extension');

    // Ensure install sub-profiles can not be uninstalled.
    unset($core['module']['testing_inherited']);
    $sync->write('core.extension', $core);

    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertText('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertText('Unable to uninstall the Testing Inherited profile since it is the main install profile.');

    // Uninstall dependencies of main profile.
    $core['module']['testing_inherited'] = 0;
    unset($core['module']['syslog']);
    $sync->write('core.extension', $core);
    $sync->deleteAll('syslog.');
    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertText('The configuration was imported successfully.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('syslog'), 'The syslog module has been uninstalled.');
  }

}
