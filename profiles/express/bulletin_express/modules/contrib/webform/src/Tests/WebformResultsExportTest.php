<?php

namespace Drupal\webform\Tests;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform results export.
 *
 * @group Webform
 */
class WebformResultsExportTest extends WebformTestBase {

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
  protected static $testWebforms = ['test_element_managed_file', 'test_results'];

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
    $this->drupalLogin($this->adminWebformUser);

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
    $edit = ['export[download][files]' => TRUE];
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
      $fid = $submission->getData('managed_file_single');
      $filename = File::load($fid)->getFilename();

      $this->assert(isset($files["submission-$serial/$filename"]));
    }

    /* Download YAML */

    // Download tar ball archive with YAML documents.
    $edit = [
      'export[download][files]' => TRUE,
      'export[format][exporter]' => 'yaml',
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
      $fid = $submission->getData('managed_file_single');
      $filename = File::load($fid)->getFilename();

      $this->assert(isset($files["submission-$serial.yml"]));
      $this->assert(isset($files["submission-$serial/$filename"]));
    }
  }

  /**
   * Tests export options.
   */
  public function testExportOptions() {
    /** @var \Drupal\webform\WebformInterface $webform */
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    list($webform, $submissions) = $this->createWebformWithSubmissions();

    $this->drupalLogin($this->adminSubmissionUser);

    // Check default options.
    $this->getExport($webform);
    $this->assertRaw('"First name","Last name"');
    $this->assertRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertRaw('Hillary,Clinton');

    // Check delimiter.
    $this->getExport($webform, ['delimiter' => '|']);
    $this->assertRaw('"First name"|"Last name"');
    $this->assertRaw('George|Washington');

    // Check header keys = label.
    $this->getExport($webform, ['header_format' => 'label']);
    $this->assertRaw('"First name","Last name"');

    // Check header keys = key.
    $this->getExport($webform, ['header_format' => 'key']);
    $this->assertRaw('first_name,last_name');

    // Check options format compact.
    $this->getExport($webform, ['options_format' => 'compact']);
    $this->assertRaw('"Flag colors"');
    $this->assertRaw('Red;White;Blue');

    // Check options format separate.
    $this->getExport($webform, ['options_format' => 'separate']);
    $this->assertRaw('"Flag colors: Red","Flag colors: White","Flag colors: Blue"');
    $this->assertNoRaw('"Flag colors"');
    $this->assertRaw('X,X,X');
    $this->assertNoRaw('Red;White;Blue');

    // Check options item format label.
    $this->getExport($webform, ['options_item_format' => 'label']);
    $this->assertRaw('Red;White;Blue');

    // Check options item format key.
    $this->getExport($webform, ['options_item_format' => 'key']);
    $this->assertNoRaw('Red;White;Blue');
    $this->assertRaw('red;white;blue');

    // Check multiple delimiter.
    $this->getExport($webform, ['multiple_delimiter' => '|']);
    $this->assertRaw('Red|White|Blue');
    $this->getExport($webform, ['multiple_delimiter' => ',']);
    $this->assertRaw('"Red,White,Blue"');

    // Check entity reference format link.
    $nodes = $this->getNodes();
    $this->getExport($webform, ['entity_reference_format' => 'link']);
    $this->assertRaw('"Favorite node: ID","Favorite node: Title","Favorite node: URL"');
    $this->assertRaw('' . $nodes[0]->id() . ',"' . $nodes[0]->label() . '",' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check entity reference format id.
    $this->getExport($webform, ['entity_reference_format' => 'id']);
    $this->assertRaw('"Favorite node"');
    $this->assertNoRaw('"Favorite node Title","Favorite node ID","Favorite node URL"');
    $this->assertRaw(',node:' . $nodes[0]->id() . ',');
    $this->assertNoRaw('"' . $nodes[0]->label() . '",' . $nodes[0]->id() . ',' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check likert questions format label.
    $this->getExport($webform, ['header_format' => 'label']);
    $this->assertRaw('"Likert: Question 1","Likert: Question 2","Likert: Question 3"');

    // Check likert questions format key.
    $this->getExport($webform, ['header_format' => 'key']);
    $this->assertNoRaw('"Likert: Question 1","Likert: Question 2","Likert: Question 3"');
    $this->assertRaw('likert__q1,likert__q2,likert__q3');

    // Check likert answers format label.
    $this->getExport($webform, ['likert_answers_format' => 'label']);
    $this->assertRaw('"Answer 1","Answer 1","Answer 1"');

    // Check likert answers format key.
    $this->getExport($webform, ['likert_answers_format' => 'key']);
    $this->assertNoRaw('"Option 1","Option 1","Option 1"');
    $this->assertRaw('1,1,1');

    // Check composite w/o header prefix.
    $this->getExport($webform, ['header_format' => 'label', 'header_prefix' => TRUE]);
    $this->assertRaw('"Address: Address","Address: Address 2","Address: City/Town","Address: State/Province","Address: Zip/Postal Code","Address: Country"');

    // Check composite w header prefix.
    $this->getExport($webform, ['header_format' => 'label', 'header_prefix' => FALSE]);
    $this->assertRaw('Address,"Address 2",City/Town,State/Province,"Zip/Postal Code",Country');

    // Check limit.
    $this->getExport($webform, ['range_type' => 'latest', 'range_latest' => 1]);
    $this->assertRaw('Hillary,Clinton');
    $this->assertNoRaw('George,Washington');
    $this->assertNoRaw('Abraham,Lincoln');

    // Check sid start.
    $this->getExport($webform, ['range_type' => 'sid', 'range_start' => $submissions[1]->id()]);
    $this->assertNoRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertRaw('Hillary,Clinton');

    // Check sid range.
    $this->getExport($webform, ['range_type' => 'sid', 'range_start' => $submissions[1]->id(), 'range_end' => $submissions[1]->id()]);
    $this->assertNoRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    // Check date range.
    $submissions[0]->set('created', strtotime('1/01/2000'))->save();
    $submissions[1]->set('created', strtotime('1/01/2001'))->save();
    $submissions[2]->set('created', strtotime('1/01/2002'))->save();
    $this->getExport($webform, ['range_type' => 'date', 'range_start' => '12/31/2000', 'range_end' => '12/31/2001']);
    $this->assertNoRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    // Check entity type and id hidden.
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->assertNoFieldById('edit-export-download-submitted-entity-type');

    // Change submission 0 & 1 to be submitted user account.
    $submissions[0]->set('entity_type', 'user')->set('entity_id', '1')->save();
    $submissions[1]->set('entity_type', 'user')->set('entity_id', '2')->save();

    // Check entity type and id visible.
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->assertFieldById('edit-export-download-submitted-entity-type');

    // Check entity type limit.
    $this->getExport($webform, ['entity_type' => 'user']);
    $this->assertRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    // Check entity type and id limit.
    $this->getExport($webform, ['entity_type' => 'user', 'entity_id' => '1']);
    $this->assertRaw('George,Washington');
    $this->assertNoRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    // Check changing default exporter to 'table' settings.
    $this->drupalLogin($this->adminWebformUser);
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/download', ['export[format][exporter]' => 'table'], t('Download'));
    $this->assertRaw('<body><table border="1"><thead><tr bgcolor="#cccccc" valign="top"><th>Serial number</th>');
    $this->assertPattern('#<td>George</td>\s+<td>Washington</td>\s+<td>Male</td>#ms');

    // Check changing default export (delimiter) settings.
    $this->drupalLogin($this->adminWebformUser);
    $this->drupalPostForm('admin/structure/webform/settings', ['export[format][delimiter]' => '|'], t('Save configuration'));
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Download'));
    $this->assertRaw('"Submission ID"|"Submission URI"');

    // Check saved webform export (delimiter) settings.
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/download', ['export[format][delimiter]' => '.'], t('Save settings'));
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Download'));
    $this->assertRaw('"Submission ID"."Submission URI"');

    // Check delete webform export (delimiter) settings.
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Reset settings'));
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Download'));
    $this->assertRaw('"Submission ID"|"Submission URI"');
  }

}
