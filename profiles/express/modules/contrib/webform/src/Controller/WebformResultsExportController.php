<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionExporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for webform submission export.
 */
class WebformResultsExportController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformResultsExportController object.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser instance to use.
   * @param \Drupal\webform\WebformSubmissionExporterInterface $webform_submission_exporter
   *   The webform submission exported.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(MimeTypeGuesserInterface $mime_type_guesser, WebformSubmissionExporterInterface $webform_submission_exporter, WebformRequestInterface $request_handler) {
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->submissionExporter = $webform_submission_exporter;
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.mime_type.guesser'),
      $container->get('webform_submission.exporter'),
      $container->get('webform.request')
    );
  }

  /**
   * Returns webform submission as a CSV.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array|null|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   A response that renders or redirects to the CSV file.
   */
  public function index(Request $request) {
    list($webform, $source_entity) = $this->requestHandler->getWebformEntities();
    $this->submissionExporter->setWebform($webform);
    $this->submissionExporter->setSourceEntity($source_entity);

    $query = $request->query->all();
    unset($query['destination']);
    if (isset($query['filename'])) {
      $build = $this->formBuilder()->getForm('Drupal\webform\Form\WebformResultsExportForm');

      // Redirect to file export.
      $file_path = $this->submissionExporter->getFileTempDirectory() . '/' . $query['filename'];
      if (file_exists($file_path)) {
        $route_name = $this->requestHandler->getRouteName($webform, $source_entity, 'webform.results_export_file');
        $route_parameters = $this->requestHandler->getRouteParameters($webform, $source_entity) + ['filename' => $query['filename']];
        $file_url = Url::fromRoute($route_name, $route_parameters, ['absolute' => TRUE])->toString();
        $this->messenger()->addStatus($this->t('Export creation complete. Your download should begin now. If it does not start, <a href=":href">download the file here</a>. This file may only be downloaded once.', [':href' => $file_url]));
        $build['#attached']['html_head'][] = [
          [
            '#tag' => 'meta',
            '#attributes' => [
              'http-equiv' => 'refresh',
              'content' => '0; url=' . $file_url,
            ],
          ],
          'webform_results_export_download_file_refresh',
        ];
      }

      return $build;
    }
    elseif ($query && empty($query['ajax_form']) && isset($query['download'])) {
      $default_options = $this->submissionExporter->getDefaultExportOptions();
      foreach ($query as $key => $value) {
        if (isset($default_options[$key]) && is_array($default_options[$key]) && is_string($value)) {
          $query[$key] = explode(',', $value);
        }
      }
      if (!empty($query['excluded_columns'])) {
        $query['excluded_columns'] = array_combine($query['excluded_columns'], $query['excluded_columns']);
      }
      $export_options = $query + $default_options;
      $this->submissionExporter->setExporter($export_options);
      if ($this->submissionExporter->isBatch()) {
        static::batchSet($webform, $source_entity, $export_options);
        return batch_process($this->requestHandler->getUrl($webform, $source_entity, 'webform.results_export'));
      }
      else {
        $this->submissionExporter->generate();
        $file_path = $this->submissionExporter->getExportFilePath();
        return $this->downloadFile($file_path, $export_options['download']);
      }

    }
    else {
      return $this->formBuilder()->getForm('Drupal\webform\Form\WebformResultsExportForm', $webform);
    }
  }

  /**
   * Returns webform submission results as CSV file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $filename
   *   CSV file name.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   A response that renders or redirects to the CSV file.
   */
  public function file(Request $request, $filename) {
    list($webform, $source_entity) = $this->requestHandler->getWebformEntities();
    $this->submissionExporter->setWebform($webform);
    $this->submissionExporter->setSourceEntity($source_entity);

    $file_path = $this->submissionExporter->getFileTempDirectory() . '/' . $filename;
    if (!file_exists($file_path)) {
      $t_args = [
        ':href' => $this->requestHandler->getUrl($webform, $source_entity, 'webform.results_export')->toString(),
      ];
      $build = [
        '#markup' => $this->t('No export file ready for download. The file may have already been downloaded by your browser. Visit the <a href=":href">download export webform</a> to create a new export.', $t_args),
      ];
      return $build;
    }
    else {
      return $this->downloadFile($file_path);
    }
  }

  /**
   * Download generated CSV file.
   *
   * @param string $file_path
   *   The paths the generate CSV file.
   * @param bool $download
   *   Download the generated CSV file. Default to TRUE.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object containing the CSV file.
   */
  public function downloadFile($file_path, $download = TRUE) {
    $headers = [];

    // If the file is not meant to be downloaded, allow CSV files to be
    // displayed as plain text.
    if (!$download && preg_match('/\.csv$/', $file_path)) {
      $headers['Content-Type'] = 'text/plain';
    }

    $response = new BinaryFileResponse($file_path, 200, $headers, FALSE, $download ? 'attachment' : 'inline');
    // Don't delete the file during automated tests.
    // @see \Drupal\webform\Tests\WebformResultsExportDownloadTest
    // @see \Drupal\Tests\webform_entity_print\Functional\WebformEntityPrintFunctionalTest
    if (!drupal_valid_test_ua()) {
      $response->deleteFileAfterSend(TRUE);
    }
    return $response;
  }

  /****************************************************************************/
  // Batch functions.
  // Using static method to prevent the service container from being serialized.
  // "Prevents exception 'AssertionError' with message 'The container was serialized.'."
  /****************************************************************************/

  /**
   * Batch API; Initialize batch operations.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform source entity.
   * @param array $export_options
   *   An array of export options.
   *
   * @see http://www.jeffgeerling.com/blogs/jeff-geerling/using-batch-api-build-huge-csv
   */
  public static function batchSet(WebformInterface $webform, EntityInterface $source_entity = NULL, array $export_options) {
    if (!empty($export_options['excluded_columns']) && is_string($export_options['excluded_columns'])) {
      $excluded_columns = explode(',', $export_options['excluded_columns']);
      $export_options['excluded_columns'] = array_combine($excluded_columns, $excluded_columns);
    }

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    $parameters = [
      $webform,
      $source_entity,
      $export_options,
    ];
    $batch = [
      'title' => t('Exporting submissions'),
      'init_message' => t('Creating export file'),
      'error_message' => t('The export file could not be created because an error occurred.'),
      'operations' => [
        [['\Drupal\webform\Controller\WebformResultsExportController', 'batchProcess'], $parameters],
      ],
      'finished' => ['\Drupal\webform\Controller\WebformResultsExportController', 'batchFinish'],
    ];

    batch_set($batch);
  }

  /**
   * Batch API callback; Write the header and rows of the export to the export file.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform source entity.
   * @param array $export_options
   *   An associative array of export options.
   * @param mixed|array $context
   *   The batch current context.
   */
  public static function batchProcess(WebformInterface $webform, EntityInterface $source_entity = NULL, array $export_options, &$context) {
    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['offset'] = 0;
      $context['sandbox']['max'] = $submission_exporter->getQuery()->count()->execute();
      // Store entity ids and not the actual webform or source entity in the
      // $context to prevent "The container was serialized" errors.
      // @see https://www.drupal.org/node/2822023
      $context['results']['webform_id'] = $webform->id();
      $context['results']['source_entity_type'] = ($source_entity) ? $source_entity->getEntityTypeId() : NULL;
      $context['results']['source_entity_id'] = ($source_entity) ? $source_entity->id() : NULL;
      $context['results']['export_options'] = $export_options;
      $submission_exporter->writeHeader();
    }

    // Write CSV records.
    $query = $submission_exporter->getQuery();
    $query->range($context['sandbox']['offset'], $submission_exporter->getBatchLimit());
    $entity_ids = $query->execute();
    $webform_submissions = WebformSubmission::loadMultiple($entity_ids);
    $submission_exporter->writeRecords($webform_submissions);

    // Track progress.
    $context['sandbox']['progress'] += count($webform_submissions);
    $context['sandbox']['offset'] += $submission_exporter->getBatchLimit();

    $context['message'] = t('Exported @count of @total submissions…', ['@count' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    // Track finished.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch API callback; Completed export.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of function calls (not used in this function).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to download the exported results.
   */
  public static function batchFinish($success, array $results, array $operations) {
    $webform_id = $results['webform_id'];
    $entity_type = $results['source_entity_type'];
    $entity_id = $results['source_entity_id'];

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load($webform_id);
    /** @var \Drupal\Core\Entity\EntityInterface|null $source_entity */
    $source_entity = ($entity_type && $entity_id) ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id) : NULL;
    /** @var array $export_options */
    $export_options = $results['export_options'];

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    if (!$success) {
      $file_path = $submission_exporter->getExportFilePath();
      @unlink($file_path);
      $archive_path = $submission_exporter->getArchiveFilePath();
      @unlink($archive_path);
      \Drupal::messenger()->addStatus(t('Finished with an error.'));
    }
    else {
      $submission_exporter->writeFooter();

      $filename = $submission_exporter->getExportFileName();

      if ($submission_exporter->isArchive()) {
        $submission_exporter->writeExportToArchive();
        $filename = $submission_exporter->getArchiveFileName();
      }

      /** @var \Drupal\webform\WebformRequestInterface $request_handler */
      $request_handler = \Drupal::service('webform.request');
      $redirect_url = $request_handler->getUrl($webform, $source_entity, 'webform.results_export', ['query' => ['filename' => $filename], 'absolute' => TRUE]);
      return new RedirectResponse($redirect_url->toString());
    }
  }

}
