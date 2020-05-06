<?php

namespace Drupal\webform_submission_export_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionGenerateInterface;
use Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provides route responses for webform submission export/import.
 */
class WebformSubmissionExportImportController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $webformSubmissionStorage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform submission generation service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $generate;

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface
   */
  protected $importer;

  /**
   * Constructs a WebformSubmissionExportImportController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate
   *   The webform submission generation service.
   * @param \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $importer
   *   The webform submission importer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler, WebformSubmissionGenerateInterface $submission_generate, WebformSubmissionExportImportImporterInterface $importer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->webformSubmissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->requestHandler = $request_handler;
    $this->generate = $submission_generate;
    $this->importer = $importer;

    // Initialize the importer.
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity();
    $this->importer->setWebform($webform);
    $this->importer->setSourceEntity($source_entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('webform.request'),
      $container->get('webform_submission.generate'),
      $container->get('webform_submission_export_import.importer')
    );
  }

  /**
   * Returns the Webform submission export example CSV view.
   */
  public function view() {
    return $this->createResponse(FALSE);
  }

  /**
   * Returns the Webform submission export example CSV download.
   */
  public function download() {
    return $this->createResponse(TRUE);
  }

  /**
   * Create a response containing submission CSV example.
   *
   * @param bool $download
   *   TRUE is response should be downloaded.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response containing submission CSV example.
   */
  protected function createResponse($download = FALSE) {
    $webform = $this->importer->getWebform();

    // From: http://obtao.com/blog/2013/12/export-data-to-a-csv-file-with-symfony/
    $response = new StreamedResponse(function () {
      $handle = fopen('php://output', 'r+');

      $header = $this->importer->exportHeader();
      fputcsv($handle, $header);

      for ($i = 1; $i <= 3; $i++) {
        $webform_submission = $this->generateSubmission($i);
        $record = $this->importer->exportSubmission($webform_submission);
        fputcsv($handle, $record);
      }

      fclose($handle);
    });

    $response->headers->set('Content-Type', $download ? 'text/csv' : 'text/plain');
    $response->headers->set('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename=' . $webform->id() . '.csv');
    return $response;
  }

  /**
   * Generate an unsaved webform submission.
   *
   * @param int $index
   *   The submission's index used for the sid and serial number.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   An unsaved webform submission.
   */
  protected function generateSubmission($index) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity();

    $users = $this->entityTypeManager->getStorage('user')->getQuery()->execute();
    $uid = array_rand($users);

    $url = $webform->toUrl();
    if ($source_entity && $source_entity->hasLinkTemplate('canonical')) {
      $url = $source_entity->toUrl();
    }

    return $this->webformSubmissionStorage->create([
      'sid' => $index,
      'serial' => $index,
      'webform_id' => $webform->id(),
      'entity_type' => ($source_entity) ? $source_entity->getEntityTypeId() : '',
      'entity_id' => ($source_entity) ? $source_entity->id() : '',
      'uid' => $uid,
      'remote_addr' => mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255),
      'uri' => preg_replace('#^' . base_path() . '#', '/', $url->toString()),
      'data' => Yaml::encode($this->generate->getData($webform)),
      'created' => strtotime('-1 year'),
      'completed' => rand(strtotime('-1 year'), time()),
      'changed' => time(),
    ]);
  }

}
