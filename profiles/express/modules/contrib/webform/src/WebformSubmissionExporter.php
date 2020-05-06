<?php

namespace Drupal\webform;

use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformExporterInterface;
use Drupal\webform\Plugin\WebformExporterManagerInterface;

/**
 * Webform submission exporter.
 */
class WebformSubmissionExporter implements WebformSubmissionExporterInterface {

  use StringTranslationTrait;
  use WebformAjaxElementTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The archiver manager.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Results exporter manager.
   *
   * @var \Drupal\webform\Plugin\WebformExporterManagerInterface
   */
  protected $exporterManager;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The results exporter.
   *
   * @var \Drupal\webform\Plugin\WebformExporterInterface
   */
  protected $exporter;

  /**
   * Default export options.
   *
   * @var array
   */
  protected $defaultOptions;

  /**
   * Webform element types.
   *
   * @var array
   */
  protected $elementTypes;

  /**
   * Constructs a WebformSubmissionExporter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Archiver\ArchiverManager $archiver_manager
   *   The archiver manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\Plugin\WebformExporterManagerInterface $exporter_manager
   *   The results exporter manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, StreamWrapperManagerInterface $stream_wrapper_manager, ArchiverManager $archiver_manager, WebformElementManagerInterface $element_manager, WebformExporterManagerInterface $exporter_manager) {
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->entityStorage = $entity_type_manager->getStorage('webform_submission');
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->archiverManager = $archiver_manager;
    $this->elementManager = $element_manager;
    $this->exporterManager = $exporter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebform(WebformInterface $webform = NULL) {
    $this->webform = $webform;
    $this->defaultOptions = NULL;
    $this->elementTypes = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $entity = NULL) {
    $this->sourceEntity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    return $this->sourceEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformOptions() {
    $name = $this->getWebformOptionsName();
    return $this->getWebform()->getState($name, []);
  }

  /**
   * {@inheritdoc}
   */
  public function setWebformOptions(array $options = []) {
    $name = $this->getWebformOptionsName();
    $this->getWebform()->setState($name, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWebformOptions() {
    $name = $this->getWebformOptionsName();
    $this->getWebform()->deleteState($name);
  }

  /**
   * Get options name for current webform and source entity.
   *
   * @return string
   *   Settings name as 'webform.export.{entity_type}.{entity_id}'.
   */
  protected function getWebformOptionsName() {
    if ($entity = $this->getSourceEntity()) {
      return 'results.export.' . $entity->getEntityTypeId() . '.' . $entity->id();
    }
    else {
      return 'results.export';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setExporter(array $export_options = []) {
    $export_options += $this->getDefaultExportOptions();
    $export_options['webform'] = $this->getWebform();
    $export_options['source_entity'] = $this->getSourceEntity();
    $this->exporter = $this->exporterManager->createInstance($export_options['exporter'], $export_options);
    return $this->exporter;
  }

  /**
   * {@inheritdoc}
   */
  public function getExporter() {
    return $this->exporter;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportOptions() {
    return $this->getExporter()->getConfiguration();
  }

  /****************************************************************************/
  // Default options and webform.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getDefaultExportOptions() {
    if (isset($this->defaultOptions)) {
      return $this->defaultOptions;
    }

    $this->defaultOptions = [
      'exporter' => 'delimited',

      'delimiter' => ',',
      'multiple_delimiter' => ';',
      'excel' => FALSE,

      'file_name' => 'submission-[webform_submission:serial]',
      'archive_type' => 'tar',

      'header_format' => 'label',
      'header_prefix' => TRUE,
      'header_prefix_label_delimiter' => ': ',
      'header_prefix_key_delimiter' => '__',
      'excluded_columns' => [
        'uuid' => 'uuid',
        'token' => 'token',
        'webform_id' => 'webform_id',
      ],

      'entity_type' => '',
      'entity_id' => '',
      'range_type' => 'all',
      'range_latest' => '',
      'range_start' => '',
      'range_end' => '',
      'order' => 'asc',
      'state' => 'all',
      'locked' => '',
      'sticky' => '',
      'download' => TRUE,
      'files' => FALSE,
    ];

    // Append webform exporter default options.
    $exporter_plugins = $this->exporterManager->getInstances();
    foreach ($exporter_plugins as $element_type => $element_plugin) {
      $this->defaultOptions += $element_plugin->defaultConfiguration();
    }

    // Append webform element default options.
    $element_types = $this->getWebformElementTypes();
    $element_plugins = $this->elementManager->getInstances();
    foreach ($element_plugins as $element_type => $element_plugin) {
      if (empty($element_types) || isset($element_types[$element_type])) {
        $this->defaultOptions += $element_plugin->getExportDefaultOptions();
      }
    }

    return $this->defaultOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options = []) {
    $export_options += $this->getDefaultExportOptions();
    $this->setExporter($export_options);

    $webform = $this->getWebform();

    // Get exporter plugins.
    $exporter_plugins = $this->exporterManager->getInstances($export_options);

    // Build #states.
    $states_archive = ['invisible' => []];
    $states_options = ['invisible' => []];
    $states_files = [
      'invisible' => [
        [':input[name="download"]' => ['checked' => FALSE]],
      ],
    ];
    $states_archive_type = ['visible' => []];
    if ($webform && $webform->hasManagedFile()) {
      $states_archive_type['visible'][] = [':input[name="files"]' => ['checked' => TRUE]];
    }
    foreach ($exporter_plugins as $plugin_id => $exporter_plugin) {
      if ($exporter_plugin->isArchive()) {
        $this->appendExporterToStates($states_archive, $plugin_id);
      }
      if (!$exporter_plugin->hasOptions()) {
        $this->appendExporterToStates($states_options, $plugin_id);
      }
      if (!$exporter_plugin->hasFiles()) {
        $this->appendExporterToStates($states_files, $plugin_id);
      }
      if ($webform && $exporter_plugin->isArchive()) {
        $this->appendExporterToStates($states_archive_type, $plugin_id);
      }
    }
    $form['#attributes']['data-webform-states-no-clear'] = TRUE;

    // Build the list of exporter descriptions.
    $exporters = $this->exporterManager->getInstances();
    $exporter_description = '';
    foreach ($exporters as $exporter) {
      $exporter_description .= '<hr/>';
      $exporter_description .= '<div><strong>' . $exporter->label() . '</strong></div>';
      $exporter_description .= '<div>' . $exporter->description() . '</div>';
    }

    $form['export']['format'] = [
      '#type' => 'details',
      '#title' => $this->t('Format options'),
      '#open' => TRUE,
    ];
    $form['export']['format']['exporter'] = [
      '#type' => 'select',
      '#title' => $this->t('Export format'),
      '#options' => $this->exporterManager->getOptions(),
      '#description' => $exporter_description,
      '#default_value' => $export_options['exporter'],
      // Below .js-webform-exporter is used for exporter configuration form
      // #states.
      // @see \Drupal\webform\Plugin\WebformExporterBase::buildConfigurationForm
      '#attributes' => ['class' => ['js-webform-exporter']],
    ];
    // Exporter configuration forms.
    $form['export']['format']['exporters'] = [
      '#tree' => TRUE,
    ];
    foreach ($exporter_plugins as $plugin_id => $exporter) {
      $subform_state = SubformState::createForSubform($form['export']['format'], $form, $form_state);
      $exporter_form = $exporter->buildConfigurationForm([], $subform_state);
      if ($exporter_form) {
        $form['export']['format']['exporters'][$plugin_id] = [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input.js-webform-exporter' => ['value' => $plugin_id],
            ],
          ],
        ] + $exporter_form;
      }
    }

    // Element.
    $form['export']['element'] = [
      '#type' => 'details',
      '#title' => $this->t('Element options'),
      '#open' => TRUE,
      '#states' => $states_options,
    ];
    $form['export']['element']['multiple_delimiter'] = [
      '#type' => 'select',
      '#title' => $this->t('Element multiple values delimiter'),
      '#description' => $this->t('The delimiter used when an element has multiple values.'),
      '#required' => TRUE,
      '#options' => [
        ';' => $this->t('Semicolon (;)'),
        ',' => $this->t('Comma (,)'),
        '|' => $this->t('Pipe (|)'),
        '.' => $this->t('Period (.)'),
        ' ' => $this->t('Space ()'),
      ],
      '#default_value' => $export_options['multiple_delimiter'],
    ];

    // Header.
    $form['export']['header'] = [
      '#type' => 'details',
      '#title' => $this->t('Header options'),
      '#open' => TRUE,
      '#states' => $states_options,
    ];
    $form['export']['header']['header_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Column header format'),
      '#description' => $this->t('Choose whether to show the element label or element key in each column header.'),
      '#required' => TRUE,
      '#options' => [
        'label' => $this->t('Element titles (label)'),
        'key' => $this->t('Element keys (key)'),
      ],
      '#default_value' => $export_options['header_format'],
    ];

    $form['export']['header']['header_prefix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Include an element's title with all sub elements and values in each column header"),
      '#return_value' => TRUE,
      '#default_value' => $export_options['header_prefix'],
    ];
    $form['export']['header']['header_prefix_label_delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Column header label delimiter'),
      '#required' => TRUE,
      '#default_value' => $export_options['header_prefix_label_delimiter'],
    ];
    $form['export']['header']['header_prefix_key_delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Column header key delimiter'),
      '#required' => TRUE,
      '#default_value' => $export_options['header_prefix_key_delimiter'],
    ];
    if ($webform) {
      $form['export']['header']['header_prefix_label_delimiter']['#states'] = [
        'visible' => [
          ':input[name="header_prefix"]' => ['checked' => TRUE],
          ':input[name="header_format"]' => ['value' => 'label'],
        ],
      ];
      $form['export']['header']['header_prefix_key_delimiter']['#states'] = [
        'visible' => [
          ':input[name="header_prefix"]' => ['checked' => TRUE],
          ':input[name="header_format"]' => ['value' => 'key'],
        ],
      ];
    }

    // Build element specific export webforms.
    // Grouping everything in $form['export']['elements'] so that element handlers can
    // assign #weight to its export options webform.
    $form['export']['elements'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-item']],
      '#states' => $states_options,
    ];
    $element_types = $this->getWebformElementTypes();
    $element_plugins = $this->elementManager->getInstances();
    foreach ($element_plugins as $element_type => $element_plugin) {
      if (empty($element_types) || isset($element_types[$element_type])) {
        $subform_state = SubformState::createForSubform($form['export']['elements'], $form, $form_state);
        $element_plugin->buildExportOptionsForm($form['export']['elements'], $subform_state, $export_options);
      }
    }

    // All the remain options are only applicable to a webform's export.
    // @see Drupal\webform\Form\WebformResultsExportForm
    if ($webform) {
      // Elements.
      $form['export']['columns'] = [
        '#type' => 'details',
        '#title' => $this->t('Column options'),
        '#description' => $this->t('The selected columns will be included in the export.'),
        '#states' => $states_options,
      ];
      $form['export']['columns']['excluded_columns'] = [
        '#type' => 'webform_excluded_columns',
        '#webform_id' => $webform->id(),
        '#default_value' => $export_options['excluded_columns'],
      ];

      // Download options.
      $form['export']['download'] = [
        '#type' => 'details',
        '#title' => $this->t('Download options'),
        '#open' => TRUE,
      ];
      $form['export']['download']['download'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Download export file'),
        '#description' => $this->t('If checked, the export file will be automatically download to your local machine. If unchecked, the export file will be displayed as plain text within your browser.'),
        '#return_value' => TRUE,
        '#default_value' => $export_options['download'],
        '#access' => !$this->requiresBatch(),
        '#states' => $states_archive,
      ];
      $form['export']['download']['files'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Download uploaded files'),
        '#description' => $this->t('If checked, the exported file and any submission file uploads will be download in the archive file.'),
        '#return_value' => TRUE,
        '#default_value' => ($webform->hasManagedFile()) ? $export_options['files'] : 0,
        '#access' => $webform->hasManagedFile(),
        '#states' => $states_files,
      ];

      $source_entity = $this->getSourceEntity();
      if (!$source_entity) {
        $entity_types = $this->entityStorage->getSourceEntityTypes($webform);
        if ($entity_types) {
          $form['export']['download']['submitted'] = [
            '#type' => 'item',
            '#input' => FALSE,
            '#title' => $this->t('Submitted to'),
            '#description' => $this->t('Select the entity type and then enter the entity id.'),
            '#field_prefix' => '<div class="container-inline">',
            '#field_suffix' => '</div>',
          ];
          $form['export']['download']['submitted']['entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Entity type'),
            '#title_display' => 'invisible',
            '#options' => ['' => $this->t('All')] + $entity_types,
            '#default_value' => $export_options['entity_type'],
          ];
          if ($export_options['entity_type']) {
            $source_entity_options = $this->entityStorage->getSourceEntityAsOptions($webform, $export_options['entity_type']);
            if ($source_entity_options) {
              $form['export']['download']['submitted']['entity_id'] = [
                '#type' => 'select',
                '#title' => $this->t('Entity id'),
                '#title_display' => 'invisible',
                '#default_value' => $export_options['entity_id'],
                '#options' => $source_entity_options,
              ];
            }
            else {
              $form['export']['download']['submitted']['entity_id'] = [
                '#type' => 'number',
                '#title' => $this->t('Entity id'),
                '#title_display' => 'invisible',
                '#min' => 1,
                '#size' => 10,
                '#default_value' => $export_options['entity_id'],
              ];
            }
          }
          else {
            $form['export']['download']['submitted']['entity_id'] = [
              '#type' => 'value',
              '#value' => '',
            ];
          }
          $this->buildAjaxElement(
            'webform-submission-export-download-submitted',
            $form['export']['download']['submitted'],
            $form['export']['download']['submitted']['entity_type']
          );
        }
      }

      $form['export']['download']['range_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Limit to'),
        '#options' => [
          'all' => $this->t('All'),
          'latest' => $this->t('Latest'),
          'serial' => $this->t('Submission number'),
          'sid' => $this->t('Submission ID'),
          'date' => $this->t('Date'),
        ],
        '#default_value' => $export_options['range_type'],
      ];
      $form['export']['download']['latest'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container-inline']],
        '#states' => [
          'visible' => [
            ':input[name="range_type"]' => ['value' => 'latest'],
          ],
        ],
        'range_latest' => [
          '#type' => 'number',
          '#title' => $this->t('Number of submissions'),
          '#min' => 1,
          '#default_value' => $export_options['range_latest'],
        ],
      ];
      $ranges = [
        'serial' => ['#type' => 'number'],
        'sid' => ['#type' => 'number'],
        'date' => ['#type' => 'date'],
      ];
      foreach ($ranges as $key => $range_element) {
        $form['export']['download'][$key] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['container-inline']],
          '#tree' => TRUE,
          '#states' => [
            'visible' => [
              ':input[name="range_type"]' => ['value' => $key],
            ],
          ],
        ];
        $form['export']['download'][$key]['range_start'] = $range_element + [
          '#title' => $this->t('From'),
          '#parents' => [$key, 'range_start'],
          '#default_value' => $export_options['range_start'],
        ];
        $form['export']['download'][$key]['range_end'] = $range_element + [
          '#title' => $this->t('To'),
          '#parents' => [$key, 'range_end'],
          '#default_value' => $export_options['range_end'],
        ];
      }
      $form['export']['download']['order'] = [
        '#type' => 'select',
        '#title' => $this->t('Order'),
        '#description' => $this->t('Order submissions by ascending (oldest first) or descending (newest first).'),
        '#options' => [
          'asc' => $this->t('Sort ascending'),
          'desc' => $this->t('Sort descending'),
        ],
        '#default_value' => $export_options['order'],
        '#states' => [
          'visible' => [
            ':input[name="range_type"]' => ['!value' => 'latest'],
          ],
        ],
      ];
      $form['export']['download']['sticky'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Starred/flagged submissions'),
        '#description' => $this->t('If checked, only starred/flagged submissions will be downloaded. If unchecked, all submissions will downloaded.'),
        '#return_value' => TRUE,
        '#default_value' => $export_options['sticky'],
      ];

      // If drafts are allowed, provide options to filter download based on
      // submission state.
      $form['export']['download']['state'] = [
        '#type' => 'radios',
        '#title' => $this->t('Submission state'),
        '#default_value' => $export_options['state'],
        '#options' => [
          'all' => $this->t('Completed and draft submissions'),
          'completed' => $this->t('Completed submissions only'),
          'draft' => $this->t('Drafts only'),
        ],
        '#access' => ($webform->getSetting('draft') != WebformInterface::DRAFT_NONE),
      ];
    }

    // Archive.
    if (class_exists('\ZipArchive')) {
      $form['export']['archive'] = [
        '#type' => 'details',
        '#title' => $this->t('Archive options'),
        '#open' => TRUE,
        '#states' => $states_archive_type,
      ];
      $form['export']['archive']['archive_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Archive file type'),
        '#description' => $this->t('Select the archive file type for submission file uploads and generated documents.'),
        '#default_value' => $export_options['archive_type'],
        '#options' => [
          WebformExporterInterface::ARCHIVE_TAR => $this->t('Tar archive (*.tar.gz)'),
          WebformExporterInterface::ARCHIVE_ZIP => $this->t('ZIP file (*.zip)'),
        ],
      ];
    }
    else {
      $form['export']['archive_type'] = [
        '#type' => 'value',
        '#value' => WebformExporterInterface::ARCHIVE_TAR,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValuesFromInput(array $values) {
    // Get selected exporter configuration.
    if (isset($values['exporter']) && isset($values['exporters'])) {
      if (isset($values['exporters'][$values['exporter']])) {
        $values += $values['exporters'][$values['exporter']];
      }
      unset($values['exporters']);
    }

    if (isset($values['range_type'])) {
      $range_type = $values['range_type'];
      $values['range_type'] = $range_type;
      if (isset($values[$range_type])) {
        $values += $values[$range_type];
      }
    }

    // Make sure only support options are returned.
    $values = array_intersect_key($values, $this->getDefaultExportOptions());

    return $values;
  }

  /**
   * Append exporter plugin id to #states API array.
   *
   * @param array $states
   *   A #states API array.
   * @param string $plugin_id
   *   The exporter plugin id.
   */
  protected function appendExporterToStates(array &$states, $plugin_id) {
    $state = key($states);
    if ($states[$state]) {
      $states[$state][] = 'or';
    }
    $states[$state][] = [':input[name="exporter"]' => ['value' => $plugin_id]];
  }

  /****************************************************************************/
  // Generate and write.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $entity_ids = $this->getQuery()->execute();
    $webform_submissions = WebformSubmission::loadMultiple($entity_ids);

    $this->writeHeader();
    $this->writeRecords($webform_submissions);
    $this->writeFooter();
  }

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {
    // If building a new archive make sure to delete the exist archive.
    if ($this->isArchive()) {
      @unlink($this->getArchiveFilePath());
    }

    $this->getExporter()->createExport();
    $this->getExporter()->writeHeader();
    $this->getExporter()->closeExport();
  }

  /**
   * {@inheritdoc}
   */
  public function writeRecords(array $webform_submissions) {
    $export_options = $this->getExportOptions();
    $webform = $this->getWebform();

    $is_archive = ($this->isArchive() && $export_options['files']);
    $files_directories = [];
    if ($is_archive) {
      $stream_wrappers = array_keys($this->streamWrapperManager->getNames(StreamWrapperInterface::WRITE_VISIBLE));
      foreach ($stream_wrappers as $stream_wrapper) {
        $files_directory = $this->fileSystem->realpath($stream_wrapper . '://webform/' . $webform->id());
        $files_directories[] = $files_directory;
      }
    }

    $this->getExporter()->openExport();
    foreach ($webform_submissions as $webform_submission) {
      if ($is_archive) {
        foreach ($files_directories as $files_directory) {
          $submission_directory = $files_directory . '/' . $webform_submission->id();
          if (file_exists($submission_directory)) {
            $file_name = $this->getSubmissionBaseName($webform_submission);
            $this->getExporter()->addToArchive(
              $submission_directory,
              $file_name,
              ['remove_path' => $submission_directory]
            );
          }
        }
      }

      $this->getExporter()->writeSubmission($webform_submission);
    }
    $this->getExporter()->closeExport();
  }

  /**
   * {@inheritdoc}
   */
  public function writeFooter() {
    $this->getExporter()->openExport();
    $this->getExporter()->writeFooter();
    $this->getExporter()->closeExport();
  }

  /**
   * {@inheritdoc}
   */
  public function writeExportToArchive() {
    $export_file_path = $this->getExportFilePath();
    if (file_exists($export_file_path)) {
      $this->getExporter()->addToArchive(
        $export_file_path,
        $this->getBaseFileName(),
        ['remove_path' => $this->getFileTempDirectory(), 'close' => TRUE]
      );
      @unlink($export_file_path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    $export_options = $this->getExportOptions();

    $webform = $this->getWebform();
    $source_entity = $this->getSourceEntity();

    $query = $this->entityStorage->getQuery()->condition('webform_id', $webform->id());

    // Filter by source entity or submitted to.
    if ($source_entity) {
      $query->condition('entity_type', $source_entity->getEntityTypeId());
      $query->condition('entity_id', $source_entity->id());
    }
    elseif ($export_options['entity_type']) {
      $query->condition('entity_type', $export_options['entity_type']);
      if ($export_options['entity_id']) {
        $query->condition('entity_id', $export_options['entity_id']);
      }
    }

    // Filter by sid or date range.
    switch ($export_options['range_type']) {
      case 'serial':
        if ($export_options['range_start']) {
          $query->condition('serial', $export_options['range_start'], '>=');
        }
        if ($export_options['range_end']) {
          $query->condition('serial', $export_options['range_end'], '<=');
        }
        break;

      case 'sid':
        if ($export_options['range_start']) {
          $query->condition('sid', $export_options['range_start'], '>=');
        }
        if ($export_options['range_end']) {
          $query->condition('sid', $export_options['range_end'], '<=');
        }
        break;

      case 'date':
        if ($export_options['range_start']) {
          $query->condition('created', strtotime($export_options['range_start']), '>=');
        }
        if ($export_options['range_end']) {
          $query->condition('created', strtotime('+1 day', strtotime($export_options['range_end'])), '<');
        }
        break;
    }

    // Filter by (completion) state.
    switch ($export_options['state']) {
      case 'draft':
        $query->condition('in_draft', 1);
        break;

      case 'completed':
        $query->condition('in_draft', 0);
        break;

    }

    // Filter by sticky.
    if ($export_options['sticky']) {
      $query->condition('sticky', 1);
    }

    // Filter by latest.
    if ($export_options['range_type'] == 'latest' && $export_options['range_latest']) {
      // Clone the query and use it to get latest sid starting sid.
      $latest_query = clone $query;
      $latest_query->sort('created', 'DESC');
      $latest_query->sort('sid', 'DESC');
      $latest_query->range(0, (int) $export_options['range_latest']);
      if ($latest_query_entity_ids = $latest_query->execute()) {
        $query->condition('sid', end($latest_query_entity_ids), '>=');
      }
    }
    else {
      // Sort by created and sid in ASC or DESC order.
      $query->sort('created', isset($export_options['order']) ? $export_options['order'] : 'ASC');
      $query->sort('sid', isset($export_options['order']) ? $export_options['order'] : 'ASC');
    }

    // Do not check access to submission since the exporter UI and Drush
    // already have access checking.
    // @see webform_query_webform_submission_access_alter()
    $query->accessCheck(FALSE);

    return $query;
  }

  /**
   * Get element types from a webform.
   *
   * @return array
   *   An array of element types from a webform.
   */
  protected function getWebformElementTypes() {
    if (isset($this->elementTypes)) {
      return $this->elementTypes;
    }
    // If the webform is not set which only occurs on the admin settings webform,
    // return an empty array.
    if (!isset($this->webform)) {
      return [];
    }

    $this->elementTypes = [];
    $elements = $this->webform->getElementsDecodedAndFlattened();
    // Always include 'entity_autocomplete' export settings which is used to
    // expand a webform submission's entity references.
    $this->elementTypes['entity_autocomplete'] = 'entity_autocomplete';
    foreach ($elements as $element) {
      if (isset($element['#type'])) {
        $type = $this->elementManager->getElementPluginId($element);
        $this->elementTypes[$type] = $type;
      }
    }
    return $this->elementTypes;
  }

  /****************************************************************************/
  // Summary and download.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    return $this->getQuery()->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchLimit() {
    return $this->getExporter()->getBatchLimit();
  }

  /**
   * {@inheritdoc}
   */
  public function requiresBatch() {
    // Get the unfiltered total number of submissions for the webform and
    // source entity.
    $total = $this->entityStorage->getTotal(
      $this->getWebform(),
      $this->getSourceEntity()
    );
    return ($total > $this->getBatchLimit()) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileTempDirectory() {
    return $this->configFactory->get('webform.settings')->get('export.temp_directory') ?: file_directory_temp();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionBaseName(WebformSubmissionInterface $webform_submission) {
    return $this->getExporter()->getSubmissionBaseName($webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseFileName() {
    return $this->getExporter()->getBaseFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFilePath() {
    return $this->getExporter()->getExportFilePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFileName() {
    return $this->getExporter()->getExportFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFilePath() {
    return $this->getExporter()->getArchiveFilePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFileName() {
    return $this->getExporter()->getArchiveFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function isArchive() {
    if ($this->getExporter()->isArchive()) {
      return TRUE;
    }
    else {
      $export_options = $this->getExportOptions();
      return ($export_options['download'] && $export_options['files']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isBatch() {
    return ($this->isArchive() || ($this->getTotal() >= $this->getBatchLimit()));
  }

  /**
   * Construct an instance of archive tar object.
   *
   * @return \Archive_Tar
   *   Archive tar object.
   *
   * @deprecated Scheduled for removal in Webform 8.x-6.x
   */
  protected function getArchiveTar() {
    return new \Archive_Tar($this->getArchiveFilePath(), 'gz');
  }

}
