<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a results exporter.
 *
 * @see \Drupal\webform\Plugin\WebformExporterInterface
 * @see \Drupal\webform\Plugin\WebformExporterManager
 * @see \Drupal\webform\Plugin\WebformExporterManagerInterface
 * @see plugin_api
 */
abstract class WebformExporterBase extends PluginBase implements WebformExporterInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformExporterBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $entity_type_manager->getStorage('webform_submission');
    $this->elementManager = $element_manager;
    $this->tokenManager = $token_manager;
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
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() {
    return $this->configFactory->get('webform.settings')->get('export.excluded_exporters.' . $this->pluginDefinition['id']) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isArchive() {
    return $this->pluginDefinition['archive'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasOptions() {
    return $this->pluginDefinition['options'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'webform' => NULL,
      'source_entity' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Get the webform whose submissions are being exported.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  protected function getWebform() {
    return $this->configuration['webform'];
  }

  /**
   * Get the webform source entity whose submissions are being exported.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A webform's source entity.
   */
  protected function getSourceEntity() {
    return $this->configuration['source_entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function createExport() {}

  /**
   * {@inheritdoc}
   */
  public function openExport() {}

  /**
   * {@inheritdoc}
   */
  public function closeExport() {}

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {}

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function writeFooter() {}

  /**
   * {@inheritdoc}
   */
  public function getFileTempDirectory() {
    return file_directory_temp();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionBaseName(WebformSubmissionInterface $webform_submission) {
    $export_options = $this->getConfiguration();
    $file_name = $export_options['file_name'];
    $file_name = $this->tokenManager->replace($file_name, $webform_submission);

    // Sanitize file name.
    // @see http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    $file_name = preg_replace('([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})', '', $file_name);
    $file_name = preg_replace('/\s+/', '-', $file_name);
    return $file_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension() {
    return 'txt';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFileName() {
    $webform = $this->getWebform();
    $source_entity = $this->getSourceEntity();
    if ($source_entity) {
      return $webform->id() . '.' . $source_entity->getEntityTypeId() . '.' . $source_entity->id();
    }
    else {
      return $webform->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFileName() {
    return $this->getBaseFileName() . '.' . $this->getFileExtension();
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFilePath() {
    return $this->getFileTempDirectory() . '/' . $this->getExportFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFilePath() {
    return $this->getFileTempDirectory() . '/' . $this->getArchiveFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFileName() {
    return $this->getBaseFileName() . '.tar.gz';
  }

}
