<?php

namespace Drupal\media_entity\Tests;

use Drupal\media_entity\Entity\Media;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that basic functions work correctly.
 *
 * @group media_entity
 */
class BasicTest extends WebTestBase {

  use MediaTestTrait;

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $testBundle;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'node', 'media_entity'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->testBundle = $this->drupalCreateMediaBundle();
  }

  /**
   * Tests creating a media bundle programmatically.
   */
  public function testMediaBundleCreation() {
    $bundle = $this->drupalCreateMediaBundle();
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle_storage */
    $bundle_storage = $this->container->get('entity.manager')->getStorage('media_bundle');

    $bundle_exists = (bool) $bundle_storage->load($bundle->id());
    $this->assertTrue($bundle_exists, 'The new media bundle has been created in the database.');

    // Test default bundle created from default configuration.
    $this->container->get('module_installer')->install(['media_entity_test_bundle']);
    $test_bundle = $bundle_storage->load('test');
    $this->assertTrue((bool) $test_bundle, 'The media bundle from default configuration has been created in the database.');
    $this->assertEqual($test_bundle->get('label'), 'Test bundle', 'Correct label detected.');
    $this->assertEqual($test_bundle->get('description'), 'Test bundle.', 'Correct description detected.');
    $this->assertEqual($test_bundle->get('type'), 'generic', 'Correct plugin ID detected.');
    $this->assertEqual($test_bundle->get('type_configuration'), [], 'Correct plugin configuration detected.');
    $this->assertEqual($test_bundle->get('field_map'), [], 'Correct field map detected.');
  }

  /**
   * Tests creating a media entity programmatically.
   */
  public function testMediaEntityCreation() {
    $media = Media::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    $media_not_exist = (bool) Media::load(rand(1000, 9999));
    $this->assertFalse($media_not_exist, 'The media entity does not exist.');

    $media_exists = (bool) Media::load($media->id());
    $this->assertTrue($media_exists, 'The new media entity has been created in the database.');
    $this->assertEqual($media->bundle(), $this->testBundle->id(), 'The media was created with correct bundle.');
    $this->assertEqual($media->label(), 'Unnamed', 'The media was corrected with correct name.');

    // Test the creation of a media without user-defined label and check if a
    // default name is provided.
    $media = Media::create([
      'bundle' => $this->testBundle->id(),
    ]);
    $media->save();
    $expected_name = 'media' . ':' . $this->testBundle->id() . ':' . $media->uuid();
    $this->assertEqual($media->bundle(), $this->testBundle->id(), 'The media was created with correct bundle.');
    $this->assertEqual($media->label(), $expected_name, 'The media was correctly created with a default name.');

  }

  /**
   * Runs basic tests for media_access function.
   */
  public function testMediaAccess() {
    // Create users and roles.
    $admin = $this->drupalCreateUser(['administer media'], 'editor');
    $user = $this->drupalCreateUser([], 'user');

    $permissions = [
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
    ];

    $roles = [];
    foreach ($permissions as $permission) {
      $roles[$permission] = $this->createRole([$permission]);
    }

    // Create media.
    $media = Media::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    $user_media = Media::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
      'uid' => $user->id(),
    ]);
    $user_media->save();

    // Test 'administer media' permission.
    $this->drupalLogin($admin);
    $this->drupalGet('media/' . $user_media->id());
    $this->assertResponse(200);
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $this->assertResponse(200);
    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $this->assertResponse(200);

    // Test 'view media' permission.
    $this->drupalLogin($user);
    $this->drupalGet('media/' . $media->id());
    $this->assertResponse(403);

    $user->addRole($roles['view media']);
    $user->save();

    $this->drupalGet('media/' . $media->id());
    $this->assertResponse(200);

    // Test 'create media' permissions.
    $this->drupalLogin($user);
    $this->drupalGet('media/add/' . $this->testBundle->id());
    $this->assertResponse(403);

    $user->addRole($roles['create media']);
    $user->save();

    $this->drupalGet('media/add/' . $this->testBundle->id());
    $this->assertResponse(200);

    // Test 'update media' and 'delete media' permissions.
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $this->assertResponse(403);

    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $this->assertResponse(403);

    $user->addRole($roles['update media']);
    $user->addRole($roles['delete media']);
    $user->save();

    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $this->assertResponse(200);

    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $this->assertResponse(200);

    // Test 'update any media' and 'delete any media' permissions.
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->assertResponse(403);

    $this->drupalGet('media/' . $media->id() . '/delete');
    $this->assertResponse(403);

    $user->addRole($roles['update any media']);
    $user->addRole($roles['delete any media']);
    $user->save();

    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->assertResponse(200);

    $this->drupalGet('media/' . $media->id() . '/delete');
    $this->assertResponse(200);
  }

}
