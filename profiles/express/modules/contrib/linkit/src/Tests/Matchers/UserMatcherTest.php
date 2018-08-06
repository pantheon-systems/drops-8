<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\Matchers\UserMatcherTest.
 */

namespace Drupal\linkit\Tests\Matchers;

use Drupal\linkit\Tests\LinkitTestBase;

/**
 * Tests user matcher.
 *
 * @group linkit
 */
class UserMatcherTest extends LinkitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['access user profiles']));
    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    $custom_role = $this->drupalCreateRole(array(), 'custom_role', 'custom_role');
    $custom_role_admin = $this->drupalCreateRole(array(), 'custom_role_admin', 'custom_role_admin');

    $this->drupalCreateUser([], 'lorem');
    $this->drupalCreateUser([], 'foo');

    $account = $this->drupalCreateUser([], 'ipsumlorem');
    $account->addRole($custom_role);
    $account->save();

    $account = $this->drupalCreateUser([], 'lorem_custom_role');
    $account->addRole($custom_role);
    $account->save();

    $account = $this->drupalCreateUser([], 'lorem_custom_role_admin');
    $account->addRole($custom_role_admin);
    $account->save();

    $account = $this->drupalCreateUser([], 'blocked_lorem');
    $account->block();
    $account->save();
  }

  /**
   * Tests user matcher.
   */
  function testUserMatcherWidthDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:user', []);
    $matches = $plugin->getMatches('Lorem');
    $this->assertEqual(4, count($matches), 'Correct number of matches');
  }

  /**
   * Tests user matcher with role filer.
   */
  function testUserMatcherWidthRoleFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:user', [
      'settings' => [
        'roles' => [
          'custom_role' => 'custom_role'
        ],
      ],
    ]);

    $matches = $plugin->getMatches('Lorem');
    $this->assertEqual(2, count($matches), 'Correct number of matches');
  }

  /**
   * Tests user matcher with include blocked setting activated.
   */
  function testUserMatcherWidthIncludeBlocked() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:user', [
      'settings' => [
        'include_blocked' => TRUE,
      ],
    ]);

    // Test without permissions to see blocked users.
    $matches = $plugin->getMatches('blocked');
    $this->assertEqual(0, count($matches), 'Correct number of matches');

    $account = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($account);

    // Test with permissions to see blocked users.
    $matches = $plugin->getMatches('blocked');
    $this->assertEqual(1, count($matches), 'Correct number of matches');
  }

}
