<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformExporterManagerInterface;
use Drupal\webform\WebformSubmissionExporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for exporters.
 */
class WebformAdminConfigExportersForm extends WebformAdminConfigBaseForm {

  /**
   * The webform exporter manager.
   *
   * @var \Drupal\webform\Plugin\WebformExporterManagerInterface
   */
  protected $exporterManager;

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_exporters_form';
  }

  /**
   * Constructs a WebformAdminConfigExportersForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\webform\Plugin\WebformExporterManagerInterface $exporter_manager
   *   The webform exporter manager.
   * @param \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter
   *   The webform submission exporter.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformExporterManagerInterface $exporter_manager, WebformSubmissionExporterInterface $submission_exporter) {
    parent::__construct($config_factory);
    $this->exporterManager = $exporter_manager;
    $this->submissionExporter = $submission_exporter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.webform.exporter'),
      $container->get('webform_submission.exporter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    // Export.
    $form['export_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Default export settings'),
      '#description' => $this->t('Enter default export settings to be used by all webforms.'),
      '#open' => TRUE,
    ];

    $export_options = $config->get('export') ;
    $export_form_state = new FormState();
    $this->submissionExporter->buildExportOptionsForm($form['export_settings'], $export_form_state, $export_options);

    // (Excluded) Exporters.
    $form['exporter_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission exporters'),
      '#description' => $this->t('Select available submission exporters'),
      '#open' => TRUE,
    ];
    $form['exporter_types']['excluded_exporters'] = $this->buildExcludedPlugins(
      $this->exporterManager,
      $config->get('export.excluded_exporters') ?: [] ?: []
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $excluded_exporters = $this->convertIncludedToExcludedPluginIds($this->exporterManager, $form_state->getValue('excluded_exporters'));

    $config = $this->config('webform.settings');
    $config->set('export', $this->submissionExporter->getValuesFromInput($form_state->getValues()) + ['excluded_exporters' => $excluded_exporters]);
    $config->save();

    parent::submitForm($form, $form_state);
  }
  
}
