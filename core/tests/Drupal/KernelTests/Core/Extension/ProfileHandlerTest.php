<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ProfileHandler class.
 *
 * @coversDefaultClass \Drupal\Core\Extension\ProfileHandler
 *
 * @group Extension
 */
class ProfileHandlerTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * The System module is required because system_rebuild_module_data() is used.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * Tests getting profile info.
   *
   * @covers ::getProfileInfo
   */
  public function testGetProfileInfo() {
    $profile_handler = $this->container->get('profile_handler');
    $info = $profile_handler->getProfileInfo('testing_inherited');
    $this->assertNotEmpty($info);
    $this->assertEquals($info['name'], 'Testing Inherited');
    $this->assertEquals($info['base profile']['name'], 'testing');
    $this->assertEquals($info['base profile']['excluded_dependencies'], ['page_cache']);
    $this->assertTrue(in_array('config', $info['dependencies'], 'config should be found in dependencies'));
    $this->assertFalse(in_array('page_cache', $info['dependencies'], 'page_cache should not be found in dependencies'));
    $this->assertTrue($info['hidden'], 'Profiles should be hidden');
    $this->assertNotEmpty($info['profile_list']);
    $profile_list = $info['profile_list'];
    // Testing order of profile list.
    $this->assertEquals($profile_list, [
      'testing' => 'testing',
      'testing_inherited' => 'testing_inherited'
    ]);

    // Test that profiles without any base return normalized info.
    $info = $profile_handler->getProfileInfo('minimal');
    $this->assertInternalType('array', $info['base profile']);

    $this->assertArrayHasKey('name', $info['base profile']);
    $this->assertEmpty($info['base profile']['name']);

    $this->assertArrayHasKey('excluded_dependencies', $info['base profile']);
    $this->assertInternalType('array', $info['base profile']['excluded_dependencies']);
    $this->assertEmpty($info['base profile']['excluded_dependencies']);

    $this->assertArrayHasKey('excluded_themes', $info['base profile']);
    $this->assertInternalType('array', $info['base profile']['excluded_themes']);
    $this->assertEmpty($info['base profile']['excluded_themes']);

    // Tests three levels profile inheritance.
    $info = $profile_handler->getProfileInfo('testing_subsubprofile');
    $this->assertEquals($info['base profile']['name'], 'testing_inherited');
    $this->assertEquals($info['profile_list'], [
      'testing' => 'testing',
      'testing_inherited' => 'testing_inherited',
      'testing_subsubprofile' => 'testing_subsubprofile',
    ]);
  }

  /**
   * Tests getting profile dependency list.
   *
   * @covers ::getProfileInheritance
   */
  public function testGetProfileInheritance() {
    $profile_handler = $this->container->get('profile_handler');

    $profiles = $profile_handler->getProfileInheritance('testing');
    $this->assertCount(1, $profiles);

    $profiles = $profile_handler->getProfileInheritance('testing_inherited');
    $this->assertCount(2, $profiles);

    $profiles = $profile_handler->getProfileInheritance('testing_subsubprofile');
    $this->assertCount(3, $profiles);

    $first_profile = current($profiles);
    $this->assertEquals(get_class($first_profile), 'Drupal\Core\Extension\Extension');
    $this->assertEquals($first_profile->getName(), 'testing');
    $this->assertEquals($first_profile->weight, 1000);
    $this->assertObjectHasAttribute('origin', $first_profile);

    $second_profile = next($profiles);
    $this->assertEquals(get_class($second_profile), 'Drupal\Core\Extension\Extension');
    $this->assertEquals($second_profile->getName(), 'testing_inherited');
    $this->assertEquals($second_profile->weight, 1001);
    $this->assertObjectHasAttribute('origin', $second_profile);

    $third_profile = next($profiles);
    $this->assertEquals(get_class($third_profile), 'Drupal\Core\Extension\Extension');
    $this->assertEquals($third_profile->getName(), 'testing_subsubprofile');
    $this->assertEquals($third_profile->weight, 1002);
    $this->assertObjectHasAttribute('origin', $third_profile);
  }

  /**
   * @covers ::selectDistribution
   * @covers ::setProfileInfo
   */
  public function testSelectDistribution() {
    /** @var \Drupal\Core\Extension\ProfileHandler $profile_handler */
    $profile_handler = $this->container->get('profile_handler');
    $profiles = ['testing', 'testing_inherited'];
    $base_info = $profile_handler->getProfileInfo('minimal');
    $profile_info = $profile_handler->getProfileInfo('testing_inherited');

    // Neither profile has distribution set.
    $distribution = $profile_handler->selectDistribution($profiles);
    $this->assertEmpty($distribution, 'No distribution should be selected');

    // Set base profile distribution.
    $base_info['distribution']['name'] = 'Minimal';
    $profile_handler->setProfileInfo('minimal', $base_info);
    // Base profile distribution should not be selected.
    $distribution = $profile_handler->selectDistribution($profiles);
    $this->assertEmpty($distribution, 'Base profile distribution should not be selected');

    // Set main profile distribution.
    $profile_info['distribution']['name'] = 'Testing Inherited';
    $profile_handler->setProfileInfo('testing_inherited', $profile_info);
    // Main profile distribution should be selected.
    $distribution = $profile_handler->selectDistribution($profiles);
    $this->assertEquals($distribution, 'testing_inherited');
  }

}
