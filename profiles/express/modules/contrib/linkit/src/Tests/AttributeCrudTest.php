<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\AttributeCrudTest.
 */

namespace Drupal\linkit\Tests;

use Drupal\Core\Url;
use Drupal\linkit\Entity\Profile;

/**
 * Tests adding, listing and deleting attributes on a profile.
 *
 * @group linkit
 */
class AttributeCrudTest extends LinkitTestBase {

  /**
   * The attribute manager.
   *
   * @var \Drupal\linkit\AttributeManager
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
    $this->manager = $this->container->get('plugin.manager.linkit.attribute');

    $this->linkitProfile = $this->createProfile();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the overview page.
   */
  function testOverview() {
    $this->drupalGet(Url::fromRoute('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));
    $this->assertText(t('No attributes added.'));

    $this->assertLinkByHref(Url::fromRoute('linkit.attribute.add', [
      'linkit_profile' => $this->linkitProfile->id(),
    ])->toString());
  }

  /**
   * Test adding an attribute to a profile.
   */
  function testAdd() {
    $this->drupalGet(Url::fromRoute('linkit.attribute.add', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $this->assertEqual(count($this->manager->getDefinitions()), count($this->xpath('//input[@type="radio"]')), 'All attributes are available.');

    $edit = array();
    $edit['plugin'] = 'dummy_attribute';
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));

    $this->assertUrl(Url::fromRoute('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $this->assertEqual(1, count($this->xpath('//table/tbody/tr')), 'Attribute added.');
    $this->assertNoText(t('No attributes added.'));
  }

  /**
   * Test adding a configurable attribute to a profile.
   */
  function testAddConfigurable() {
    $this->drupalGet(Url::fromRoute('linkit.attribute.add', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $this->assertEqual(count($this->manager->getDefinitions()), count($this->xpath('//input[@type="radio"]')), 'All attributes are available.');

    $edit = array();
    $edit['plugin'] = 'configurable_dummy_attribute';
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));

    $this->assertUrl(Url::fromRoute('linkit.attribute.edit', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => 'configurable_dummy_attribute',
    ]));

    $this->drupalGet(Url::fromRoute('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    $this->assertEqual(1, count($this->xpath('//table/tbody/tr')), 'Attribute added.');
    $this->assertNoText(t('No attributes added.'));

    $plugin_url = Url::fromRoute('linkit.attribute.edit', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => 'configurable_dummy_attribute',
    ]);

    $this->assertLinkByHref($plugin_url->toString());
  }

  /**
   * Test delete an attribute from a profile.
   */
  function testDelete() {
    /** @var \Drupal\linkit\AttributeInterface $plugin */
    $plugin = $this->manager->createInstance('dummy_attribute');

    $this->linkitProfile->addAttribute($plugin->getConfiguration());
    $this->linkitProfile->save();

    // Try delete an attribute that is not attached to the profile.
    $this->drupalGet(Url::fromRoute('linkit.attribute.delete', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => 'doesntexists'
    ]));
    $this->assertResponse('404');

    // Go to the delete page, but press cancel.
    $this->drupalGet(Url::fromRoute('linkit.attribute.delete', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => $plugin->getPluginId(),
    ]));
    $this->clickLink(t('Cancel'));
    $this->assertUrl(Url::fromRoute('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));

    // Delete the attribute from the profile.
    $this->drupalGet(Url::fromRoute('linkit.attribute.delete', [
      'linkit_profile' => $this->linkitProfile->id(),
      'plugin_instance_id' => 'dummy_attribute',
    ]));

    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertRaw(t('The attribute %plugin has been deleted.', ['%plugin' => $plugin->getLabel()]));
    $this->assertUrl(Url::fromRoute('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]));
    $this->assertText(t('No attributes added.'));

    /** @var \Drupal\linkit\Entity\Profile $updated_profile */
    $updated_profile = Profile::load($this->linkitProfile->id());
    $this->assertFalse($updated_profile->getAttributes()->has($plugin->getPluginId()), 'The attribute is deleted from the profile');
  }

}
