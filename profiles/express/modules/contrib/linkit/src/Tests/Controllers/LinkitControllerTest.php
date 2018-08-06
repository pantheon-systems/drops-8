<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\Controllers\LinkitControllerTest.
 */

namespace Drupal\linkit\Tests\Controllers;

use Drupal\Core\Url;
use Drupal\linkit\Tests\LinkitTestBase;


/**
 * Tests Linkit controller.
 *
 * @group linkit
 */
class LinkitControllerTest extends LinkitTestBase {

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->linkitProfile = $this->createProfile();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the profile route title callback.
   */
  function testProfileTitle() {
    $this->drupalGet(Url::fromRoute('entity.linkit_profile.edit_form', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $this->assertText('Edit ' . $this->linkitProfile->label() . ' profile');
  }

  /**
   * Tests the matcher route title callback.
   */
  function testMatcherTitle() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->container->get('plugin.manager.linkit.matcher')->createInstance('configurable_dummy_matcher');
    $matcher_uuid = $this->linkitProfile->addMatcher($plugin->getConfiguration());
    $this->linkitProfile->save();

    $this->drupalGet(Url::fromRoute('linkit.matcher.edit', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => $matcher_uuid,
    ]));

    $this->assertText('Edit ' . $plugin->getLabel() . ' matcher');
  }

  /**
   * Tests the attribute route title callback.
   */
  function testAttributeTitle() {
    /** @var \Drupal\linkit\AttributeInterface $plugin */
    $plugin = $this->container->get('plugin.manager.linkit.attribute')->createInstance('configurable_dummy_attribute');
    $this->linkitProfile->addAttribute($plugin->getConfiguration());
    $this->linkitProfile->save();

    $this->drupalGet(Url::fromRoute('linkit.attribute.edit', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => $plugin->getPluginId(),
    ]));
    $this->assertText('Edit ' . $plugin->getLabel() . ' attribute');
  }

}
