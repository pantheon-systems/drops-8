<?php

namespace Drupal\diff;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Diff\Diff;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Component\Utility\Xss;

/**
 * Entity comparison service that prepares a diff of a pair of entities.
 */
class DiffEntityComparison {

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Wrapper object for simple configuration from diff.plugins.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $pluginsConfig;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * A list of all the field types from the system and their definitions.
   *
   * @var array
   */
  protected $fieldTypeDefinitions;

  /**
   * The entity parser.
   *
   * @var \Drupal\diff\DiffEntityParser
   */
  protected $entityParser;

  /**
   * The field diff plugin manager service.
   *
   * @var \Drupal\diff\DiffBuilderManager
   */
  protected $diffBuilderManager;

  /**
   * Constructs a DiffEntityComparison object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory.
   * @param \Drupal\diff\DiffFormatter $diff_formatter
   *   The diff formatter service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The plugin manager service.
   * @param \Drupal\diff\DiffEntityParser $entity_parser
   *   The entity parser.
   * @param \Drupal\diff\DiffBuilderManager $diff_builder_manager
   *   The diff builder manager.
   */
  public function __construct(ConfigFactory $config_factory, DiffFormatter $diff_formatter, PluginManagerInterface $plugin_manager, DiffEntityParser $entity_parser, DiffBuilderManager $diff_builder_manager) {
    $this->configFactory = $config_factory;
    $this->pluginsConfig = $this->configFactory->get('diff.plugins');
    $this->diffFormatter = $diff_formatter;
    $this->fieldTypeDefinitions = $plugin_manager->getDefinitions();
    $this->entityParser = $entity_parser;
    $this->diffBuilderManager = $diff_builder_manager;
  }

  /**
   * This method should return an array of items ready to be compared.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $left_entity
   *   The left entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $right_entity
   *   The right entity.
   *
   * @return array
   *   Items ready to be compared by the Diff component.
   */
  public function compareRevisions(ContentEntityInterface $left_entity, ContentEntityInterface $right_entity) {
    $result = array();

    $left_values = $this->entityParser->parseEntity($left_entity);
    $right_values = $this->entityParser->parseEntity($right_entity);

    foreach ($left_values as $left_key => $values) {
      list (, $field_key) = explode(':', $left_key);
      // Get the compare settings for this field type.
      $compare_settings = $this->pluginsConfig->get('fields.' . $field_key);
      $result[$left_key] = [
        '#name' => (isset($compare_settings['settings']['show_header']) && $compare_settings['settings']['show_header'] == 0) ? '' : $values['label'],
        '#settings' => $compare_settings,
        '#data' => [],
      ];

      // Fields which exist on the right entity also.
      if (isset($right_values[$left_key])) {
        $result[$left_key]['#data'] += $this->combineFields($left_values[$left_key], $right_values[$left_key]);
        // Unset the field from the right entity so that we know if the right
        // entity has any fields that left entity doesn't have.
        unset($right_values[$left_key]);
      }
      // This field exists only on the left entity.
      else {
        $result[$left_key]['#data'] += $this->combineFields($left_values[$left_key], []);
      }
    }

    // Fields which exist only on the right entity.
    foreach ($right_values as $right_key => $values) {
      list (, $field_key) = explode(':', $right_key);
      $compare_settings = $this->pluginsConfig->get('fields.' . $field_key);
      $result[$right_key] = [
        '#name' => (isset($compare_settings['settings']['show_header']) && $compare_settings['settings']['show_header'] == 0) ? '' : $values['label'],
        '#settings' => $compare_settings,
        '#data' => [],
      ];
      $result[$right_key]['#data'] += $this->combineFields([], $right_values[$right_key]);
    }

    return $result;
  }

  /**
   * Combine two fields into an array with keys '#left' and '#right'.
   *
   * @param array $left_values
   *   Entity field formatted into an array of strings.
   * @param array $right_values
   *   Entity field formatted into an array of strings.
   *
   * @return array
   *   Array resulted after combining the left and right values.
   */
  protected function combineFields(array $left_values, array $right_values) {
    $result = array(
      '#left' => array(),
      '#right' => array(),
    );
    $max = max(array(count($left_values), count($right_values)));
    for ($delta = 0; $delta < $max; $delta++) {
      // EXPERIMENTAL: Transform thumbnail from ImageFieldBuilder.
      // @todo Make thumbnail / rich diff data pluggable.
      // @see https://www.drupal.org/node/2840566
      if (isset($left_values[$delta])) {
        $value = $left_values[$delta];
        if (isset($value['#thumbnail'])) {
          $result['#left_thumbnail'][] = $value['#thumbnail'];
        }
        else {
          $result['#left'][] = is_array($value) ? implode("\n", $value) : $value;
        }
      }
      if (isset($right_values[$delta])) {
        $value = $right_values[$delta];
        if (isset($value['#thumbnail'])) {
          $result['#right_thumbnail'][] = $value['#thumbnail'];
        }
        else {
          $result['#right'][] = is_array($value) ? implode("\n", $value) : $value;
        }
      }
    }

    // If a field has multiple values combine them into one single string.
    $result['#left'] = implode("\n", $result['#left']);
    $result['#right'] = implode("\n", $result['#right']);

    return $result;
  }

  /**
   * Prepare the table rows for #type 'table'.
   *
   * @param string $a
   *   The source string to compare from.
   * @param string $b
   *   The target string to compare to.
   * @param bool $show_header
   *   Display diff context headers. For example, "Line x".
   * @param array $line_stats
   *   Tracks line numbers across multiple calls to DiffFormatter.
   *
   * @see \Drupal\Component\Diff\DiffFormatter::format
   *
   * @return array
   *   Array of rows usable with #type => 'table' returned by the core diff
   *   formatter when format a diff.
   */
  public function getRows($a, $b, $show_header = FALSE, array &$line_stats = NULL) {
    if (!isset($line_stats)) {
      $line_stats = array(
        'counter' => array('x' => 0, 'y' => 0),
        'offset' => array('x' => 0, 'y' => 0),
      );
    }

    // Header is the line counter.
    $this->diffFormatter->show_header = $show_header;
    $diff = new Diff($a, $b);

    return $this->diffFormatter->format($diff);
  }

  /**
   * Splits the strings into lines and counts the resulted number of lines.
   *
   * @param array $diff
   *   Array of strings.
   */
  public function processStateLine(array &$diff) {
    $data = $diff['#data'];
    if (isset($data['#left']) && $data['#left'] != '') {
      if (is_string($data['#left'])) {
        $diff['#data']['#left'] = explode("\n", $data['#left']);
      }
      $diff['#data']['#count_left'] = count($diff['#data']['#left']);
    }
    else {
      $diff['#data']['#count_left'] = 0;
      $diff['#data']['#left'] = [];
    }
    if (isset($data['#right']) && $data['#right'] != '') {
      if (is_string($data['#right'])) {
        $diff['#data']['#right'] = explode("\n", $data['#right']);
      }
      $diff['#data']['#count_right'] = count($diff['#data']['#right']);
    }
    else {
      $diff['#data']['#count_right'] = 0;
      $diff['#data']['#right'] = [];
    }
  }

  /**
   * Gets the revision description of the revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The current revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $previous_revision
   *   (optional) The previous revision. Defaults to NULL.
   *
   * @return string
   *   The revision log message.
   */
  public function getRevisionDescription(ContentEntityInterface $revision, ContentEntityInterface $previous_revision = NULL) {
    $summary_elements = [];
    $revision_summary = '';
    // Check if the revision has a revision log message.
    if ($revision instanceof RevisionLogInterface) {
      $revision_summary = Xss::filter($revision->getRevisionLogMessage());
    }
    // Auto generate the revision log.
    if ($revision_summary == '') {
      // If there is a previous revision, load values of both revisions, loop
      // over the current revision fields.
      if ($previous_revision) {
        $left_values = $this->summary($previous_revision);
        $right_values = $this->summary($revision);
        foreach ($right_values as $key => $value) {
          // Unset left values after comparing. Add right value label to the
          // summary if it is changed or new.
          if (isset($left_values[$key])) {
            if ($value['value'] != $left_values[$key]['value']) {
              $summary_elements[] = $value['label'];
            }
            unset($left_values[$key]);
          }
          else {
            $summary_elements[] = $value['label'];
          }
        }
        // Add the remaining left values if not present in the right entity.
        foreach ($left_values as $key => $value) {
          if (!isset($right_values[$key])) {
            $summary_elements[] = $value['label'];
          }
        }
        if (count($summary_elements) > 0) {
          $revision_summary = 'Changes on: ' . implode(', ', $summary_elements);
        }
        else {
          $revision_summary = 'No changes.';
        }
      }
      else {
        $revision_summary = 'Initial revision.';
      }
    }

    return $revision_summary;
  }

  /**
   * Creates an log message based on the changes of entity fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The current revision.
   *
   * @return array
   *   Array of the revision fields with their value and label.
   */
  protected function summary(ContentEntityInterface $revision) {
    $result = [];
    $entity_type_id = $revision->getEntityTypeId();
    // Loop through entity fields and transform every FieldItemList object
    // into an array of strings according to field type specific settings.
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
    foreach ($revision as $field_items) {
      $show_delta = FALSE;
      // Create a plugin instance for the field definition.
      $plugin = $this->diffBuilderManager->createInstanceForFieldDefinition($field_items->getFieldDefinition());
      if ($plugin && $this->diffBuilderManager->showDiff($field_items->getFieldDefinition()->getFieldStorageDefinition())) {
        // Create the array with the fields of the entity. Recursive if the
        // field contains entities.
        if ($plugin instanceof FieldReferenceInterface) {
          foreach ($plugin->getEntitiesToDiff($field_items) as $entity_key => $reference_entity) {
            foreach ($this->summary($reference_entity) as $key => $build) {
              if ($field_items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality() != 1) {
                $show_delta = TRUE;
              }
              $result[$key] = $build;
              $delta = $show_delta ? '<sub>' . ($entity_key + 1) . '</sub> ' : ' - ';
              $result[$key]['label'] = $field_items->getFieldDefinition()->getLabel() . $delta . $result[$key]['label'];
            };
          }
        }
        else {
          // Create a unique flat key.
          $key = $revision->id() . ':' . $entity_type_id . '.' . $field_items->getName();

          $result[$key]['value'] = $field_items->getValue();
          $result[$key]['label'] = $field_items->getFieldDefinition()->getLabel();
        }
      }
    }

    return $result;
  }

}
