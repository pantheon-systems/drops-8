<?php

namespace Drupal\Tests\libraries\Kernel\ExternalLibrary\Asset;

use Drupal\Tests\libraries\Kernel\LibraryTypeKernelTestBase;

/**
 * Provides a base test class for asset library type tests.
 */
abstract class AssetLibraryTestBase extends LibraryTypeKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * \Drupal\libraries\Extension requires system_get_info() which is in
   * system.module.
   */
  public static $modules = ['system'];

  /**
   * The Drupal core library discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $coreLibraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->coreLibraryDiscovery = $this->container->get('library.discovery');
  }

}
