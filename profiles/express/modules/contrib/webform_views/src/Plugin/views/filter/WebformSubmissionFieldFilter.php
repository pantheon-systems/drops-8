<?php

namespace Drupal\webform_views\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\views\Plugin\views\filter\StringFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter based on value of a webform submission.
 *
 * @ViewsFilter("webform_submission_field_filter")
 */
class WebformSubmissionFieldFilter extends StringFilter {

  /**
   * Constant that denotes using webform element type for value form.
   *
   * @var string
   */
  const ELEMENT_TYPE = 'element';

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
  public function operatorForm(&$form, FormStateInterface $form_state) {
    parent::operatorForm($form, $form_state);

    if (isset($form['operator'])) {
      $do_ajax = TRUE;

      // We must guess the future #parents for this operator here.
      if (!$this->isExposed()) {
        $parents = ['options', 'operator'];
      }
      elseif (!$this->isAGroup()) {
        // We cannot determine which row we are building, so we prefer to hold
        // off ajax.
        $do_ajax = FALSE;
        $parents = [];
      }
      elseif ($this->options['expose']['use_operator']) {
        $do_ajax = FALSE;
        $parents = [$this->options['expose']['operator_id']];
      }
      else {
        // We shouldn't really fall into here. If we do, it means there is yet
        // another unknown use case of this method.
        $do_ajax = FALSE;
        $parents = [];
      }
      $operator = NestedArray::getValue($form_state->getUserInput(), $parents);
      if (!$operator || !isset($this->operators()[$operator])) {
        $operator = $this->operator;
      }

      if ($do_ajax) {
        $process = $this->getFormElementProperty($form['operator'], '#process', []);
        // We need to run before ajax process stuff since we dynamically insert
        // the wrapper value for ajax.
        array_unshift($process, [self::class, 'processOperatorForm']);

        $form['operator']['#ajax'] = [
          'callback' => [self::class, 'ajaxValueForm'],
          'wrapper' => 'this-will-be-set-up-in-process',
        ];
        $form['operator']['#process'] = $process;
      }
      else {
        // We are in the scenario where Ajax will break. So we fall to some
        // inoffensive (with relaxed validation) operator.
        // See https://www.drupal.org/node/2804457 and
        // https://www.drupal.org/node/2842525 for the bugs that force us to do
        // it this way.
        $operator = 'contains';
      }

      $form_state->set(['webform_views', 'filter_operator'], $operator);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    $webform = $this->entityTypeManager->getStorage('webform')->load($this->definition['webform_id']);

    $element = $webform->getElementInitialized($this->definition['webform_submission_field']);
    $element['#default_value'] = $this->value;
    $element['#required'] = FALSE;

    $operator = $form_state->get(['webform_views', 'filter_operator']) ?: $this->operator;
    $operator_definition = $this->operators()[$operator];
    // Swap the type of value if the current operator dictates doing so.
    if (isset($operator_definition['webform_views_element_type']) && $operator_definition['webform_views_element_type'] != self::ELEMENT_TYPE) {
      $element['#type'] = $operator_definition['webform_views_element_type'];
    }

    // Wrap the value with a container that will be used for AJAX.
    $html_id = Html::getUniqueId($this->pluginId);
    $form_state->set(['webform_views', 'filter_value_form_wrapper_id'], $html_id);

    $theme_wrappers = $this->getFormElementProperty($element, '#theme_wrappers', []);
    $theme_wrappers['container'] = ['#attributes' => ['id' => $html_id]];
    $element['#theme_wrappers'] = $theme_wrappers;

    $process = $this->getFormElementProperty($element, '#process', []);
    // We wanna run as the 1st process since we might change the type of the
    // element and thus the ongoing processes may become obsolete.
    array_unshift($process, [self::class, 'processValueForm']);
    $element['#process'] = $process;

    // We will need the definition of operators in the process callback and at
    // that moment we will in static context without access to methods of this
    // object. So we thoughtfully attach definition of all operators to the form
    // element itself.
    $element['#webform_views_filter']['operators'] = $this->operators();

    $form['value'] = $element;
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

    // We additionally mark each operator as whether it should use element form
    // or just a text field. For example, when you filter by email, generally
    // you want to use #type => 'email', i.e. the element form, but when you do
    // "contains" or "regex" or similar operator, you want to have just a text
    // field.
    $operator_map = [
      '=' => self::ELEMENT_TYPE,
      '!=' => self::ELEMENT_TYPE,
      'contains' => 'textfield',
      'word' => 'textfield',
      'allwords' => 'textfield',
      'starts' => 'textfield',
      'not_starts' => 'textfield',
      'ends' => 'textfield',
      'not_ends' => 'textfield',
      'not' => 'textfield',
      'shorterthan' => 'number',
      'longerthan' => 'number',
      'regular_expression' => 'textfield',
    ];

    foreach ($operators as $k => $v) {
      if (isset($operator_map[$k])) {
        $operators[$k]['webform_views_element_type'] = $operator_map[$k];
      }
    }

    return $operators;
  }

  /**
   * Form process for ::operatorForm() method.
   */
  public static function processOperatorForm($element, FormStateInterface $form_state, $form) {
    $element['#ajax']['wrapper'] = $form_state->get(['webform_views', 'filter_value_form_wrapper_id']);
    return $element;
  }

  /**
   * Form process for ::valueForm() method.
   */
  public static function processValueForm($element, FormStateInterface $form_state, $form) {
    // Store the location of value form within the whole $form so ajax callback
    // has better time finding it.
    $form_state->set(['webform_views', 'filter_value_form_array_parents'], $element['#array_parents']);

    return $element;
  }

  /**
   * Ajax callback for operator form element.
   */
  public static function ajaxValueForm($form, FormStateInterface $form_state) {
    $value_form = NestedArray::getValue($form, $form_state->get(['webform_views', 'filter_value_form_array_parents']));

    // Views sets its #prefix and #suffix for CSS purposes.
    unset($value_form['#prefix'], $value_form['#suffix']);

    return $value_form;
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
