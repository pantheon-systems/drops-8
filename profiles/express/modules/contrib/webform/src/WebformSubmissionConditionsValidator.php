<?php

namespace Drupal\webform;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformElement;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Webform submission conditions (#states) validator.
 *
 * @see \Drupal\webform\Element\WebformElementStates
 * @see drupal_process_states()
 */
class WebformSubmissionConditionsValidator implements WebformSubmissionConditionsValidatorInterface {

  use StringTranslationTrait;

  /**
   * State aliases.
   *
   * @var array
   *
   * @see Drupal.states.State.aliases
   */
  protected $aliases = [
    'enabled' => '!disabled',
    'invisible' => '!visible',
    'invisible-slide' => '!visible-slide',
    'invalid' => '!valid',
    'optional' => '!required',
    'filled' => '!empty',
    'unchecked' => '!checked',
    'expanded' => '!collapsed',
    'open' => '!collapsed',
    'closed' => 'collapsed',
    'readwrite' => '!readonly',
  ];

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformSubmissionConditionsValidator object.
   *
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(WebformElementManagerInterface $element_manager) {
    $this->elementManager = $element_manager;
  }

  /****************************************************************************/
  // Build pages methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildPages(array $pages, WebformSubmissionInterface $webform_submission) {
    foreach ($pages as $page_key => $page) {
      // Check #access which can be set via form alter.
      if ($page['#access'] === FALSE) {
        unset($pages[$page_key]);
      }
      // Check #states (visible/hidden).
      if (!empty($page['#states'])) {
        $state = key($page['#states']);
        $conditions = $page['#states'][$state];
        $result = $this->validateState($state, $conditions, $webform_submission);
        if ($result !== NULL && !$result) {
          unset($pages[$page_key]);
        }
      }
    }
    return $pages;
  }

  /****************************************************************************/
  // Build form methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_state->getFormObject()->getEntity();

    // Get build/visible form elements.
    $visible_elements = &$this->getBuildElements($form);

    // Loop through visible elements with #states.
    foreach ($visible_elements as &$element) {
      $states =& WebformElementHelper::getStates($element);
      // Store original #states in #_webform_states.
      $element['#_webform_states'] = $states;
      foreach ($states as $original_state => $conditions) {
        if (!is_array($conditions)) {
          continue;
        }

        // Process state/negate.
        list($state, $negate) = $this->processState($original_state);

        // If hide/show we need to make sure that validation is not triggered.
        if (strpos($state, 'visible') === 0) {
          $element['#after_build'][] = [get_class($this), 'elementAfterBuild'];
        }

        $targets = $this->getConditionTargetsVisiblity($conditions, $visible_elements);

        // Determine if targets are visible or cross page.
        $all_targets_visible = (array_sum($targets) === count($targets));
        $has_cross_page_targets = (!$all_targets_visible && array_sum($targets));

        // Skip if evaluating conditions when all targets are visible.
        if ($all_targets_visible) {
          // Add .js-webform-states-hidden to element's that are not visible when
          // the form is rendered.
          if (strpos($state, 'visible') === 0
            && !$this->validateConditions($conditions, $webform_submission)) {
            $this->addStatesHiddenToElement($element);
          }
          continue;
        }

        // Replace hidden cross page targets with hidden inputs.
        if ($has_cross_page_targets) {
          $cross_page_targets = array_filter(
            $targets,
            function ($visible) {
              return $visible === FALSE;
            }
          );
          $states[$original_state] = $this->replaceCrossPageTargets($conditions, $webform_submission, $cross_page_targets, $form);
          continue;
        }

        $result = $this->validateConditions($conditions, $webform_submission);

        // Skip invalid conditions.
        if ($result === NULL) {
          continue;
        }

        // Negate the result.
        $result = ($negate) ? !$result : $result;

        // Apply result to element state.
        switch ($state) {
          case 'required':
            $element['#required'] = $result;
            break;

          case 'disabled':
            $element['#disabled'] = $result;
            break;

          case 'visible':
          case 'visible-slide':
            if (!$result) {
              // Visual hide the element.
              $this->addStatesHiddenToElement($element);
              // Clear the default value.
              if (!isset($element['#states_clear']) || $element['#states_clear'] === TRUE) {
                unset($element['#default_value']);
              }
            }
            break;

          case 'collapsed':
            $element['#open'] = !$result;
            break;

          case 'checked':
            $element['#default_value'] = $result;
            break;
        }

        // Remove #states state/conditions.
        unset($states[$original_state]);
      }

      // Remove #states if all states have been applied.
      if (empty($states)) {
        unset($element['#states']);
      }
    }
  }

  /**
   * Replace hidden cross page targets with hidden inputs.
   *
   * @param array $conditions
   *   An element's conditions.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $targets
   *   An array of hidden target selectors.
   * @param array $form
   *   A form.
   *
   * @return array
   *   The conditions with cross page targets replaced with hidden inputs.
   */
  public function replaceCrossPageTargets(array $conditions, WebformSubmissionInterface $webform_submission, array $targets, array &$form) {
    // Cache random cross page values.
    static $cross_page_values = [];

    $cross_page_conditions = [];
    foreach ($conditions as $index => $value) {
      if (is_int($index) && is_array($value) && WebformArrayHelper::isSequential($value)) {
        $cross_page_conditions[$index] = $this->replaceCrossPageTargets($conditions, $webform_submission, $targets, $form);
      }
      else {
        $cross_page_conditions[$index] = $value;

        if (is_string($value) && in_array($value, ['and', 'or', 'xor'])) {
          continue;
        }
        elseif (is_int($index)) {
          $selector = key($value);
          $condition = $value[$selector];
        }
        else {
          $selector = $index;
          $condition = $value;
        }

        if (!isset($targets[$selector])) {
          continue;
        }

        $condition_result = $this->validateCondition($selector, $condition, $webform_submission);
        if ($condition_result === NULL) {
          continue;
        }

        $target_trigger = $condition_result ? 'value' : '!value';
        $target_name = 'webform_states_' . Crypt::hashBase64($selector);
        $target_selector = ':input[name="' . $target_name . '"]';

        // IMPORTANT:
        // Using a random value to make sure users can't determine a hidden
        // or computed element's value/result.
        if (!isset($cross_page_values[$target_name])) {
          $cross_page_values[$target_name] = rand();
        }
        $target_value = $cross_page_values[$target_name];

        if (is_int($index)) {
          unset($cross_page_conditions[$index][$selector]);
          $cross_page_conditions[$index][$target_selector] = [$target_trigger => $target_value];
        }
        else {
          unset($cross_page_conditions[$selector]);
          $cross_page_conditions[$target_selector] = [$target_trigger => $target_value];
        }

        // Append cross page element's result as a hidden input.
        $form[$target_name] = [
          '#type' => 'hidden',
          '#value' => $target_value,
        ];
      }
    }
    return $cross_page_conditions;
  }

  /****************************************************************************/
  // Validate form methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\webform\WebformSubmissionForm::validateForm
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateFormRecursive($form, $form_state);
  }

  /**
   * Recurse through a form and validate visible elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateFormRecursive(array $form, FormStateInterface $form_state) {
    foreach ($form as $key => $element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      if (isset($element['#access']) && $element['#access'] === FALSE) {
        continue;
      }

      $this->validateFormElement($element, $form_state);

      $this->validateFormRecursive($element, $form_state);
    }
  }

  /**
   * Validate a form element.
   *
   * @param array $element
   *   A form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateFormElement(array $element, FormStateInterface $form_state) {
    $states = WebformElementHelper::getStates($element);
    if (empty($states)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_state->getFormObject()->getEntity();
    foreach ($states as $state => $conditions) {
      // Only required/optional validation is supported.
      if (!in_array($state, ['required', 'optional'])) {
        continue;
      }

      // Element must be an input to be required or optional.
      $element_plugin = $this->elementManager->getElementInstance($element);
      if (!$element_plugin->isInput($element)) {
        continue;
      }

      // Determine if the element is required.
      $is_required = $this->validateConditions($conditions, $webform_submission);
      $is_required = ($state == 'optional') ? !$is_required : $is_required;
      if (!$is_required) {
        continue;
      }

      // Determine if the element is empty (but not zero).
      if (isset($element['#webform_key'])) {
        $value = $webform_submission->getElementData($element['#webform_key']);
      }
      else {
        $value = $element['#value'];
      }

      // Perform required validation. Use element's method if available.
      $element_definition = $element_plugin->getFormElementClassDefinition();
      if (method_exists($element_definition, 'setRequiredError')) {
        $element_definition::setRequiredError($element, $form_state);
      }
      else {
        $is_empty = (empty($value) && $value !== '0');
        $is_default_input_mask = (TextBase::isDefaultInputMask($element, $value));

        // If required and empty then set required error.
        if ($is_empty || $is_default_input_mask) {
          WebformElementHelper::setRequiredError($element, $form_state);
        }
      }
    }
  }

  /****************************************************************************/
  // Submit form methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_state->getFormObject()->getEntity();

    // Get submission data.
    $data = $webform_submission->getData();

    // Recursive through the form and unset unset submission data for
    // form elements that are hidden.
    $this->submitFormRecursive($form, $webform_submission, $data);

    // Set submission data.
    $webform_submission->setData($data);
  }

  /**
   * Recursively unset submission data for form elements that are hidden.
   *
   * @param array $elements
   *   An array of form elements.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $data
   *   A webform submission's data.
   * @param bool $visible
   *   Flag that determine if the currrent form elements are visible.
   */
  protected function submitFormRecursive(array $elements, WebformSubmissionInterface $webform_submission, array &$data, $visible = TRUE) {
    foreach ($elements as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      // Skip if element's #states_clear is FALSE.
      if (isset($element['#states_clear']) && $element['#states_clear'] === FALSE) {
        continue;
      }

      if (isset($element['#_webform_access']) && $element['#_webform_access'] === FALSE) {
        continue;
      }

      // Determine if the element is visible.
      $element_visible = ($visible && $this->isElementVisible($element, $webform_submission)) ? TRUE : FALSE;

      // Set data to empty array or string for any webform element that is hidden.
      if (!$element_visible && !empty($element['#webform_key']) && isset($data[$key])) {
        $data[$key] = (is_array($data[$key])) ? [] : '';
      }

      $this->submitFormRecursive($element, $webform_submission, $data, $element_visible);
    }
  }

  /****************************************************************************/
  // Element hide/show validation methods.
  /****************************************************************************/

  /**
   * Webform element #after_build callback: Wrap #element_validate so that we suppress element validation errors.
   */
  public static function elementAfterBuild(array $element, FormStateInterface $form_state) {
    return WebformElementHelper::setElementValidate($element, [get_called_class(), 'elementValidate']);
  }

  /**
   * Webform conditional #element_validate callback: Execute #element_validate and suppress errors.
   */
  public static function elementValidate(array &$element, FormStateInterface $form_state) {
    // Element validation is trigger sequentially.
    // Triggers must be validated before dependants.
    //
    // Build webform submission with validated and processed data.
    // Webform submission must be rebuilt every time since the
    // $element and $form_state values can be changed by validation callbacks.
    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    $complete_form = &$form_state->getCompleteForm();
    $webform_submission = $form_object->buildEntity($complete_form, $form_state);

    /** @var \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator */
    $conditions_validator = \Drupal::service('webform_submission.conditions_validator');
    if ($conditions_validator->isElementVisible($element, $webform_submission)) {
      WebformElementHelper::triggerElementValidate($element, $form_state);
    }
    else {
      WebformElementHelper::suppressElementValidate($element, $form_state);
    }
  }

  /****************************************************************************/
  // Element state methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isElementVisible(array $element, WebformSubmissionInterface $webform_submission) {
    $states = WebformElementHelper::getStates($element);

    $visible = TRUE;
    foreach ($states as $state => $conditions) {
      if (!is_array($conditions)) {
        continue;
      }

      // Process state/negate.
      list($state, $negate) = $this->processState($state);

      $result = $this->validateConditions($conditions, $webform_submission);
      // Skip invalid conditions.
      if ($result === NULL) {
        continue;
      }

      // Negate the result.
      $result = ($negate) ? !$result : $result;

      // Apply result to element state.
      if (strpos($state, 'visible') === 0 && $result === FALSE) {
        $visible = FALSE;
      }
    }

    return $visible;
  }

  /**
   * {@inheritdoc}
   */
  public function isElementEnabled(array $element, WebformSubmissionInterface $webform_submission) {
    $states = WebformElementHelper::getStates($element);

    $enabled = TRUE;
    foreach ($states as $state => $conditions) {
      if (!is_array($conditions)) {
        continue;
      }

      // Process state/negate.
      list($state, $negate) = $this->processState($state);

      $result = $this->validateConditions($conditions, $webform_submission);
      // Skip invalid conditions.
      if ($result === NULL) {
        continue;
      }

      // Negate the result.
      $result = ($negate) ? !$result : $result;

      // Apply result to element state.
      if ($state === 'disabled' && $result === TRUE) {
        $enabled = FALSE;
      }
    }

    return $enabled;
  }

  /****************************************************************************/
  // Validate state methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function validateState($state, array $conditions, WebformSubmissionInterface $webform_submission) {
    // Process state/negate.
    list($state, $negate) = $this->processState($state);

    // Validation conditions.
    $result = $this->validateConditions($conditions, $webform_submission);

    // Skip invalid conditions.
    if ($result === NULL) {
      return NULL;
    }

    // Negate the result.
    return ($negate) ? !$result : $result;
  }

  /****************************************************************************/
  // Validate condition methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function validateConditions(array $conditions, WebformSubmissionInterface $webform_submission) {
    // Determine condition logic.
    // @see Drupal.states.Dependent.verifyConstraints
    if (WebformArrayHelper::isSequential($conditions)) {
      $condition_logic = (in_array('xor', $conditions)) ? 'xor' : 'or';
    }
    else {
      $condition_logic = 'and';
    }

    $condition_results = [];

    foreach ($conditions as $index => $value) {
      // Skip and, or, and xor.
      if (is_string($value) && in_array($value, ['and', 'or', 'xor'])) {
        continue;
      }

      if (is_int($index) && is_array($value)) {
        // Validate nested conditions.
        // NOTE: Nested conditions is not supported via the UI.
        $nested_result = $this->validateConditions($value, $webform_submission);
        if ($nested_result === NULL) {
          return NULL;
        }
        $condition_results[] = $nested_result;
      }
      else {
        if (is_int($index)) {
          $selector = key($value);
          $condition = $value[$selector];
        }
        else {
          $selector = $index;
          $condition = $value;
        }

        $condition_result = $this->validateCondition($selector, $condition, $webform_submission);
        if ($condition_result === NULL) {
          return NULL;
        }

        $condition_results[] = $this->validateCondition($selector, $condition, $webform_submission);
      }
    }

    // Process condition logic. (XOR, AND, or OR)
    $conditions_sum = array_sum($condition_results);
    $conditions_total = count($condition_results);
    switch ($condition_logic) {
      case 'xor':
        return ($conditions_sum === 1);

      case 'or':
        return (boolean) $conditions_sum;

      case 'and':
        return ($conditions_sum === $conditions_total);

      default:
        // Never called.
        return NULL;
    }
  }

  /**
   * Validate #state condition.
   *
   * @param string $selector
   *   The #state condition selector.
   * @param array $condition
   *   A condition.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool|null
   *   TRUE if the condition validates. NULL if condition can't be processed.
   *   NULL is returned when there is invalid selector and missing element
   *   in the conditions.
   */
  protected function validateCondition($selector, array $condition, WebformSubmissionInterface $webform_submission) {
    // Ignore invalid selector and return NULL.
    $input_name = static::getSelectorInputName($selector);
    if (!$input_name) {
      return NULL;
    }

    $element_key = static::getInputNameAsArray($input_name, 0);
    $element = $webform_submission->getWebform()->getElement($element_key);

    // If no element is found try checking file uploads which use
    // :input[name="files[ELEMENT_KEY].
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::getElementSelectorOptions
    if (!$element && strpos($selector, ':input[name="files[') === 0) {
      $element_key = static::getInputNameAsArray($input_name, 1);
      $element = $webform_submission->getWebform()->getElement($element_key);
    }

    // Ignore missing dependee element and return NULL.
    if (!$element) {
      return NULL;
    }

    // Issue #1149078: States API doesn't work with multiple select fields.
    // @see https://www.drupal.org/project/drupal/issues/1149078
    if (WebformArrayHelper::isSequential($condition)) {
      $sub_condition_results = [];
      foreach ($condition as $sub_condition) {
        $sub_condition_results[] = $this->checkCondition($element, $selector, $sub_condition, $webform_submission);
      }
      // Evaluate sub-conditions using the 'OR' operator.
      return (boolean) array_sum($sub_condition_results);
    }
    else {
      return $this->checkCondition($element, $selector, $condition, $webform_submission);
    }
  }

  /**
   * Check a condition.
   *
   * @param array $element
   *   An element.
   * @param string $selector
   *   The element's selector.
   * @param array $condition
   *   The condition.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool|null
   *   TRUE if condition is validated. NULL if the condition can't be evaluated.
   */
  protected function checkCondition(array $element, $selector, array $condition, WebformSubmissionInterface $webform_submission) {
    $trigger_state = key($condition);
    $trigger_value = $condition[$trigger_state];

    $element_plugin = $this->elementManager->getElementInstance($element);

    // Ignored conditions for generic webform elements.
    if ($element_plugin instanceof WebformElement) {
      return TRUE;
    }

    $element_value = $element_plugin->getElementSelectorInputValue($selector, $trigger_state, $element, $webform_submission);

    // Process trigger sub state used for custom #states API validation.
    // @see Drupal.behaviors.webformStatesComparisions
    // @see http://drupalsun.com/julia-evans/2012/03/09/extending-form-api-states-regular-expressions
    if ($trigger_state == 'value' && is_array($trigger_value)) {
      $trigger_substate = key($trigger_value);
      if (in_array($trigger_substate, ['pattern', '!pattern', 'less', 'greater', 'between'])) {
        $trigger_state = $trigger_substate;
        $trigger_value = reset($trigger_value);
      }
    }

    // Process trigger state/negate.
    list($trigger_state, $trigger_negate) = $this->processState($trigger_state);

    // Process triggers (aka remote conditions).
    // @see \Drupal\webform\Element\WebformElementStates::processWebformStates
    switch ($trigger_state) {
      case 'empty':
        $empty = (empty($element_value) && $element_value !== '0');
        $result = ($empty === (boolean) $trigger_value);
        break;

      case 'checked':
        $result = ((boolean) $element_value === (boolean) $trigger_value);
        break;

      case 'value':
        if ($element_plugin->hasMultipleValues($element)) {
          $trigger_values = (array) $trigger_value;
          $element_values = (array) $element_value;
          $result = (array_intersect($trigger_values, $element_values)) ? TRUE : FALSE;
        }
        else {
          $result = ((string) $element_value === (string) $trigger_value);
        }
        break;

      case 'pattern':
        // PHP: Convert JavaScript-escaped Unicode characters to PCRE
        // escape sequence format.
        // @see \Drupal\webform\Plugin\WebformElement\TextBase::validatePattern
        $pcre_pattern = preg_replace('/\\\\u([a-fA-F0-9]{4})/', '\\x{\\1}', $trigger_value);
        $result = preg_match('{' . $pcre_pattern . '}u', $element_value);
        break;

      case 'less':
        $result = ($element_value !== '' && floatval($trigger_value) > floatval($element_value));
        break;

      case 'greater':
        $result = ($element_value !== '' && floatval($trigger_value) < floatval($element_value));
        break;

      case 'between':
        $result = FALSE;
        if ($element_value !== '') {
          $greater = NULL;
          $less = NULL;
          if (strpos($trigger_value, ':') === FALSE) {
            $greater = $trigger_value;
          }
          else {
            list($greater, $less) = explode(':', $trigger_value);
          }
          $is_greater_than = ($greater === NULL || $greater === '' || floatval($element_value) >= floatval($greater));
          $is_less_than = ($less === NULL || $less === '' || floatval($element_value) <= floatval($less));
          $result = ($is_greater_than && $is_less_than);
        }
        break;

      default:
        return NULL;
    }

    return ($trigger_negate) ? !$result : $result;
  }

  /****************************************************************************/
  // State methods.
  /****************************************************************************/

  /**
   * Process state by mapping aliases and negation.
   *
   * @param string $state
   *   A state.
   *
   * @return array
   *   An array containing state and negate
   */
  protected function processState($state) {
    // Set aliases.
    if (isset($this->aliases[$state])) {
      $state = $this->aliases[$state];
    }

    // Set negate.
    $negate = FALSE;
    if (strpos($state, '!') === 0) {
      $negate = TRUE;
      $state = ltrim($state, '!');
    }

    return [$state, $negate];
  }

  /****************************************************************************/
  // Element methods.
  /****************************************************************************/

  /**
   * Build and get visible elements from a form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   Visible elements.
   */
  protected function &getBuildElements(array &$form) {
    $elements = [];
    $this->getBuildElementsRecusive($elements, $form);
    return $elements;
  }

  /**
   * Recurse through a form, review #states/#required, and get visible elements.
   *
   * @param array $elements
   *   Visible elements with #states property.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $parent_states
   *   An associative array containing 'required'/'optional' states from parent
   *   container to be set on the element.
   */
  protected function getBuildElementsRecusive(array &$elements, array &$form, array $parent_states = []) {
    foreach ($form as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      // Pass parent states to sub-element states.
      $subelement_states = $parent_states;

      if (!empty($element['#states']) || !empty($parent_states)) {
        if (!empty($element['#required'])) {
          // If element has #states and is #required there may be a conflict where
          // visibly hidden elements are required. The solution is to convert
          // #required into corresponding 'required/optional' states based on
          // 'visible/invisible' states.
          if (!isset($element['#states']['required']) && !isset($element['#states']['optional'])) {
            if (isset($element['#states']['visible'])) {
              $element['#states']['required'] = $element['#states']['visible'];
            }
            elseif (isset($element['#states']['visible-slide'])) {
              $element['#states']['required'] = $element['#states']['visible-slide'];
            }
            elseif (isset($element['#states']['invisible'])) {
              $element['#states']['optional'] = $element['#states']['invisible'];
            }
            elseif (isset($element['#states']['invisible-slide'])) {
              $element['#states']['optional'] = $element['#states']['invisible-slide'];
            }
            elseif ($parent_states) {
              $element += ['#states' => []];
              $element['#states'] += $parent_states;
            }
          }

          if (isset($element['#states']['optional']) || isset($element['#states']['required'])) {
            unset($element['#required']);
          }

          // Store a reference to the original #required value so that
          // form alter hooks know if the element's required/optional #states
          // are based the 'visible/invisible' states or the parent states.
          if (!isset($element['#required'])) {
            $element['#_required'] = TRUE;
          }
        }

        // If this container element has a visibility state, make its
        // sub-elements required/optional based on this state.
        if (isset($element['#states']['visible'])) {
          $subelement_states = ['required' => $element['#states']['visible']];
        }
        elseif (isset($element['#states']['visible-slide'])) {
          $subelement_states = ['required' => $element['#states']['visible-slide']];
        }
        elseif (isset($element['#states']['invisible'])) {
          $subelement_states = ['optional' => $element['#states']['invisible']];
        }
        elseif (isset($element['#states']['invisible-slide'])) {
          $subelement_states = ['optional' => $element['#states']['invisible-slide']];
        }
      }

      // Store original #access in #_webform_access for all elements.
      // @see \Drupal\webform\WebformSubmissionConditionsValidator::submitFormRecursive
      if (isset($element['#access'])) {
        $element['#_webform_access'] = $element['#access'];
      }

      // Skip if element is not visible.
      if (isset($element['#access']) && $element['#access'] === FALSE) {
        continue;
      }

      $elements[$key] = &$element;

      $this->getBuildElementsRecusive($elements, $element, $subelement_states);

      $element_plugin = $this->elementManager->getElementInstance($element);
      if ($element_plugin instanceof WebformCompositeBase && !$element_plugin->hasMultipleValues($element)) {
        // Handle composite with single item.
        if ($subelement_states) {
          $composite_elements = $element_plugin->getCompositeElements();
          foreach ($composite_elements as $composite_key => $composite_element) {
            // Skip if #access is set to FALSE.
            if (isset($element['#' . $composite_key . '__access']) && $element['#' . $composite_key . '__access'] === FALSE) {
              continue;
            }
            // Move #composite__required to #composite___required which triggers
            // conditional #_required handling.
            if (!empty($element['#' . $composite_key . '__required'])) {
              unset($element['#' . $composite_key . '__required']);
              $element['#' . $composite_key . '___required'] = TRUE;
              $element['#' . $composite_key . '__states'] = $subelement_states;
            }
          }
        }
      }
      elseif (isset($element['#element']) && isset($element['#webform_composite_elements'])) {
        // Handle composite with multiple items and custom composite elements.
        //
        // For $element['#elements'] ...
        // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::prepareMultipleWrapper
        //
        // For $element['#webform_composite_elements'] ...
        // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::initializeCompositeElements
        // @see \Drupal\webform_composite\Plugin\WebformElement\WebformComposite::initializeCompositeElements
        //
        // Recurse through a composite's sub elements.
        $this->getBuildElementsRecusive($elements, $element['#element'], $subelement_states);
      }
    }
  }

  /**
   * Get the visibility state for all of conditions targets.
   *
   * @param array $conditions
   *   An associative array containing conditions.
   * @param array $elements
   *   An associative array of visible elements.
   *
   * @return array
   *   An associative array keyed by target selectors with a boolean state.
   */
  protected function getConditionTargetsVisiblity(array $conditions, array $elements) {
    $targets = [];
    $this->getConditionTargetsVisiblityRecursive($conditions, $targets);
    foreach ($targets as $selector) {
      // Ignore invalid selector and return FALSE.
      $input_name = static::getSelectorInputName($selector);
      if (!$input_name) {
        $targets[$selector] = FALSE;
        continue;
      }

      // Check if the input's element is visible.
      $element_key = static::getInputNameAsArray($input_name, 0);
      if (!isset($elements[$element_key])) {
        $targets[$selector] = FALSE;
        continue;
      }

      $targets[$selector] = TRUE;
    }
    return $targets;
  }

  /**
   * Recursively collect a conditions targets.
   *
   * @param array $conditions
   *   An associative array containing conditions.
   * @param array $targets
   *   An associative array keyed by target selectors with a boolean state.
   */
  protected function getConditionTargetsVisiblityRecursive(array $conditions, array &$targets = []) {
    foreach ($conditions as $index => $value) {
      if (is_int($index) && is_array($value) && WebformArrayHelper::isSequential($value)) {
        // Recurse downward and get nested target element.
        // NOTE: Nested conditions is not supported via the UI.
        $this->getConditionTargetsVisiblityRecursive($value, $targets);
      }
      elseif (is_string($value) && in_array($value, ['and', 'or', 'xor'])) {
        // Skip AND, OR, or XOR operators.
        continue;
      }
      elseif (is_int($index)) {
        $selector = key($value);
      }
      else {
        $selector = $index;
      }

      $targets[$selector] = $selector;
    }
  }

  /**
   * Add .js-webform-states-hidden to an element.
   *
   * @param array $element
   *   An element.
   */
  protected function addStatesHiddenToElement(array &$element) {
    $element_plugin = $this->elementManager->getElementInstance($element);
    $attributes_property = ($element_plugin->hasWrapper($element) || $element_plugin->getPluginDefinition()['states_wrapper']) ? '#wrapper_attributes' : '#attributes';
    $element += [$attributes_property => []];
    $element[$attributes_property] += ['class' => []];
    $element[$attributes_property]['class'][] = 'js-webform-states-hidden';
  }

  /****************************************************************************/
  // Static input and selector methods.
  // @see \Drupal\webform\Plugin\WebformElementBase::getElementSelectorInputValue
  /****************************************************************************/

  /**
   * Get input name from CSS :input[name="*"] selector.
   *
   * @param string $selector
   *   A CSS :input[name="*"] selector.
   *
   * @return string|null
   *   The input name or NULL if selector can not be parsed
   */
  public static function getSelectorInputName($selector) {
    return (preg_match('/\:input\[name="([^"]+)"\]/', $selector, $match)) ? $match[1] : NULL;
  }

  /**
   * Parse input name which can container nested elements defined using #tree.
   *
   * Converts 'input[subkey]' into ['input', 'subkey'].
   *
   * @param string $name
   *   The input name.
   * @param int $index
   *   A specific input name index to be returned.
   *
   * @return array|string
   *   An array containing the input name and keys or specific input name.
   *
   * @see http://php.net/manual/en/faq.html.php#faq.html.arrays
   */
  public static function getInputNameAsArray($name, $index = NULL) {
    $name = str_replace(['][', '[', ']'], ['|', '|', ''], $name);
    $array = explode('|', $name);
    if ($index !== NULL) {
      return isset($array[$index]) ? $array[$index] : NULL;
    }
    else {
      return $array;
    }
  }

}
