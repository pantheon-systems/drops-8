<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\LinkitTestBase.
 */

namespace Drupal\linkit\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\linkit\Entity\Profile;
use Drupal\simpletest\WebTestBase;

/**
 * Sets up page and article content types.
 */
abstract class LinkitTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Enable block module to get the local_actions_block to work.
   *
   * @var array
   */
  public static $modules = ['linkit', 'linkit_test', 'block'];

  /**
   * A user with the 'administer linkit profiles' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user without the 'administer linkit profiles' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer linkit profiles']);
    $this->baseUser = $this->drupalCreateUser();

    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'content']);
    $this->drupalPlaceBlock('local_actions_block', ['region' => 'content']);
    $this->drupalPlaceBlock('system_messages_block', ['region' => 'highlighted']);
  }

  /**
   * Creates a profile based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the profile, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array
   *
   *   The following defaults are provided:
   *   - label: Random string.
   *
   * @return \Drupal\linkit\ProfileInterface
   *   The created profile entity.
   */
  protected function createProfile(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
    ];

    $profile = Profile::create($settings);
    $profile->save();

    return $profile;
  }

}
