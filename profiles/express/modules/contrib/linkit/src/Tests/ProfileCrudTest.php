<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\ProfileCreationTest.
 */

namespace Drupal\linkit\Tests;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;

/**
 * Tests creating, loading and deleting profiles.
 *
 * @group linkit
 */
class ProfileCrudTest extends LinkitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the overview page.
   */
  function testOverview() {
    // Verify that the profile collection page is not accessible for regular
    // users.
    $this->drupalLogin($this->baseUser);
    $this->drupalGet(Url::fromRoute('entity.linkit_profile.collection'));
    $this->assertResponse(403);
    $this->drupalLogout();

    // Verify that the profile collection page is accessible for regular users.
    $this->drupalLogin($this->adminUser);

    $profiles = [];
    $profiles[] = $this->createProfile();
    $profiles[] = $this->createProfile();

    $this->drupalGet(Url::fromRoute('entity.linkit_profile.collection'));
    $this->assertResponse(200);

    // Assert that the 'Add profile' action exists.
    $this->assertLinkByHref(Url::fromRoute('entity.linkit_profile.add_form')->toString());

    /** @var \Drupal\linkit\ProfileInterface $profile */
    foreach ($profiles as $profile) {
      $this->assertLinkByHref('admin/config/content/linkit/manage/' . $profile->id());
      $this->assertLinkByHref('admin/config/content/linkit/manage/' . $profile->id() . '/delete');
    }
  }

  /**
   * Creates profile.
   */
  function testProfileCreation() {
    $this->drupalGet(Url::fromRoute('entity.linkit_profile.add_form'));
    $this->drupalGet('admin/config/content/linkit/add');
    $this->assertResponse(200);

    // Create a profile.
    $edit = [];
    $edit['label'] = Unicode::strtolower($this->randomMachineName());
    $edit['id'] = Unicode::strtolower($this->randomMachineName());
    $edit['description'] = $this->randomMachineName(16);
    $this->drupalPostForm(NULL, $edit, t('Save and manage matchers'));

    $this->assertRaw(t('Created new profile %label.', ['%label' => $edit['label']]));

    $this->drupalGet(Url::fromRoute('entity.linkit_profile.collection'));
    $this->assertText($edit['label'], 'Profile exists in the profile collection.');
  }

  /**
   * Updates a profile.
   */
  function testProfileUpdate() {
    $profile = $this->createProfile();
    $this->drupalGet(Url::fromRoute('entity.linkit_profile.edit_form', [
      'linkit_profile' => $profile->id(),
    ]));
    $this->assertResponse(200);

    $id_field = $this->xpath('.//input[not(@disabled) and @name="id"]');

    $this->assertTrue(empty($id_field), 'Machine name field is disabled.');
    $this->assertLinkByHref(Url::fromRoute('entity.linkit_profile.edit_form', [
      'linkit_profile' => $profile->id(),
    ])->toString());
    $this->assertLinkByHref('admin/config/content/linkit/manage/' . $profile->id() . '/delete');

    $edit = [];
    $edit['label'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName(16);
    $this->drupalPostForm(NULL, $edit, t('Update profile'));

    $this->assertRaw(t('Updated profile %label.', ['%label' => $edit['label']]));

    $this->drupalGet(Url::fromRoute('entity.linkit_profile.collection'));
    $this->assertText($edit['label'], 'Updated profile exists in the profile collection.');
  }

  /**
   * Delete a profile.
   */
  function testProfileDelete() {
    /** @var \Drupal\linkit\ProfileInterface $profile */
    $profile = $this->createProfile();
    $this->drupalGet(Url::fromRoute('entity.linkit_profile.delete_form', [
      'linkit_profile' => $profile->id(),
    ]));
    $this->drupalPostForm(NULL, [], t('Delete'));

    $this->assertRaw(t('The linkit profile %label has been deleted.', ['%label' => $profile->label()]));
    $this->drupalGet(Url::fromRoute('entity.linkit_profile.collection'));
    $this->assertNoText($profile->label(), 'Deleted profile does not exists in the profile collection.');
  }

}
