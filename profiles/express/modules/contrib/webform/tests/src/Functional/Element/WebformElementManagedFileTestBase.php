<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for testing webform element managed file handling.
 */
abstract class WebformElementManagedFileTestBase extends WebformElementBrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform'];

  /**
   * File usage manager.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * An array of plain text test files.
   *
   * @var array
   */
  protected $files;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileUsage = $this->container->get('file.usage');
    $this->files = $this->getTestFiles('text');

    $this->verbose('<pre>' . print_r($this->files, TRUE) . '</pre>');
  }

  /**
   * Retrieves the fid of the last inserted file.
   */
  protected function getLastFileId() {
    return (int) \Drupal::database()->query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

  /**
   * Load an uncached file entity.
   *
   * @param string $fid
   *   A file id.
   *
   * @return \Drupal\file\FileInterface
   *   An uncached file object
   */
  protected function fileLoad($fid) {
    \Drupal::entityTypeManager()->getStorage('file')->resetCache();
    return File::load($fid);
  }

}
