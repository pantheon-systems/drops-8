<?php

namespace Drupal\Tests\features\Kernel;

use Drupal\features\Entity\FeaturesBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use org\bovigo\vfs\vfsStream;

/**
 * @group features
 */
class FeaturesGenerateTest extends KernelTestBase {

  const PACKAGE_NAME = 'my_test_package';
  const BUNDLE_NAME = 'giraffe';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['features', 'system'];

  /**
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * @var \Drupal\features\FeaturesGeneratorInterface
   */
  protected $generator;

  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('features');
    $this->installConfig('system');

    $this->featuresManager = \Drupal::service('features.manager');
    $this->generator = \Drupal::service('features_generator');
    $this->assigner = \Drupal::service('features_assigner');

    $this->featuresManager->initPackage(self::PACKAGE_NAME, 'My test package');
    $package = $this->featuresManager->getPackage(self::PACKAGE_NAME);
    $package->appendConfig('system.site');
    $this->featuresManager->setPackage($package);
  }

  /**
   * @covers \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationArchive
   */
  public function testExportArchive() {
    $filename = file_directory_temp() . '/' . self::PACKAGE_NAME . '.tar.gz';
    if (file_exists($filename)) {
      unlink($filename);
    }
    $this->assertFalse(file_exists($filename), 'Archive file already exists.');

    $this->generator->generatePackages('archive', $this->assigner->getBundle(), [self::PACKAGE_NAME]);
    $this->assertTrue(file_exists($filename), 'Archive file was not generated.');

    $archive = new ArchiveTar($filename);
    $files = $archive->listContent();

    $this->assertEquals(3, count($files));
    $this->assertEquals(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.info.yml', $files[0]['filename']);
    $this->assertEquals(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.features.yml', $files[1]['filename']);
    $this->assertEquals(self::PACKAGE_NAME . '/config/install/system.site.yml', $files[2]['filename']);

    $expected_info = [
      "name" => "My test package",
      "type" => "module",
      "core" => "8.x",
    ];
    $info = Yaml::decode($archive->extractInString(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.info.yml'));
    $this->assertEquals($expected_info, $info, 'Incorrect info file generated');
  }

  public function testGeneratorWithBundle() {
    $filename = file_directory_temp() . '/' . self::BUNDLE_NAME . '_' . self::PACKAGE_NAME . '.tar.gz';
    if (file_exists($filename)) {
      unlink($filename);
    }
    $this->assertFalse(file_exists($filename), 'Archive file already exists.');

    $bundle = FeaturesBundle::create([
      'machine_name' =>  self::BUNDLE_NAME
    ]);

    $this->generator->generatePackages('archive', $bundle, [self::PACKAGE_NAME]);

    $package = $this->featuresManager->getPackage(self::PACKAGE_NAME);
    $this->assertNull($package);

    $package = $this->featuresManager->getPackage( self::BUNDLE_NAME . '_' . self::PACKAGE_NAME);
    $this->assertEquals(self::BUNDLE_NAME . '_' . self::PACKAGE_NAME, $package->getMachineName());
    $this->assertEquals(self::BUNDLE_NAME, $package->getBundle());

    $this->assertTrue(file_exists($filename), 'Archive file was not generated.');
  }

  /**
   * @covers \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite
   */
  public function testExportWrite() {
    // Set a fake drupal root, so the testbot can also write into it.
    vfsStream::setup('drupal');
    \Drupal::getContainer()->set('app.root', 'vfs://drupal');
    $this->featuresManager->setRoot('vfs://drupal');

    $package = $this->featuresManager->getPackage(self::PACKAGE_NAME);
    // Find out where package will be exported
    list($full_name, $path) = $this->featuresManager->getExportInfo($package, $this->assigner->getBundle());
    $path = 'vfs://drupal/' . $path . '/' . $full_name;
    if (file_exists($path)) {
      file_unmanaged_delete_recursive($path);
    }
    $this->assertFalse(file_exists($path), 'Package directory already exists.');

    $this->generator->generatePackages('write', $this->assigner->getBundle(), [self::PACKAGE_NAME]);
    $info_file_uri = $path . '/' . self::PACKAGE_NAME . '.info.yml';
    $this->assertTrue(file_exists($path), 'Package directory was not generated.');
    $this->assertTrue(file_exists($info_file_uri), 'Package info.yml not generated.');
    $this->assertTrue(file_exists($path . '/config/install'), 'Package config/install not generated.');
    $this->assertTrue(file_exists($path . '/config/install/system.site.yml'), 'Config.yml not exported.');

    $expected_info = [
      "name" => "My test package",
      "type" => "module",
      "core" => "8.x",
    ];
    $info = Yaml::decode(file_get_contents($info_file_uri));
    $this->assertEquals($expected_info, $info, 'Incorrect info file generated');

    // Now, add stuff to the feature and re-export to ensure it is preserved
    // Add a dependency to the package itself to see that it gets exported.
    $package->setDependencies(['user']);
    $this->featuresManager->setPackage($package);
    // Add dependency and custom key to the info file to simulate manual edit.
    $info['dependencies'] = ['node'];
    $info['mykey'] = "test value";
    $info_contents = Yaml::encode($info);
    file_put_contents($info_file_uri, $info_contents);

    // Add an extra file that should be retained.
    $css_file = $path . '/' . self::PACKAGE_NAME . '.css';
    $file_contents = "This is a dummy file";
    file_put_contents($css_file, $file_contents);

    // Add a config file that should be removed since it's not part of the
    // feature.
    $config_file = $path . '/config/install/node.type.mytype.yml';
    file_put_contents($config_file, $file_contents);

    $this->generator->generatePackages('write', $this->assigner->getBundle(), [self::PACKAGE_NAME]);
    $this->assertTrue(file_exists($info_file_uri), 'Package info.yml not generated.');
    $expected_info = [
      "name" => "My test package",
      "type" => "module",
      "core" => "8.x",
      "dependencies" => ["node", "user"],
      "mykey" => "test value",
    ];
    $info = Yaml::decode(file_get_contents($info_file_uri));
    $this->assertEquals($expected_info, $info, 'Incorrect info file generated');
    $this->assertTrue(file_exists($css_file), 'Extra file was not retained.');
    $this->assertFalse(file_exists($config_file), 'Config directory was not cleaned.');
    $this->assertEquals($file_contents, file_get_contents($css_file), 'Extra file contents not retained');

    // Next, test that generating an Archive picks up the extra files.
    $filename = file_directory_temp() . '/' . self::PACKAGE_NAME . '.tar.gz';
    if (file_exists($filename)) {
      unlink($filename);
    }
    $this->assertFalse(file_exists($filename), 'Archive file already exists.');
    $this->generator->generatePackages('archive', $this->assigner->getBundle(), [self::PACKAGE_NAME]);
    $this->assertTrue(file_exists($filename), 'Archive file was not generated.');

    $archive = new ArchiveTar($filename);
    $files = $archive->listContent();

    $this->assertEquals(4, count($files));
    $this->assertEquals(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.info.yml', $files[0]['filename']);
    $this->assertEquals(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.features.yml', $files[1]['filename']);
    $this->assertEquals(self::PACKAGE_NAME . '/config/install/system.site.yml', $files[2]['filename']);
    $this->assertEquals(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.css', $files[3]['filename']);

    $expected_info = [
      "name" => "My test package",
      "type" => "module",
      "core" => "8.x",
      "dependencies" => ["node", "user"],
      "mykey" => "test value",
    ];
    $info = Yaml::decode($archive->extractInString(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.info.yml'));
    $this->assertEquals($expected_info, $info, 'Incorrect info file generated');
    $this->assertEquals($file_contents, $archive->extractInString(self::PACKAGE_NAME . '/' . self::PACKAGE_NAME . '.css'), 'Extra file contents not retained');
  }
}
