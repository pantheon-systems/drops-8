<?php

namespace Drupal\webform_views\Plugin\views\filter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\webform_views\Plugin\views\WebformSubmissionCastToNumberTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter based on numeric value of a webform submission.
 *
 * Since webform submission values are stored as string, we introduce additional
 * following definition properties to this handler:
 * - explicit_cast: (bool) Whether the values should be explicitly casted to a
 *   number in the views query
 *
 * @ViewsFilter("webform_submission_numeric_filter")
 */
class WebformSubmissionNumericFilter extends NumericFilter {

  use WebformSubmissionCastToNumberTrait;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.element_info')
    );
  }

  /**
   * WebformSubmissionFieldFilter constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->elementInfoManager = $element_info_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $webform = $this->entityTypeManager->getStorage('webform')->load($this->definition['webform_id']);

    // A set of possible locations where webform element widget might have to
    // be inserted.
    $possible_locations = [];
    $possible_locations[] = ['value'];
    $possible_locations[] = ['value', 'value'];
    $possible_locations[] = ['value', 'min'];
    $possible_locations[] = ['value', 'max'];

    foreach ($possible_locations as $possible_location) {
      $key_exists = FALSE;
      $element = &NestedArray::getValue($form, $possible_location, $key_exists);
      if ($key_exists && isset($element['#type']) && $element['#type'] != 'value') {
        $default_value = end($possible_location);
        $default_value = isset($this->value[$default_value]) ? $this->value[$default_value] : NULL;
        $element = [
            '#title' => $element['#title'],
            '#default_value' => $default_value,
            '#required' => FALSE,
            '#states' => $element['#states'],
          ] + $webform->getElementInitialized($this->definition['webform_submission_field']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function opSimple($field) {
    if ($this->definition['explicit_cast']) {
      $this->query->addWhereExpression($this->options['group'], $this->castToDataType($field) . ' ' . $this->operator . ' ' . $this->castToDataType(':value'), [
        ':value' => $this->value['value'],
      ]);
    }
    else {
      parent::opSimple($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function opBetween($field) {
    if ($this->definition['explicit_cast']) {
      $operator = $this->operator == 'between' ? 'BETWEEN' : 'NOT BETWEEN';
      $this->query->addWhereExpression($this->options['group'], $this->castToDataType($field) . ' ' . $operator . ' ' . $this->castToDataType(':min') . ' AND ' . $this->castToDataType(':max'), [
        ':min' => $this->value['min'],
        ':max' => $this->value['max'],
      ]);
    }
    else {
      parent::opBetween($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (parent::acceptExposedInput($input)) {
      if (empty($this->options['exposed'])) {
        return TRUE;
      }

      if (!empty($this->options['expose']['identifier'])) {
        $value = $input[$this->options['expose']['identifier']];

        return (bool) $value;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function operators() {
    $operators = parent::operators();
    unset($operators['regular_expression']);

    return $operators;
  }

  /**
   * Extract a property of a form element.
   *
   * @param array $element
   *   Form element whose property to extract
   * @param string $property
   *   Property to extract
   * @param mixed $default
   *   Default value to use when the property is not defined in the provided
   *   element
   *
   * @return mixed
   *   The property value extracted from form element
   */
  protected function getFormElementProperty($element, $property, $default) {
    if (isset($element[$property])) {
      return $element[$property];
    }

    return $this->elementInfoManager->getInfoProperty($element['#type'], $property, $default);
  }

}
