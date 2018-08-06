<?php

namespace Drupal\Tests\features\Unit;

use Drupal\features\Entity\FeaturesBundle;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Site\Settings;

/**
 * @coversDefaultClass Drupal\features\Entity\FeaturesBundle
 * @group features
 */
class FeaturesBundleTest extends UnitTestCase {

  public function setUp() {
    parent::setUp();

    // Mock an assigner.
    $manager = new DummyPluginManager();

    // Mock the container.
    $container = $this->prophesize('\Symfony\Component\DependencyInjection\ContainerInterface');
    $container->get('plugin.manager.features_assignment_method')
      ->willReturn($manager);
    \Drupal::setContainer($container->reveal());
  }

  /**
   * @covers ::getEnabledAssignments
   * @covers ::getAssignmentWeights
   * @covers ::getAssignmentSettings
   * @covers ::setAssignmentSettings
   * @covers ::setAssignmentWeights
   * @covers ::setEnabledAssignments
   */
  public function testAssignmentSetting() {
    // Create an entity.
    $settings = [
      'foo' => [
        'enabled' => TRUE,
        'weight' => 0,
        'my_setting' => 42,
      ],
      'bar' => [
        'enabled' => FALSE,
        'weight' => 1,
        'another_setting' => 'value',
      ],
    ];
    $bundle = new FeaturesBundle([
      'assignments' => $settings,
    ], 'features_bundle');

    // Get assignments and attributes.
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      ['foo' => 'foo'],
      'Can get enabled assignments'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentWeights(),
      ['foo' => 0, 'bar' => 1],
      'Can get assignment weights'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings('foo'),
      $settings['foo'],
      'Can get assignment settings'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Can get all assignment settings'
    );

    // Change settings.
    $settings['foo']['my_setting'] = 97;
    $bundle->setAssignmentSettings('foo', $settings['foo']);
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings('foo'),
      $settings['foo'],
      'Can change assignment settings'
    );

    // Change weights.
    $settings['foo']['weight'] = 1;
    $settings['bar']['weight'] = 0;
    $bundle->setAssignmentWeights(['foo' => 1, 'bar' => 0]);
    $this->assertArrayEquals(
      $bundle->getAssignmentWeights(),
      ['foo' => 1, 'bar' => 0],
      'Can change assignment weights'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Weight changes are reflected in settings'
    );

    // Enable existing assignment.
    $settings['bar']['enabled'] = TRUE;
    $bundle->setEnabledAssignments(['foo', 'bar']);
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      ['foo' => 'foo', 'bar' => 'bar'],
      'Can enable assignment'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Enabled assignment status is reflected in settings'
    );

    // Disable existing assignments.
    $settings['foo']['enabled'] = FALSE;
    $settings['bar']['enabled'] = FALSE;
    $bundle->setEnabledAssignments([]);
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      [],
      'Can disable assignments'
    );
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'Disabled assignment status is reflected in settings'
    );

    // Enable a new assignment.
    $settings['foo']['enabled'] = TRUE;
    $settings['iggy'] = ['enabled' => TRUE, 'weight' => 0, 'new_setting' => 3];
    $bundle->setEnabledAssignments(['foo', 'iggy']);
    $this->assertArrayEquals(
      $bundle->getEnabledAssignments(),
      ['foo' => 'foo', 'iggy' => 'iggy'],
      'Can enable new assignment'
    );
    $bundle->setAssignmentSettings('iggy', $settings['iggy']);
    $this->assertArrayEquals(
      $bundle->getAssignmentSettings(),
      $settings,
      'New enabled assignment status is reflected in settings'
    );

  }

  /**
   * @covers ::getFullName
   * @covers ::getShortName
   * @covers ::SetIsProfile
   * @covers ::isProfile
   * @covers ::getProfileName
   * @covers ::isProfilePackage
   * @covers ::inBundle
   */
  public function testFullname() {
    $bundle = new FeaturesBundle([
      'machine_name' => 'mybundle',
      'profile_name' => 'mybundle'
    ], 'mybundle');
    $this->assertFalse($bundle->isProfile());
    // Settings:get('profile_name') isn't defined in test, so this returns NULL.
    $this->assertNull($bundle->getProfileName());
    $this->assertFalse($bundle->isProfilePackage('mybundle'));
    $this->assertEquals('mybundle_test', $bundle->getFullName('test'));
    $this->assertEquals('mybundle_test', $bundle->getFullName('mybundle_test'));
    $this->assertEquals('mybundle_mybundle', $bundle->getFullName('mybundle'));
    $this->assertEquals('test', $bundle->getShortName('test'));
    $this->assertEquals('test', $bundle->getShortName('mybundle_test'));
    $this->assertEquals('mybundle', $bundle->getShortName('mybundle_mybundle'));
    $this->assertEquals('mybundle', $bundle->getShortName('mybundle'));
    $this->assertFalse($bundle->inBundle('test'));
    $this->assertTrue($bundle->inBundle('mybundle_test'));
    $this->assertFalse($bundle->inBundle('mybundle'));

    // Now test it as a profile bundle.
    $bundle->setIsProfile(TRUE);
    $this->assertTrue($bundle->isProfile());
    $this->assertTrue($bundle->isProfilePackage('mybundle'));
    $this->assertFalse($bundle->isProfilePackage('standard'));
    $this->assertEquals('mybundle', $bundle->getProfileName());
    $this->assertEquals('mybundle', $bundle->getFullName('mybundle'));
    $this->assertFalse($bundle->inBundle('test'));
    $this->assertTrue($bundle->inBundle('mybundle_test'));
    $this->assertTrue($bundle->inBundle('mybundle'));
  }

}

/**
 * A dummy plugin manager, to help testing.
 */
class DummyPluginManager {
  public function getDefinition($method_id) {
    $definition = [
      'enabled' => TRUE,
      'weight' => 0,
      'default_settings' => [
        'my_setting' => 42,
      ],
    ];
    return $definition;
  }

}
