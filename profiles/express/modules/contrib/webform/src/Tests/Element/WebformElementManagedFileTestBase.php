<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Base class for testing webform element managed file handling.
 */
abstract class WebformElementManagedFileTestBase extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file'];

  /**
   * File usage manager.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The 'test_element_managed_file' webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

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

    // Create users.
    $this->createUsers();

    $this->fileUsage = $this->container->get('file.usage');
    $this->webform = Webform::load('test_element_managed_file');
    $this->files = $this->drupalGetTestFiles('text');

    $this->verbose('<pre>' . print_r($this->files, TRUE) . '</pre>');
  }

  /**
   * Retrieves the fid of the last inserted file.
   */
  protected function getLastFileId() {
    return (int) \Drupal::database()->query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

}
