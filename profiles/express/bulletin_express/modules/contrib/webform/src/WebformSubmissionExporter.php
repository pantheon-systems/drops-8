<?php

namespace Drupal\webform;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Webform submission exporter.
 */
class WebformSubmissionExporter implements WebformSubmissionExporterInterface {

  use StringTranslationTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Results exporter manager.
   *
   * @var \Drupal\webform\WebformExporterManagerInterface
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
   * @var \Drupal\webform\WebformExporterInterface
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformExporterManagerInterface $exporter_manager
   *   The results exporter manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, StreamWrapperManagerInterface $stream_wrapper_manager, WebformElementManagerInterface $element_manager, WebformExporterManagerInterface $exporter_manager) {
    $this->configFactory = $config_factory;
    $this->entityStorage = $entity_type_manager->getStorage('webform_submission');
    $this->queryFactory = $query_factory;
    $this->streamWrapperManager = $stream_wrapper_manager;
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
   *   Settings name as 'webform.export.{entity_type}.{entity_id}.
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

      'file_name' => 'submission-[webform_submission:serial]',

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
      'state' => 'all',
      'sticky' => '',
      'download' => TRUE,
      'files' => FALSE,
    ];

    // Append element handler default options.
    $element_types = $this->getWebformElementTypes();
    $element_handlers = $this->elementManager->getInstances();
    foreach ($element_handlers as $element_type => $element_handler) {
      if (empty($element_types) || isset($element_types[$element_type])) {
        $this->defaultOptions += $element_handler->getExportDefaultOptions();
      }
    }

    return $this->defaultOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options = []) {
    $default_options = $this->getDefaultExportOptions();
    $export_options = NestedArray::mergeDeep($default_options, $export_options);
    $this->setExporter($export_options);

    $webform = $this->getWebform();

    // Get exporter and build #states.
    $exporter_plugins = $this->exporterManager->getInstances($export_options);
    $states_archive = ['invisible' => []];
    $states_options = ['invisible' => []];
    foreach ($exporter_plugins as $plugin_id => $exporter_plugin) {
      if ($exporter_plugin->isArchive()) {
        if ($states_archive['invisible']) {
          $states_archive['invisible'][] = 'or';
        }
        $states_archive['invisible'][] = [':input[name="export[format][exporter]"]' => ['value' => $plugin_id]];
      }
      if (!$exporter_plugin->hasOptions()) {
        if ($states_options['invisible']) {
          $states_options['invisible'][] = 'or';
        }
        $states_options['invisible'][] = [':input[name="export[format][exporter]"]' => ['value' => $plugin_id]];
      }
    }

    $form['export']['#tree'] = TRUE;

    $form['export']['format'] = [
      '#type' => 'details',
      '#title' => $this->t('Format options'),
      '#open' => TRUE,
    ];
    $form['export']['format']['exporter'] = [
      '#type' => 'select',
      '#title' => $this->t('Export format'),
      '#options' => $this->exporterManager->getOptions(),
      '#default_value' => $export_options['exporter'],
      // Below .js-webform-exporter is used for exporter configuration form
      // #states.
      // @see \Drupal\webform\WebformExporterBase::buildConfigurationForm
      '#attributes' => ['class' => ['js-webform-exporter']],
    ];
    foreach ($exporter_plugins as $plugin_id => $exporter) {
      $form['export']['format'] = $exporter->buildConfigurationForm($form['export']['format'], $form_state);
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
      '#description' => $this->t('This is the delimiter when an element has multiple values.'),
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
      '#title' => $this->t("Include an element's title with all sub elements and values in each column header."),
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
          ':input[name="export[header][header_prefix]"]' => ['checked' => TRUE],
          ':input[name="export[header][header_format]"]' => ['value' => 'label'],
        ],
      ];
      $form['export']['header']['header_prefix_key_delimiter']['#states'] = [
        'visible' => [
          ':input[name="export[header][header_prefix]"]' => ['checked' => TRUE],
          ':input[name="export[header][header_format]"]' => ['value' => 'key'],
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
    $element_handlers = $this->elementManager->getInstances();
    foreach ($element_handlers as $element_type => $element_handler) {
      if (empty($element_types) || isset($element_types[$element_type])) {
        $element_handler->buildExportOptionsForm($form['export']['elements'], $form_state, $export_options);
      }
    }

    // All the remain options are only applicable to a webform's export.
    // @see Drupal\webform\Form\WebformResultsExportForm
    if (!$webform) {
      return;
    }

    // Elements.
    $form['export']['columns'] = [
      '#type' => 'details',
      '#title' => $this->t('Column options'),
      '#states' => $states_options,
    ];
    $form['export']['columns']['excluded_columns'] = [
      '#type' => 'webform_excluded_columns',
      '#description' => $this->t('The selected columns will be included in the export.'),
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
      '#description' => $this->t('If checked, the exported file and any submission file uploads will be download in a gzipped tar file.'),
      '#return_value' => TRUE,
      '#access' => $webform->hasManagedFile(),
      '#states' => [
        'invisible' => [
          ':input[name="export[download][download]"]' => ['checked' => FALSE],
        ],
      ],
      '#default_value' => ($webform->hasManagedFile()) ? $export_options['files'] : 0,
    ];

    $source_entity = $this->getSourceEntity();
    if (!$source_entity) {
      $entity_types = $this->entityStorage->getSourceEntityTypes($webform);
      if ($entity_types) {
        $form['export']['download']['submitted'] = [
          '#type' => 'item',
          '#title' => $this->t('Submitted to'),
          '#description' => $this->t('Select the entity type and then enter the entity id.'),
          '#field_prefix' => '<div class="container-inline">',
          '#field_suffix' => '</div>',
        ];
        $form['export']['download']['submitted']['entity_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Entity type'),
          '#title_display' => 'Invisible',
          '#options' => ['' => $this->t('All')] + $entity_types,
          '#default_value' => $export_options['entity_type'],
        ];
        $form['export']['download']['submitted']['entity_id'] = [
          '#type' => 'number',
          '#title' => $this->t('Entity id'),
          '#title_display' => 'Invisible',
          '#min' => 1,
          '#size' => 10,
          '#default_value' => $export_options['entity_id'],
          '#states' => [
            'invisible' => [
              ':input[name="export[download][submitted][entity_type]"]' => ['value' => ''],
            ],
          ],
        ];
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
          ':input[name="export[download][range_type]"]' => ['value' => 'latest'],
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
        '#states' => [
          'visible' => [
            ':input[name="export[download][range_type]"]' => ['value' => $key],
          ],
        ],
      ];
      $form['export']['download'][$key]['range_start'] = $range_element + [
          '#title' => $this->t('From'),
          '#default_value' => $export_options['range_start'],
        ];
      $form['export']['download'][$key]['range_end'] = $range_element + [
          '#title' => $this->t('To'),
          '#default_value' => $export_options['range_end'],
        ];
    }

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
      '#access' => ($webform->getSetting('draft') != WebformInterface::DRAFT_ENABLED_NONE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValuesFromInput(array $input) {
    if (empty($input['export'])) {
      return [];
    }
    $export_values = $input['export'];
    $values = [];

    // Append download/range type, submitted, and sticky.
    if (isset($export_values['download'])) {
      if (isset($export_values['download']['download'])) {
        $values['download'] = $export_values['download']['download'];
      }
      if (isset($export_values['download']['state'])) {
        $values['state'] = $export_values['download']['state'];
      }
      if (isset($export_values['download']['files'])) {
        $values['files'] = $export_values['download']['files'];
      }
      if (isset($export_values['download']['sticky'])) {
        $values['sticky'] = $export_values['download']['sticky'];
      }
      if (!empty($export_values['download']['submitted']['entity_type'])) {
        $values += $export_values['download']['submitted'];
      }
      if (isset($export_values['download']['range_type'])) {
        $range_type = $export_values['download']['range_type'];
        $values['range_type'] = $range_type;
        if (isset($export_values['download'][$range_type])) {
          $values += $export_values['download'][$range_type];
        }
      }
    }

    // Append format.
    if (isset($export_values['format'])) {
      $values += $export_values['format'];
    }

    // Append element.
    if (isset($export_values['element'])) {
      $values += $export_values['element'];
    }

    // Append header.
    if (isset($export_values['header'])) {
      $values += $export_values['header'];
    }

    // Append columns.
    if (isset($export_values['columns'])) {
      $values += $export_values['columns'];
    }

    // Append (and flatten) elements.
    // http://stackoverflow.com/questions/1319903/how-to-flatten-a-multidimensional-array
    $default_options = $this->getDefaultExportOptions();
    array_walk_recursive($export_values['elements'], function ($item, $key) use (&$values, $default_options) {
      if (isset($default_options[$key])) {
        $values[$key] = $item;
      }
    });

    return $values;
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
      $archiver = new ArchiveTar($this->getArchiveFilePath(), 'gz');
      $stream_wrappers = array_keys($this->streamWrapperManager->getNames(StreamWrapperInterface::WRITE_VISIBLE));
      foreach ($stream_wrappers as $stream_wrapper) {
        $files_directory = \Drupal::service('file_system')->realpath($stream_wrapper . '://webform/' . $webform->id());
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
            $archiver->addModify($submission_directory, $file_name, $submission_directory);
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
      $archive_file_path = $this->getArchiveFilePath();

      $archiver = new ArchiveTar($archive_file_path, 'gz');
      $archiver->addModify($export_file_path, $this->getBaseFileName(), $this->getFileTempDirectory());

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

    $query = $this->queryFactory->get('webform_submission')->condition('webform_id', $webform->id());

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
          $query->condition('created', strtotime($export_options['range_end']), '<=');
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
      $latest_query->sort('sid', 'DESC');
      $latest_query->range(0, (int) $export_options['range_latest']);
      if ($latest_query_entity_ids = $latest_query->execute()) {
        $query->condition('sid', end($latest_query_entity_ids), '>=');
      }
    }

    // Sort by sid with the oldest one first.
    $query->sort('sid', 'ASC');

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
    return $this->configFactory->get('webform.settings')->get('batch.default_batch_export_size') ?: 500;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresBatch() {
    return ($this->getTotal() > $this->getBatchLimit()) ? TRUE : FALSE;
  }

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

}
