<?php

namespace Drupal\diff\Plugin\diff\Layout;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\diff\DiffEntityComparison;
use Drupal\diff\DiffEntityParser;
use Drupal\diff\DiffLayoutBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides Unified fields diff layout.
 *
 * @DiffLayoutBuilder(
 *   id = "unified_fields",
 *   label = @Translation("Unified fields"),
 *   description = @Translation("Field based layout, displays revision comparison line by line."),
 * )
 */
class UnifiedFieldsDiffLayout extends DiffLayoutBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The diff entity comparison service.
   *
   * @var \Drupal\diff\DiffEntityComparison
   */
  protected $entityComparison;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a UnifiedFieldsDiffLayout object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\diff\DiffEntityParser $entity_parser
   *   The entity parser.
   * @param \Drupal\Core\DateTime\DateFormatter $date
   *   The date service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\diff\DiffEntityComparison $entity_comparison
   *   The diff entity comparison service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, DiffEntityParser $entity_parser, DateFormatter $date, RendererInterface $renderer, DiffEntityComparison $entity_comparison, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config, $entity_type_manager, $entity_parser, $date);
    $this->renderer = $renderer;
    $this->entityComparison = $entity_comparison;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('diff.entity_parser'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('diff.entity_comparison'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(ContentEntityInterface $left_revision, ContentEntityInterface $right_revision, ContentEntityInterface $entity) {
    // Build the revisions data.
    $build = $this->buildRevisionsData($left_revision, $right_revision);

    $active_filter = $this->requestStack->getCurrentRequest()->query->get('filter') ?: 'raw';
    $raw_active = $active_filter == 'raw';

    $build['controls']['filter'] = [
      '#type' => 'item',
      '#title' => $this->t('Filter'),
      '#wrapper_attributes' => ['class' => 'diff-controls__item'],
      'options' => $this->buildFilterNavigation($entity, $left_revision, $right_revision, 'unified_fields', $active_filter),
    ];

    // Build the diff comparison table.
    $diff_header = $this->buildTableHeader($right_revision);
    // Perform comparison only if both entity revisions loaded successfully.
    $fields = $this->entityComparison->compareRevisions($left_revision, $right_revision);
    // Build the diff rows for each field and append the field rows
    // to the table rows.
    $diff_rows = [];
    foreach ($fields as $field) {
      $field_label_row = '';
      if (!empty($field['#name'])) {
        $field_label_row = [
          'data' => $field['#name'],
          'colspan' => 4,
          'class' => ['field-name'],
        ];
      }

      if (!$raw_active) {
        $field_settings = $field['#settings'];
        if (!empty($field_settings['settings']['markdown'])) {
          $field['#data']['#left'] = $this->applyMarkdown($field_settings['settings']['markdown'], $field['#data']['#left']);
          $field['#data']['#right'] = $this->applyMarkdown($field_settings['settings']['markdown'], $field['#data']['#right']);
        }
        // In case the settings are not loaded correctly use drupal_html_to_text
        // to avoid any possible notices when a user clicks on markdown.
        else {
          $field['#data']['#left'] = $this->applyMarkdown('drupal_html_to_text', $field['#data']['#left']);
          $field['#data']['#right'] = $this->applyMarkdown('drupal_html_to_text', $field['#data']['#right']);
        }
      }

      // Process the array (split the strings into single line strings)
      // and get line counts per field.
      $this->entityComparison->processStateLine($field);

      $field_diff_rows = $this->entityComparison->getRows(
        $field['#data']['#left'],
        $field['#data']['#right']
      );

      $final_diff = [];
      $row_count_left = NULL;
      $row_count_right = NULL;
      foreach ($field_diff_rows as $key => $value) {
        $show = FALSE;
        if (isset($field_diff_rows[$key][1]['data'])) {
          if ($field_diff_rows[$key][1] == $field_diff_rows[$key][3]) {
            $show = TRUE;
            $row_count_right++;
          }
          $row_count_left++;
          $final_diff[] = [
            'left-line-number' => [
              'data' => $row_count_left,
              'class' => ['diff-line-number', $field_diff_rows[$key][1]['class']],
            ],
            'right-line-number' => [
              'data' => $show ? $row_count_right : NULL,
              'class' => ['diff-line-number', $field_diff_rows[$key][1]['class']],
            ],
            'row-sign' => [
              'data' => isset($field_diff_rows[$key][0]['data']) ? $field_diff_rows[$key][0]['data'] : NULL,
              'class' => [isset($field_diff_rows[$key][0]['class']) ? $field_diff_rows[$key][0]['class'] : NULL, $field_diff_rows[$key][1]['class']],
            ],
            'row-data' => [
              'data' => $field_diff_rows[$key][1]['data'],
              'colspan' => 2,
              'class' => $field_diff_rows[$key][1]['class'],
            ],
          ];
        }
        if ($field_diff_rows[$key][1] != $field_diff_rows[$key][3]) {
          if (isset($field_diff_rows[$key][3]['data'])) {
            $row_count_right++;
            $final_diff[] = [
              'left-line-number' => [
                'data' => NULL,
                'class' => ['diff-line-number', $field_diff_rows[$key][3]['class']],
              ],
              'right-line-number' => [
                'data' => $row_count_right,
                'class' => ['diff-line-number', $field_diff_rows[$key][3]['class']],
              ],
              'row-sign' => [
                'data' => isset($field_diff_rows[$key][2]['data']) ? $field_diff_rows[$key][2]['data'] : NULL,
                'class' => [isset($field_diff_rows[$key][2]['class']) ? $field_diff_rows[$key][2]['class'] : NULL, $field_diff_rows[$key][3]['class']],
              ],
              'row-data' => [
                'data' => $field_diff_rows[$key][3]['data'],
                'colspan' => 2,
                'class' => $field_diff_rows[$key][3]['class'],
              ],
            ];
          }
        }
      }

      // Add field label to the table only if there are changes to that field.
      if (!empty($final_diff) && !empty($field_label_row)) {
        $diff_rows[] = [$field_label_row];
      }

      // Add field diff rows to the table rows.
      $diff_rows = array_merge($diff_rows, $final_diff);
    }

    if (!$raw_active) {
      // Remove line numbers.
      foreach ($diff_rows as $i => $row) {
        unset($diff_rows[$i]['left-line-number']);
        unset($diff_rows[$i]['right-line-number']);
      }

      // Reduce the colspan.
      $diff_header[0]['colspan'] = 2;
      $diff_rows[0][0]['colspan'] = 2;
    }
    $build['diff'] = [
      '#type' => 'table',
      '#header' => $diff_header,
      '#rows' => $diff_rows,
      '#weight' => 10,
      '#empty' => $this->t('No visible changes'),
      '#attributes' => [
        'class' => ['diff'],
      ],
    ];

    $build['#attached']['library'][] = 'diff/diff.single_column';
    $build['#attached']['library'][] = 'diff/diff.colors';
    return $build;
  }

  /**
   * Build the header for the diff table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   Revision from the right hand side.
   *
   * @return array
   *   Header for Diff table.
   */
  protected function buildTableHeader(EntityInterface $right_revision) {
    $header = [];
    $header[] = [
      'data' => ['#markup' => $this->buildRevisionLink($right_revision)],
      'colspan' => 4,
    ];

    return $header;
  }

}
