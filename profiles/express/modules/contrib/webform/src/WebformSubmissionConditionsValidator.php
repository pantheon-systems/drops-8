<?php

namespace Drupal\webform;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;

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
    'invalid' => '!valid',
    'optional' => '!required',
    'filled' => '!empty',
    'unchecked' => '!checked',
    'expanded' => '!collapsed',
    'open' => '!collapsed',
    'closed' => 'collapsed',
    // Below states are never used by the #states API.
    // 'untouched' => '!touched',
    // 'irrelevant' => '!relevant',
    // 'readwrite' => '!readonly',
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
  // Webform submission form methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\webform\WebformSubmissionForm::buildForm
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_state->getFormObject()->getEntity();

    // Get visible form elements.
    $elements = &$this->getVisibleElements($form);

    // Loop through visible elements with #states.
    foreach ($elements as &$element) {
      $states = static::getElementStates($element);
      foreach ($states as $state => $conditions) {
        if (!is_array($conditions)) {
          continue;
        }

        if ($this->isConditionsTargetsVisible($conditions, $elements)) {
          list($state, $negate) = $this->processState($state);
          if ($state == 'required') {
            unset($element['#required']);
          }
          continue;
        }

        $result = $this->validateConditions($conditions, $webform_submission);
        // Skip invalid conditions.
        if ($result === NULL) {
          continue;
        }

        // Remove #states state/conditions.
        unset($states[$state]);

        // Process state/negate.
        list($state, $negate) = $this->processState($state);

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
            $element['#access'] = $result;
            break;

          case 'collapsed':
            $element['#open'] = !$result;
            break;

          case 'checked':
            $element['#default_value'] = $result;
            break;
        }
      }

      // Remove #states if all states have been applied.
      if (empty($states)) {
        unset($element['#states']);
      }
    }
  }

  /****************************************************************************/
  // Validation methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\webform\WebformSubmissionForm::validateForm
   */
  public function validateForm(array &$form, FormStateInterface $form_state)  {
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
      if (Element::property($key) || !is_array($element)) {
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
    $states = static::getElementStates($element);
    if (empty($states)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_state->getFormObject()->getEntity();
    foreach ($states as $state => $conditions) {
      if (!in_array($state, ['required', 'optional'])) {
        continue;
      }

      $is_required = $this->validateConditions($conditions, $webform_submission);
      $is_required = ($state == 'optional') ? !$is_required : $is_required;

      if (isset($element['#webform_key'])) {
        $value = $webform_submission->getElementData($element['#webform_key']);
      }
      else {
        $value = $element['#value'];
      }

      if ($is_required && ($value === '' || $value === NULL)) {
        $this->setRequiredError($element, $form_state);
      }
    }
  }

  /****************************************************************************/
  // Condition methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function validateConditions(array $conditions, WebformSubmissionInterface $webform_submission) {
    $condition_logic = 'and';
    $condition_results = [];
    foreach ($conditions as $index => $value) {
      if (is_string($value) && in_array($value, ['and', 'or', 'xor'])) {
        $condition_logic = $value;
        // If OR conditional logic operatator, check current condition
        // results.
        if ($condition_logic === 'or' && array_sum($condition_results)) {
          return TRUE;
        }
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

      // Ignore invalid selector and return NULL.
      $input_name = static::getSelectorInputName($selector);
      if (!$input_name) {
        return NULL;
      }

      $element_key = static::getInputNameAsArray($input_name, 0);

      // Ignore missing dependee element and return NULL.
      $element = $webform_submission->getWebform()->getElement($element_key);
      if (!$element) {
        return NULL;
      }

      $trigger_state = key($condition);
      $trigger_value = $condition[$trigger_state];

      $element_plugin = $this->elementManager->getElementInstance($element);
      $element_value = $element_plugin->getElementSelectorInputValue($selector, $trigger_state, $element, $webform_submission);

      // Process trigger state/negate.
      list($trigger_state, $trigger_negate) = $this->processState($trigger_state);

      // Process triggers (aka remote conditions).
      // @see \Drupal\webform\Element\WebformElementStates::processWebformStates
      switch ($trigger_state) {
        case 'empty':
          $result = (empty($element_value) === (boolean) $trigger_value);
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

        default:
          return NULL;
      }

      $condition_results[$selector] = ($trigger_negate) ? !$result : $result;
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

  /****************************************************************************/
  // State methods.
  /****************************************************************************/

  /**
   * Process state by mapping aliases and negation.
   *
   * @param string $state
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
    if ($state[0] === '!') {
      $negate = TRUE;
      $state = ltrim($state, '!');
    }

    return [$state, $negate];
  }

  /****************************************************************************/
  // Validation methods.
  /****************************************************************************/

  /**
   * Set required validation message for an element.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setRequiredError(array $element, FormStateInterface $form_state) {
    if (isset($element['#required_error'])) {
      $form_state->setError($element, $element['#required_error']);
    }
    elseif (isset($element['#title'])) {
      $form_state->setError($element, $this->t('@name field is required.', ['@name' => $element['#title']]));
    }
    else {
      $form_state->setError($element);
    }
  }

  /****************************************************************************/
  // Element methods.
  /****************************************************************************/

  /**
   * Get visible elements with from a form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   Visible elements.
   */
  protected function &getVisibleElements(array &$form) {
    $elements = [];
    $this->getVisibleElementsRecusive($elements, $form);
    return $elements;
  }

  /**
   * Recurse through a form and get visible elements.
   *
   * @param array $elements
   *   Visible elements with #states property
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function getVisibleElementsRecusive(array &$elements, array &$form) {
    foreach ($form as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      if (isset($element['#access']) && $element['#access'] === FALSE) {
        continue;
      }

      $elements[$key] = &$element;

      $this->getVisibleElementsRecusive($elements, $element);
    }
  }

  /**
   * Determine if #states conditions targets are visible.
   *
   * @param array $conditions
   *   An associative array containing conditions.
   * @param array $elements
   *   An associative array of visible elements.
   *
   * @return bool
   *   TRUE if ALL #states conditions targets are visible.
   */
  protected function isConditionsTargetsVisible(array $conditions, array $elements) {
    foreach ($conditions as $index => $value) {
      if (is_string($value) && in_array($value, ['and', 'or', 'xor'])) {
        continue;
      }
      elseif (is_int($index)) {
        $selector = key($value);
      }
      else {
        $selector = $index;
      }

      // Ignore invalid selector and return FALSE.
      $input_name = static::getSelectorInputName($selector);
      if (!$input_name) {
        return FALSE;
      }

      $element_key = static::getInputNameAsArray($input_name, 0);
      return isset($elements[$element_key]) ? TRUE : FALSE;
    }
  }

  /****************************************************************************/
  // Static input and selector methods.
  // @see \Drupal\webform\Plugin\WebformElementBase::getElementSelectorInputValue
  /****************************************************************************/

  /**
   * Get an element's states API array.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   an element's states API array or an empty array.
   */
  public static function getElementStates(array $element) {
    if (isset($element['#states'])) {
      return $element['#states'];
    }
    // @see \Drupal\webform\Utility\WebformElementHelper::fixStatesWrapper
    if (isset($element['#_webform_states'])) {
      return $element['#_webform_states'];
    }

    return [];
  }

  /**
   * Get input name from CSS :input[name="*"] selector.
   *
   * @param $selector
   *   A CSS :input[name="*"] selector.
   *
   * @return string|NULL
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
  public static function getInputNameAsArray($name, $index = NULL){
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
