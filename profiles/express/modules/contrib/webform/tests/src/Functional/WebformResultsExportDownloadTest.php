<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform results export download.
 *
 * @group Webform
 */
class WebformResultsExportDownloadTest extends WebformBrowserTestBase {

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

    $archive_types = ['tar', 'zip'];
    foreach ($archive_types as $archive_type) {
      // Set exporter archive type.
      $submission_exporter->setExporter(['archive_type' => $archive_type]);

      /* Download CSV */

      // Download tar ball archive with CSV.
      $edit = [
        'files' => TRUE,
        'archive_type' => $archive_type,
      ];
      $this->drupalPostForm('/admin/structure/webform/manage/test_element_managed_file/results/download', $edit, t('Download'));

      // Load the archive and get a list of files.
      $files = $this->getArchiveContents($submission_exporter->getArchiveFilePath());

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
        'archive_type' => $archive_type,
      ];
      $this->drupalPostForm('/admin/structure/webform/manage/test_element_managed_file/results/download', $edit, t('Download'));

      // Load the archive and get a list of files.
      $files = $this->getArchiveContents($submission_exporter->getArchiveFilePath());

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

  /**
   * Get archive contents.
   *
   * @param string $filepath
   *   Archive file path.
   *
   * @return array
   *   Array of archive contents.
   */
  protected function getArchiveContents($filepath) {
    if (strpos($filepath, '.zip') !== FALSE) {
      $archive = new \ZipArchive();
      $archive->open($filepath);
      $files = [];
      for ($i = 0; $i < $archive->numFiles; $i++) {
        $files[] = $archive->getNameIndex($i);
      }
    }
    else {
      $archive = new \Archive_Tar($filepath, 'gz');
      $files = [];
      foreach ($archive->listContent() as $file_data) {
        $files[] = $file_data['filename'];
      }
    }
    return array_combine($files, $files);
  }

}
