<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\MatcherCrudTest.
 */

namespace Drupal\linkit\Tests;
use Drupal\Core\Url;
use Drupal\linkit\Entity\Profile;

/**
 * Tests adding, listing, updating and deleting matchers on a profile.
 *
 * @group linkit
 */
class MatcherCrudTest extends LinkitTestBase {

  /**
   * The attribute manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

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
    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    $this->linkitProfile = $this->createProfile();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the overview page.
   */
  function testOverview() {
    $this->drupalGet(Url::fromRoute('linkit.matchers', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));
    $this->assertText(t('No matchers added.'));

    $this->assertLinkByHref(Url::fromRoute('linkit.matcher.add', [
      'linkit_profile' => $this->linkitProfile->id(),
    ])->toString());
  }

  /**
   * Test adding a matcher to a profile.
   */
  function testAdd() {
    $this->drupalGet(Url::fromRoute('linkit.matcher.add', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $edit = array();
    $edit['plugin'] = 'dummy_matcher';
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));

    // Load the saved profile.
    $this->linkitProfile = Profile::load($this->linkitProfile->id());

    $matcher_ids = $this->linkitProfile->getMatchers()->getInstanceIds();
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->linkitProfile->getMatcher(current($matcher_ids));

    $this->assertRaw(t('Added %label matcher.', ['%label' => $plugin->getLabel()]));
    $this->assertNoText(t('No matchers added.'));
  }

  /**
   * Test adding a configurable attribute to a profile.
   */
  function testAddConfigurable() {
    $this->drupalGet(Url::fromRoute('linkit.matcher.add', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $edit = array();
    $edit['plugin'] = 'configurable_dummy_matcher';
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));

    // Load the saved profile.
    $this->linkitProfile = Profile::load($this->linkitProfile->id());

    $matcher_ids = $this->linkitProfile->getMatchers()->getInstanceIds();
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->linkitProfile->getMatcher(current($matcher_ids));

    $this->assertUrl(Url::fromRoute('linkit.matcher.edit', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => $plugin->getUuid(),
    ]));

    $this->drupalGet(Url::fromRoute('linkit.matchers', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $this->assertNoText(t('No matchers added.'));
  }

  /**
   * Test delete a matcher from a profile.
   */
  function testDelete() {
    /** @var \Drupal\linkit\AttributeInterface $plugin */
    $plugin = $this->manager->createInstance('dummy_matcher');

    $profile = $this->createProfile();
    $plugin_uuid = $profile->addMatcher($plugin->getConfiguration());
    $profile->save();

    // Try delete a matcher that is not attached to the profile.
    $this->drupalGet(Url::fromRoute('linkit.matcher.delete', [
      'linkit_profile' => $profile->id(),
      'plugin_instance_id' => 'doesntexists'
    ]));
    $this->assertResponse('404');

    // Go to the delete page, but press cancel.
    $this->drupalGet(Url::fromRoute('linkit.matcher.delete', [
      'linkit_profile' => $profile->id(),
      'plugin_instance_id' => $plugin_uuid,
    ]));
    $this->clickLink(t('Cancel'));
    $this->assertUrl(Url::fromRoute('linkit.matchers', [
      'linkit_profile' => $profile->id(),
    ]));

    // Delete the matcher from the profile.
    $this->drupalGet(Url::fromRoute('linkit.matcher.delete', [
      'linkit_profile' => $profile->id(),
      'plugin_instance_id' => $plugin_uuid,
    ]));

    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertRaw(t('The matcher %plugin has been deleted.', ['%plugin' => $plugin->getLabel()]));
    $this->assertUrl(Url::fromRoute('linkit.matchers', [
      'linkit_profile' => $profile->id(),
    ]));
    $this->assertText(t('No matchers added.'));

    /** @var \Drupal\linkit\Entity\Profile $updated_profile */
    $updated_profile = Profile::load($profile->id());
    $this->assertFalse($updated_profile->getMatchers()->has($plugin_uuid), 'The user matcher is deleted from the profile');
  }

}
