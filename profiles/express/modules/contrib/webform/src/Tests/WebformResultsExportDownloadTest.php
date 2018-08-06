<?php

namespace Drupal\webform\Tests;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform results export download.
 *
 * @group Webform
 */
class WebformResultsExportDownloadTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'locale', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests download files.
   */
  public function testDownloadFiles() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform_managed_file */
    $webform_managed_file = Webform::load('test_element_managed_file');

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform_managed_file);
    $submission_exporter->setExporter();

    $sids = [];
    $sids[] = $this->postSubmissionTest($webform_managed_file);
    $sids[] = $this->postSubmissionTest($webform_managed_file);
    $sids[] = $this->postSubmissionTest($webform_managed_file);

    /* Download CSV */

    // Download tar ball archive with CSV.
    $edit = ['files' => TRUE];
    $this->drupalPostForm('admin/structure/webform/manage/test_element_managed_file/results/download', $edit, t('Download'));

    // Load the tar and get a list of files.
    $tar = new ArchiveTar($submission_exporter->getArchiveFilePath(), 'gz');
    $files = [];
    $content_list = $tar->listContent();
    foreach ($content_list as $file) {
      $files[$file['filename']] = $file['filename'];
    }

    // Check that CSV file exists.
    $this->assert(isset($files['test_element_managed_file/test_element_managed_file.csv']));

    // Check submission file directories.
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = WebformSubmission::loadMultiple($sids);
    foreach ($submissions as $submission) {
      $serial = $submission->serial();
      $fid = $submission->getElementData('managed_file_single');
      $filename = File::load($fid)->getFilename();

      $this->assert(isset($files["submission-$serial/$filename"]));
    }

    /* Download YAML */

    // Download tar ball archive with YAML documents.
    $edit = [
      'files' => TRUE,
      'exporter' => 'yaml',
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_element_managed_file/results/download', $edit, t('Download'));

    // Load the tar and get a list of files.
    $tar = new ArchiveTar($submission_exporter->getArchiveFilePath(), 'gz');
    $files = [];
    $content_list = $tar->listContent();
    foreach ($content_list as $file) {
      $files[$file['filename']] = $file['filename'];
    }

    // Check that CSV file does not exists.
    $this->assert(!isset($files['test_element_managed_file/test_element_managed_file.csv']));

    // Check submission file directories.
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = WebformSubmission::loadMultiple($sids);
    foreach ($submissions as $submission) {
      $serial = $submission->serial();
      $fid = $submission->getElementData('managed_file_single');
      $filename = File::load($fid)->getFilename();

      $this->assert(isset($files["submission-$serial.yml"]));
      $this->assert(isset($files["submission-$serial/$filename"]));
    }
  }

}
