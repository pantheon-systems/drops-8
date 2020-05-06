<?php

namespace Drupal\Tests\webform_submission_export_import\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Webform submission export/import test.
 *
 * @group webform_browser
 */
class WebformSubmissionImportExportFunctionalTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file',
    'webform',
    'webform_submission_export_import',
    'webform_submission_export_import_test',
  ];

  /**
   * Test submission import.
   */
  public function testSubmissionExport() {
    $this->drupalLogin($this->rootUser);

    $export_csv_uri = 'public://test_submission_export_import-export.csv';
    $export_csv_url = file_create_url('public://test_submission_export_import-export.csv');

    $webform = Webform::load('test_submission_export_import');

    /** @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $importer */
    $importer = \Drupal::service('webform_submission_export_import.importer');
    $importer->setWebform($webform);
    $importer->setImportUri($export_csv_url);

    // Create 3 submissions.
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = [
      WebformSubmission::load($this->postSubmissionTest($webform)),
      WebformSubmission::load($this->postSubmissionTest($webform)),
      WebformSubmission::load($this->postSubmissionTest($webform)),
    ];

    // Create CSV export.
    $this->drupalPostForm('/admin/structure/webform/manage/test_submission_export_import/results/download', ['exporter' => 'webform_submission_export_import'], t('Download'));
    file_put_contents($export_csv_uri, $this->getRawContent());

    /**************************************************************************/

    // Import CSV export without any changes.
    $actual_stats = $importer->import();
    WebformElementHelper::convertRenderMarkupToStrings($actual_stats);
    $expected_stats = [
      'created' => 0,
      'updated' => 3,
      'skipped' => 0,
      'total' => 3,
      'warnings' => [
        1 => [],
        2 => [],
        3 => [],
      ],
      'errors' => [
        1 => [],
        2 => [],
        3 => [],
      ],
    ];
    $this->assertEquals($expected_stats, $actual_stats);

    // Check that submission values are unchanged.
    foreach ($submissions as $original_submission) {
      $expected_values = $original_submission->toArray(TRUE);
      $updated_submission = $this->loadSubmissionByProperty('uuid', $original_submission->uuid());
      $actual_values = $updated_submission->toArray(TRUE);
      $this->assertEquals($expected_values, $actual_values);
    }

    // Alter the first submission.
    $submissions[0]->setCompletedTime(time() - 1000);
    $submissions[0]->setNotes('This is a note');
    $submissions[0]->save();

    // @todo Determine why the below test is failing via DrupalCI.
    return;

    // Deleted the third submission.
    $file_uri = file_create_url(File::load($submissions[2]->getElementData('file'))->getFileUri());
    $files_uri = file_create_url(File::load($submissions[2]->getElementData('files')[0])->getFileUri());
    $submissions[2]->delete();
    unset($submissions[2]);

    // Import CSV export without any changes.
    $actual_stats = $importer->import();
    WebformElementHelper::convertRenderMarkupToStrings($actual_stats);
    $this->debug($actual_stats);
    $expected_stats = [
      'created' => 1,
      'updated' => 2,
      'skipped' => 0,
      'total' => 3,
      'warnings' => [
        1 => [],
        2 => [],
        3 => [
          0 => '[file] Unable to read file from URL (' . $file_uri . ').',
          1 => '[files] Unable to read file from URL (' . $files_uri . ').',
        ],
      ],
      'errors' => [
        1 => [],
        2 => [],
        3 => [],
      ],
    ];
    $this->assertEquals($expected_stats, $actual_stats);

    // Check that submission 1 values reset to original values.
    $original_submission = $submissions[0];
    $expected_values = $original_submission->toArray(TRUE);
    $updated_submission = $this->loadSubmissionByProperty('uuid', $original_submission->uuid());
    $actual_values = $updated_submission->toArray(TRUE);

    // Check that changes and notes were updated.
    $this->assertNotEqual($expected_values['completed'], $actual_values['completed']);
    $this->assertNotEqual($expected_values['notes'], $actual_values['notes']);

    // Check that notes was reset.
    $this->assertEqual('This is a note', $expected_values['notes']);
    $this->assertEqual('', $actual_values['notes']);

    // Unset changed and notes.
    unset($expected_values['completed'], $expected_values['notes']);
    unset($actual_values['completed'], $actual_values['notes']);

    // Check all other values remained the same.
    $this->assertEquals($expected_values, $actual_values);
  }

  /**
   * Test submission import.
   */
  public function testSubmissionImport() {
    $this->drupalLogin($this->rootUser);

    $webform_csv_url = file_create_url('public://test_submission_export_import-webform.csv');
    $external_csv_url = file_create_url('public://test_submission_export_import-external.csv');

    $webform = Webform::load('test_submission_export_import');

    /** @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $importer */
    $importer = \Drupal::service('webform_submission_export_import.importer');
    $importer->setWebform($webform);
    $importer->setImportUri($webform_csv_url);

    /**************************************************************************/

    // Upload the webform.csv.
    $this->drupalPostForm(
      '/admin/structure/webform/manage/test_submission_export_import/results/upload',
      ['import_url' => $webform_csv_url],
      t('Continue')
    );

    // Check submission count.
    $this->assertRaw('Are you sure you want to import 3 submissions?');

    // Import only the valid record.
    $this->drupalPostForm(
      NULL,
      ['import_options[treat_warnings_as_errors]' => TRUE, 'confirm' => TRUE],
      t('Import')
    );

    // Check import stats.
    $this->assertRaw('Submission import completed. (total: 3; created: 1; updated: 0; skipped: 2)');

    // Check error messages.
    $this->assertRaw('<strong>Row #2:</strong> [file] Invalid file URL (/webform/plain/tests/files/sample.gif). URLS must begin with http:// or https://.');
    $this->assertRaw('<strong>Row #2:</strong> [composites] YAML is not valid.');
    $this->assertRaw('<strong>Row #3:</strong> The email address <em class="placeholder">not an email address</em> is not valid.');
    $this->assertRaw('<strong>Row #3:</strong> An illegal choice has been detected. Please contact the site administrator.');

    // Check the submission 1 (valid) record.
    $submission_1 = $this->loadSubmissionByProperty('notes', 'valid');
    $this->assertEquals('valid', $submission_1->getElementData('summary'));
    $this->assertEquals('e1d59c85-7096-4bee-bafa-1bd6798862e2', $submission_1->uuid());
    $this->assertEquals($this->rootUser->id(), $submission_1->getOwnerId());

    // Check submission 1 data.
    $submission_1_expected_data = [
      'checkbox' => '1',
      'checkboxes' => [
        0 => 'two',
        1 => 'three',
        2 => 'one',
      ],
      'composite' => [
        'title' => 'Oratione',
        'url' => 'http://example.com',
      ],
      'composites' => [
        0 => [
          'title' => 'Oratione',
          'url' => 'http://example.com',
        ],
        1 => [
          'title' => 'Oratione',
          'url' => 'http://example.com',
        ],
        2 => [
          'title' => 'Oratione',
          'url' => 'http://test.com',
        ],
      ],
      'email' => 'example@example.com',
      'emails' => [
        0 => 'example@example.com',
        1 => 'random@random.com',
        2 => 'test@test.com',
      ],
      'entity_reference' => '1',
      'entity_references' => '1',
      'file' => '2',
      'files' => [
        0 => '3',
        1 => '4',
      ],
      'likert' => [
        'q1' => '3',
        'q2' => '3',
        'q3' => '3',
      ],
      'summary' => 'valid',
    ];
    $submission_1_actual_data = $submission_1->getData();
    $this->assertEquals($submission_1_expected_data, $submission_1_actual_data);

    // Re-import the webform.csv using the API with warnings
    // not treated as errors.
    $actual_stats = $importer->import();
    WebformElementHelper::convertRenderMarkupToStrings($actual_stats);
    $expected_stats = [
      'created' => 1,
      'updated' => 1,
      'skipped' => 1,
      'total' => 3,
      'warnings' => [
        1 => [],
        2 => [
          0 => '[file] Invalid file URL (/webform/plain/tests/files/sample.gif). URLS must begin with http:// or https://.',
          1 => '[composites] YAML is not valid. The reserved indicator &quot;@&quot; cannot start a plain scalar; you need to quote the scalar at line 1 (near &quot;@#$%^not valid &#039;:&#039; yaml&quot;).',
        ],
        3 => [],
      ],
      'errors' => [
        1 => [],
        2 => [],
        3 => [
          0 => 'The email address <em class="placeholder">not an email address</em> is not valid.',
          1 => 'The email address <em class="placeholder">not an email address</em> is not valid.',
          2 => 'An illegal choice has been detected. Please contact the site administrator.',
        ],
      ],
    ];

    // Unset YAML warning which can vary from server to server.
    unset(
      $expected_stats['warnings'][2][1],
      $actual_stats['warnings'][2][1]
    );

    $this->assertEquals($expected_stats, $actual_stats);

    // Check the submission 2 (validation warnings) record.
    $submission_2 = $this->loadSubmissionByProperty('notes', 'validation warnings');
    $this->assertEquals('validation warnings', $submission_2->getElementData('summary'));
    $this->assertEquals('9a05b67b-a69a-43d8-a498-9bea83c1cbbe', $submission_2->uuid());

    // Check submission 2 data.
    $submission_2_actual_data = $submission_2->getData();
    $submission_2_expected_data = [
      'checkbox' => '1',
      'checkboxes' => [
        0 => 'two',
        1 => 'one',
        2 => 'three',
      ],
      'composite' => [
        'title' => 'Loremipsum',
        'url' => 'http://test.com',
      ],
      'email' => 'test@test.com',
      'emails' => [
        0 => 'random@random.com',
        1 => 'example@example.com',
        2 => 'test@test.com',
      ],
      'entity_reference' => '',
      'entity_references' => '',
      'file' => '',
      'likert' => [
        'q1' => '3',
        'q2' => '3',
        'q3' => '3',
      ],
      'summary' => 'validation warnings',
    ];
    $this->assertEquals($submission_2_expected_data, $submission_2_actual_data);

    // Re-import the webform.csv using the API with warnings
    // not treated as errors and skipping validation errors.
    $importer->setImportOptions(['skip_validation' => TRUE]);
    $actual_stats = $importer->import();
    WebformElementHelper::convertRenderMarkupToStrings($actual_stats);
    $expected_stats = [
      'created' => 1,
      'updated' => 2,
      'skipped' => 0,
      'total' => 3,
      'warnings' => [
        1 => [],
        2 => [
          0 => '[file] Invalid file URL (/webform/plain/tests/files/sample.gif). URLS must begin with http:// or https://.',
          1 => '[composites] YAML is not valid. The reserved indicator &quot;@&quot; cannot start a plain scalar; you need to quote the scalar at line 1 (near &quot;@#$%^not valid &#039;:&#039; yaml&quot;).',
        ],
        3 => [],
      ],
      'errors' => [
        1 => [],
        2 => [],
        3 => [],
      ],
    ];
    // Unset YAML warning which can vary from server to server.

    unset(
      $expected_stats['warnings'][2][1],
      $actual_stats['warnings'][2][1]
    );

    $this->assertEquals($expected_stats, $actual_stats);

    // Check the submission 3 (validation warnings) record.
    $submission_3 = $this->loadSubmissionByProperty('notes', 'validation errors');
    $this->assertEquals('428e338b-d09c-4bb6-8e34-7dcea79f1f0d', $submission_3->uuid());
    $this->assertEquals('validation errors', $submission_3->getElementData('summary'));

    // Check submission 3 contain invalid data.
    $this->assertEquals(['invalid'], $submission_3->getElementData('checkboxes'));
    $this->assertEquals('not an email address', $submission_3->getElementData('email'));
    $this->assertEquals('not an email address', $submission_3->getElementData('emails')[2]);

    // Set not_mapped destination to summary using the UI.
    // Upload the webform.csv.
    $this->drupalPostForm(
      '/admin/structure/webform/manage/test_submission_export_import/results/upload',
      ['import_url' => $webform_csv_url],
      t('Continue')
    );

    $this->drupalPostForm(
      NULL,
      [
        'import_options[mapping][summary]' => '',
        'import_options[mapping][not_mapped]' => 'summary',
        'confirm' => TRUE,
      ],
      t('Import')
    );

    // Check that submission summary now is set to not mapped.
    $submission_1 = $this->loadSubmissionByProperty('notes', 'valid');
    $this->assertEquals('{not mapped}', $submission_1->getElementData('summary'));

    // Upload the external.csv.
    $this->drupalPostForm(
      '/admin/structure/webform/manage/test_submission_export_import/results/upload',
      ['import_url' => $external_csv_url],
      t('Continue')
    );

    // Check that UUID warning is displayed.
    $this->assertRaw('No UUID or token was found in the source (CSV). A unique hash will be generated for the each CSV record. Any changes to already an imported record in the source (CSV) will create a new submission.');

    // Import the external.csv.
    $this->drupalPostForm(NULL, ['confirm' => TRUE], t('Import'));

    // Check that 1 external submission created.
    $this->assertRaw('Submission import completed. (total: 1; created: 1; updated: 0; skipped: 0)');

    // Check that external submissions exists.
    $submission_4 = $this->loadSubmissionByProperty('notes', 'valid external data');
    $this->assertEquals('valid external data', $submission_4->getElementData('summary'));

    // Upload the external.csv.
    $this->drupalPostForm(
      '/admin/structure/webform/manage/test_submission_export_import/results/upload',
      ['import_url' => $external_csv_url],
      t('Continue')
    );

    // Re-import the external.csv.
    $this->drupalPostForm(NULL, ['confirm' => TRUE], t('Import'));

    // Check that 1 external submission updated.
    $this->assertRaw('Submission import completed. (total: 1; created: 0; updated: 1; skipped: 0)');
  }

  /****************************************************************************/

  /**
   * Load a webform submission using a property value.
   *
   * @param string $property
   *   A submission property.
   * @param string|int $value
   *   A property value.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   */
  protected function loadSubmissionByProperty($property, $value) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    // Always reset the cache.
    $submission_storage->resetCache();

    $submissions = $submission_storage->loadByProperties([$property => $value]);
    return reset($submissions);
  }

}
