<?php

namespace Drupal\webform_entity_print\Plugin\WebformExporter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_print\Plugin\ExportTypeManagerInterface;
use Drupal\entity_print\PrintBuilderInterface;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformExporter\DocumentBaseWebformExporter;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a Webform Entity Print PDF exporter.
 *
 * @WebformExporter(
 *   id = "webform_entity_print",
 *   archive = TRUE,
 *   options = FALSE,
 *   deriver = "Drupal\webform_entity_print\Plugin\Derivative\WebformEntityPrintWebformExporterDeriver",
 * )
 */
class WebformEntityPrintWebformExporter extends DocumentBaseWebformExporter {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The plugin manager for our Print engines.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $printEngineManager;

  /**
   * The export type manager.
   *
   * @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface
   */
  protected $exportTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Print builder.
   *
   * @var \Drupal\entity_print\PrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * Constructs a WebformEntityPrintBaseWebformExporter object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager, RequestStack $request_stack, EntityPrintPluginManagerInterface $print_engine_manager, ExportTypeManagerInterface $export_type_manager, PrintBuilderInterface $print_builder, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config_factory, $entity_type_manager, $element_manager, $token_manager);
    $this->request = $request_stack->getCurrentRequest();
    $this->printEngineManager = $print_engine_manager;
    $this->exportTypeManager = $export_type_manager;
    $this->printBuilder = $print_builder;
    $this->fileSystem = $file_system ?: \Drupal::service('file_system');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.token_manager'),
      $container->get('request_stack'),
      $container->get('plugin.manager.entity_print.print_engine'),
      $container->get('plugin.manager.entity_print.export_type'),
      $container->get('entity_print.print_builder'),
      $container->get('file_system')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'view_mode' => 'html',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => [
        'html' => $this->t('HTML'),
        'table' => $this->t('Table'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['view_mode'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {
    $configuration = $this->getConfiguration();

    // Make sure Webform Entity Print template is used.
    // @see webform_entity_print_entity_view_alter()
    $this->request->request->set('_webform_entity_print', TRUE);

    // Set view mode.
    // @see \Drupal\webform\WebformSubmissionViewBuilder::view
    $this->request->request->set('_webform_submissions_view_mode', $configuration['view_mode']);

    // Get print engine.
    $export_type_id = $this->getExportTypeId();
    $print_engine = $this->printEngineManager->createSelectedInstance($export_type_id);

    // Get scheme.
    $scheme = 'temporary';

    // Get file name.
    $file_extension = $this->getExportTypeFileExtension();
    $file_name = $this->getSubmissionBaseName($webform_submission) . '.' . $file_extension;

    // Save printable document.
    $temporary_file_path = $this->printBuilder->savePrintable([$webform_submission], $print_engine, $scheme, $file_name);
    if ($temporary_file_path) {
      $this->addToArchive(file_get_contents($temporary_file_path), $file_name);
      $this->fileSystem->delete($temporary_file_path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchLimit() {
    // Limit batch document export to 10 submissions.
    return 10;
  }

  /****************************************************************************/
  // Export type methods.
  /****************************************************************************/

  /**
   * Get export type id.
   *
   * @return string
   *   The export type id.
   */
  protected function getExportTypeId() {
    return str_replace('webform_entity_print:', '', $this->getPluginId());
  }

  /**
   * Get export type definition.
   *
   * @return array
   *   Export type definition.
   */
  protected function getExportTypeDefinition() {
    $export_type_id = $this->getExportTypeId();
    return $this->exportTypeManager->getDefinition($export_type_id);
  }

  /**
   * Get export type file extension.
   *
   * @return string
   *   Export type file extension.
   */
  protected function getExportTypeFileExtension() {
    $definition = $this->getExportTypeDefinition();
    return $definition['file_extension'];
  }

}
