<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Helper class webform element methods.
 */
class WebformElementHelper {

  /**
   * Ignored element properties.
   *
   * @var array
   */
  public static $ignoredProperties = [
    // Properties that will allow code injection.
    '#allowed_tags' => '#allowed_tags',
      // Properties that will break webform data handling.
    '#tree' => '#tree',
    '#array_parents' => '#array_parents',
    '#parents' => '#parents',
    // Properties that will cause unpredictable rendering.
    '#weight' => '#weight',
    // Callbacks are blocked to prevent unwanted code executions.
    '#access_callback' => '#access_callback',
    '#ajax' => '#ajax',
    '#after_build' => '#after_build',
    '#element_validate' => '#element_validate',
    '#lazy_builder' => '#lazy_builder',
    '#post_render' => '#post_render',
    '#pre_render' => '#pre_render',
    '#process' => '#process',
    '#submit' => '#submit',
    '#validate' => '#validate',
    '#value_callback' => '#value_callback',
    // Element specific callbacks.
    '#file_value_callbacks' => '#file_value_callbacks',
    '#date_date_callbacks' => '#date_date_callbacks',
    '#date_time_callbacks' => '#date_time_callbacks',
    '#captcha_validate' => '#captcha_validate',
  ];

  /**
   * Regular expression used to determine if sub-element property should be ignored.
   *
   * @var string
   */
  protected static $ignoredSubPropertiesRegExp;

  /**
   * Determine if an element and its key is a renderable array.
   *
   * @param array|mixed $element
   *   An element.
   * @param string $key
   *   The element key.
   *
   * @return bool
   *   TRUE if an element and its key is a renderable array.
   */
  public static function isElement($element, $key) {
    return (Element::child($key) && is_array($element));
  }

  /**
   * Determine if an element has children.
   *
   * @param array|mixed $element
   *   An element.
   *
   * @return bool
   *   TRUE if an element has children.
   *
   * @see \Drupal\Core\Render\Element::children
   */
  public static function hasChildren($element) {
    foreach ($element as $key => $value) {
      if ($key === '' || $key[0] !== '#') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determine if an element is a webform element and should be enhanced.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if an element is a webform element.
   */
  public static function isWebformElement(array $element) {
    if (isset($element['#webform_key']) || isset($element['#webform_element'])) {
      return TRUE;
    }
    elseif (\Drupal::service('webform.request')->isWebformAdminRoute()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Determine if a webform element is a specified #type.
   *
   * @param array $element
   *   A webform element.
   * @param string|array $type
   *   An element type.
   *
   * @return bool
   *   TRUE if a webform element is a specified #type.
   */
  public static function isType(array $element, $type) {
    if (!isset($element['#type'])) {
      return FALSE;
    }

    if (is_array($type)) {
      return in_array($element['#type'], $type);
    }
    else {
      return ($element['#type'] === $type);
    }
  }

  /**
   * Get a webform element's admin title.
   *
   * @param array $element
   *   A webform element.
   *
   * @return string
   *   A webform element's admin title.
   */
  public static function getAdminTitle(array $element) {
    if (!empty($element['#admin_title'])) {
      return $element['#admin_title'];
    }
    elseif (!empty($element['#title'])) {
      return $element['#title'];
    }
    elseif (!empty($element['#webform_key'])) {
      return $element['#webform_key'];
    }
    else {
      return '';
    }
  }

  /**
   * Determine if a webform element's title is displayed.
   *
   * @param array $element
   *   A webform element.
   *
   * @return bool
   *   TRUE if a webform element's title is displayed.
   */
  public static function isTitleDisplayed(array $element) {
    return (!empty($element['#title']) && (empty($element['#title_display']) || !in_array($element['#title_display'], ['invisible', 'attribute']))) ? TRUE : FALSE;
  }

  /**
   * Determine if element or sub-element has properties.
   *
   * @param array $element
   *   An element.
   * @param array $properties
   *   Element properties.
   *
   * @return bool
   *   TRUE if element or sub-element has any property.
   */
  public static function hasProperties(array $element, array $properties) {
    foreach ($element as $key => $value) {
      // Recurse through sub-elements.
      if (static::isElement($value, $key)) {
        if (static::hasProperties($value, $properties)) {
          return TRUE;
        }
      }
      // Return TRUE if property exists and property value is NULL or equal.
      elseif (array_key_exists($key, $properties) && ($properties[$key] === NULL || $properties[$key] === $value)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determine if element or sub-element has property and value.
   *
   * @param array $elements
   *   An array of elements.
   * @param string $property
   *   An element property.
   * @param mixed|null $value
   *   An element value.
   *
   * @return bool
   *   TRUE if element or sub-element has property and value.
   */
  public static function hasProperty(array $elements, $property, $value = NULL) {
    return static::hasProperties($elements, [$property => $value]);
  }

  /**
   * Get an associative array containing a render element's properties.
   *
   * @param array $element
   *   A render element.
   *
   * @return array
   *   An associative array containing a render element's properties.
   */
  public static function getProperties(array $element) {
    $properties = [];
    foreach ($element as $key => $value) {
      if (Element::property($key)) {
        $properties[$key] = $value;
      }
    }
    return $properties;
  }

  /**
   * Remove all properties from a render element.
   *
   * @param array $element
   *   A render element.
   *
   * @return array
   *   A render element with no properties.
   */
  public static function removeProperties(array $element) {
    foreach ($element as $key => $value) {
      if (Element::property($key)) {
        unset($element[$key]);
      }
    }
    return $element;
  }

  /**
   * Set a property on all elements and sub-elements.
   *
   * @param array $element
   *   A render element.
   * @param string $property_key
   *   The property key.
   * @param mixed $property_value
   *   The property value.
   */
  public static function setPropertyRecursive(array &$element, $property_key, $property_value) {
    $element[$property_key] = $property_value;
    foreach (Element::children($element) as $key) {
      self::setPropertyRecursive($element[$key], $property_key, $property_value);
    }
  }

  /**
   * Process a form element and apply webform element specific enhancements.
   *
   * This method allows any form API element to be enhanced using webform
   * specific features include custom validation, external libraries,
   * accessibility improvements, etc…
   *
   * @param array $element
   *   An associative array containing an element with a #type property.
   *
   * @return array
   *   The processed form element with webform element specific enhancements.
   */
  public static function process(array &$element) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    return $element_manager->processElement($element);
  }

  /**
   * Fix webform element #states handling.
   *
   * @param array $element
   *   A webform element that is missing the 'data-drupal-states' attribute.
   */
  public static function fixStatesWrapper(array &$element) {
    if (empty($element['#states'])) {
      return;
    }

    $attributes = [];

    // Set .js-form-wrapper which is targeted by states.js hide/show logic.
    $attributes['class'][] = 'js-form-wrapper';

    // Add .js-webform-states-hidden to hide elements when they are being rendered.
    $attributes_properties = ['#wrapper_attributes', '#attributes'];
    foreach ($attributes_properties as $attributes_property) {
      if (isset($element[$attributes_property]) && isset($element[$attributes_property]['class'])) {
        $index = array_search('js-webform-states-hidden', $element[$attributes_property]['class']);
        if ($index !== FALSE) {
          unset($element[$attributes_property]['class'][$index]);
          $attributes['class'][] = 'js-webform-states-hidden';
          break;
        }
      }
    }

    $attributes['data-drupal-states'] = Json::encode($element['#states']);

    $element += ['#prefix' => '', '#suffix' => ''];

    // ISSUE: JSON is being corrupted when the prefix is rendered.
    // $element['#prefix'] = '<div ' . new Attribute($attributes) . '>' . $element['#prefix'];
    // WORKAROUND: Safely set filtered #prefix to FormattableMarkup.
    $allowed_tags = isset($element['#allowed_tags']) ? $element['#allowed_tags'] : Xss::getHtmlTagList();
    $element['#prefix'] = Markup::create('<div' . new Attribute($attributes) . '>' . Xss::filter($element['#prefix'], $allowed_tags));
    $element['#suffix'] = $element['#suffix'] . '</div>';

    // Attach library.
    $element['#attached']['library'][] = 'core/drupal.states';

    // Copy #states to #_webform_states property which can be used by the
    // WebformSubmissionConditionsValidator.
    // @see \Drupal\webform\WebformSubmissionConditionsValidator
    $element['#_webform_states'] = $element['#states'];

    // Remove #states property to prevent nesting.
    unset($element['#states']);
  }

  /**
   * Get ignored properties from a webform element.
   *
   * @param array $element
   *   A webform element.
   *
   * @return array
   *   An array of ignored properties.
   */
  public static function getIgnoredProperties(array $element) {
    $ignored_properties = [];
    foreach ($element as $key => $value) {
      if (Element::property($key)) {
        if (self::isIgnoredProperty($key)) {
          // Computed elements use #ajax as boolean and should not be ignored.
          // @see \Drupal\webform\Element\WebformComputedBase
          $is_ajax_computed = ($key === '#ajax' && is_bool($value));
          if (!$is_ajax_computed) {
            $ignored_properties[$key] = $key;
          }
        }
        elseif ($key == '#element' && is_array($value) && isset($element['#type']) && $element['#type'] === 'webform_composite') {
          foreach ($value as $composite_value) {

            // Multiple sub composite elements are not supported.
            if (isset($composite_value['#multiple'])) {
              $ignored_properties['#multiple'] = t('Custom composite sub elements do not support elements with multiple values.');
            }

            // Check that sub composite element type is supported.
            if (isset($composite_value['#type']) && !WebformCompositeBase::isSupportedElementType($composite_value['#type'])) {
              $composite_type = $composite_value['#type'];
              $ignored_properties["composite.$composite_type"] = t('Custom composite elements do not support the %type element.', ['%type' => $composite_type]);
            }

            $ignored_properties += self::getIgnoredProperties($composite_value);
          }
        }
      }
      elseif (is_array($value)) {
        $ignored_properties += self::getIgnoredProperties($value);
      }
    }
    return $ignored_properties;
  }

  /**
   * Remove ignored properties from an element.
   *
   * @param array $element
   *   A webform element.
   *
   * @return array
   *   A webform element with ignored properties removed.
   */
  public static function removeIgnoredProperties(array $element) {
    foreach ($element as $key => $value) {
      if (Element::property($key) && self::isIgnoredProperty($key)) {
        // Computed elements use #ajax as boolean and should not be ignored.
        // @see \Drupal\webform\Element\WebformComputedBase
        $is_ajax_computed = ($key === '#ajax' && is_bool($value));
        if (!$is_ajax_computed) {
          unset($element[$key]);
        }
      }
      elseif (is_array($value)) {
        $element[$key] = static::removeIgnoredProperties($value);
      }
    }
    return $element;
  }

  /**
   * Determine if an element's property should be ignored.
   *
   * - Subelement properties are delimited using __.
   * - All _validation and _callback properties are ignored.
   *
   * @param string $property
   *   A property name.
   *
   * @return bool
   *   TRUE is the property should be ignored.
   *
   * @see \Drupal\webform\Element\WebformSelectOther
   * @see \Drupal\webform\Element\WebformCompositeBase::processWebformComposite
   */
  protected static function isIgnoredProperty($property) {
    // Build cached ignored sub properties regular expression.
    if (!isset(self::$ignoredSubPropertiesRegExp)) {
      self::$ignoredSubPropertiesRegExp = '/__(' . implode('|', array_keys(WebformArrayHelper::removePrefix(self::$ignoredProperties))) . ')$/';
    }

    if (isset(self::$ignoredProperties[$property])) {
      return TRUE;
    }
    elseif (strpos($property, '__') !== FALSE && preg_match(self::$ignoredSubPropertiesRegExp, $property)) {
      return TRUE;
    }
    elseif (preg_match('/_(validates?|callbacks?)$/', $property)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Merge element properties.
   *
   * @param array $elements
   *   An array of elements.
   * @param array $source_elements
   *   An array of elements to be merged.
   */
  public static function merge(array &$elements, array $source_elements) {
    foreach ($elements as $key => &$element) {
      if (!isset($source_elements[$key])) {
        continue;
      }

      $source_element = $source_elements[$key];
      if (gettype($element) !== gettype($source_element)) {
        continue;
      }

      if (is_array($element)) {
        self::merge($element, $source_element);
      }
      elseif (is_scalar($element)) {
        $elements[$key] = $source_element;
      }
    }
  }

  /**
   * Apply translation to element.
   *
   * IMPORTANT: This basically a modified version WebformElementHelper::merge()
   * that initially only merge element properties and ignores sub-element.
   *
   * @param array $element
   *   An element.
   * @param array $translation
   *   An associative array of translated element properties.
   */
  public static function applyTranslation(array &$element, array $translation) {
    // Apply all translated properties to the element.
    // This allows default properties to be translated, which includes
    // composite element titles.
    $element += $translation;

    foreach ($element as $key => &$value) {
      // Make sure to only merge properties.
      if (!Element::property($key) || empty($translation[$key])) {
        continue;
      }

      $translation_value = $translation[$key];
      if (gettype($value) !== gettype($translation_value)) {
        continue;
      }

      if (is_array($value)) {
        self::merge($value, $translation_value);
      }
      elseif (is_scalar($value)) {
        $element[$key] = $translation_value;
      }
    }
  }

  /**
   * Flatten a nested array of elements.
   *
   * @param array $elements
   *   An array of elements.
   *
   * @return array
   *   A flattened array of elements.
   */
  public static function getFlattened(array $elements) {
    $flattened_elements = [];
    foreach ($elements as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      $flattened_elements[$key] = self::getProperties($element);
      $flattened_elements += self::getFlattened($element);
    }
    return $flattened_elements;
  }

  /**
   * Get reference to first element by name.
   *
   * @param array $elements
   *   An associative array of elements.
   * @param string $name
   *   The element's name.
   *
   * @return array|null
   *   Reference to found element.
   */
  public static function &getElement(array &$elements, $name) {
    foreach (Element::children($elements) as $element_name) {
      if ($element_name == $name) {
        return $elements[$element_name];
      }
      elseif (is_array($elements[$element_name])) {
        $child_elements =& $elements[$element_name];
        if ($element = &static::getElement($child_elements, $name)) {
          return $element;
        }
      }
    }
    $element = NULL;
    return $element;
  }

  /**
   * Convert all render(able) markup into strings.
   *
   * This method is used to prevent objects from being serialized on form's
   * that are using #ajax callbacks or rebuilds.
   *
   * @param array $elements
   *   An associative array of elements.
   */
  public static function convertRenderMarkupToStrings(array &$elements) {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        self::convertRenderMarkupToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
    }
  }

  /**
   * Convert element or property to a string.
   *
   * This method is used to prevent 'Array to string conversion' errors.
   *
   * @param array|string|MarkupInterface $element
   *   An element, render array, string, or markup.
   *
   * @return string
   *   The element or property to a string.
   */
  public static function convertToString($element) {
    if (is_array($element)) {
      return (string) \Drupal::service('renderer')->renderPlain($element);
    }
    else {
      return (string) $element;
    }
  }

  /****************************************************************************/
  // Validate callbacks to trigger or suppress validation.
  /****************************************************************************/

  /****************************************************************************/
  // ISSUE: Hidden elements still need to call #element_validate because
  // certain elements, including managed_file, checkboxes, password_confirm,
  // etc…, will also massage the submitted values via #element_validate.
  //
  // SOLUTION: Call #element_validate for all hidden elements but suppresses
  // #element_validate errors.
  /****************************************************************************/

  /**
   * Set element validate callback.
   *
   * @param array $element
   *   An element.
   * @param array $element_validate
   *   Element validate callback.
   *
   * @return array
   *   The element with validate callback.
   *
   * @see \Drupal\webform\Plugin\WebformElementBase::hiddenElementAfterBuild
   * @see \Drupal\webform\WebformSubmissionConditionsValidator::elementAfterBuild
   */
  public static function setElementValidate(array $element, array $element_validate = [WebformElementHelper::class, 'suppressElementValidate']) {
    // Element validation can only overridden once so we need to reset
    // the #eleemnt_validate callback.
    if (isset($element['#_element_validate'])) {
      $element['#element_validate'] = $element['#_element_validate'];
      unset($element['#_element_validate']);
    }

    // Wrap #element_validate so that we suppress validation error messages.
    // This only applies visible elements (#access: TRUE) with
    // #element_validate callbacks which are also conditionally hidden.
    if (!empty($element['#element_validate'])) {
      $element['#_element_validate'] = $element['#element_validate'];
      $element['#element_validate'] = [$element_validate];
    }
    return $element;
  }

  /**
   * Webform element #element_validate callback: Execute #element_validate and suppress errors.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function triggerElementValidate(array &$element, FormStateInterface $form_state) {
    // @see \Drupal\Core\Form\FormValidator::doValidateForm
    foreach ($element['#_element_validate'] as $callback) {
      $complete_form = &$form_state->getCompleteForm();
      $arguments = [&$element, &$form_state, &$complete_form];
      call_user_func_array($form_state->prepareCallback($callback), $arguments);
    }
  }

  /**
   * Webform element #element_validate callback: Execute #element_validate and suppress errors.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function suppressElementValidate(array &$element, FormStateInterface $form_state) {
    // Create a temp webform state that will capture and suppress all element
    // validation errors.
    $temp_form_state = clone $form_state;
    $temp_form_state->setLimitValidationErrors([]);

    // @see \Drupal\Core\Form\FormValidator::doValidateForm
    foreach ($element['#_element_validate'] as $callback) {
      $complete_form = &$form_state->getCompleteForm();
      $arguments = [&$element, &$temp_form_state, &$complete_form];
      call_user_func_array($form_state->prepareCallback($callback), $arguments);
    }

    // Get the temp webform state's values.
    $form_state->setValues($temp_form_state->getValues());
  }

  /**
   * Set form state required error for a specified element.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $title
   *   OPTIONAL. Required error title.
   */
  public static function setRequiredError(array $element, FormStateInterface $form_state, $title = NULL) {
    if (isset($element['#required_error'])) {
      $form_state->setError($element, $element['#required_error']);
    }
    elseif ($title) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $title]));
    }
    elseif (isset($element['#title'])) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }
    else {
      $form_state->setError($element);
    }
  }

  /**
   * Get an element's #states.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An associative array containing an element's states.
   */
  public static function &getStates(array &$element) {
    // Processed elements store the original #states in '#_webform_states'.
    // @see \Drupal\webform\WebformSubmissionConditionsValidator::buildForm
    //
    // Composite and multiple elements use a custom states wrapper
    // which will change '#states' to '#_webform_states'.
    // @see \Drupal\webform\Utility\WebformElementHelper::fixStatesWrapper
    if (!empty($element['#_webform_states'])) {
      return $element['#_webform_states'];
    }
    elseif (!empty($element['#states'])) {
      return $element['#states'];
    }
    else {
      // Return empty states variable to prevent the below notice.
      // 'Only variable references should be returned by reference'.
      $empty_states = [];
      return $empty_states;
    }
  }

  /**
   * Get required #states from an element's visible #states.
   *
   * This method allows composite and multiple to conditionally
   * require sub-elements when they are visible.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An associative array containing 'visible' and 'invisible' selectors
   *   and triggers.
   */
  public static function getRequiredFromVisibleStates(array $element) {
    $states = WebformElementHelper::getStates($element);
    $required_states = [];
    if (!empty($states['visible'])) {
      $required_states['required'] = $states['visible'];
    }
    if (!empty($states['invisible'])) {
      $required_states['optional'] = $states['invisible'];
    }
    return $required_states;
  }

  /**
   * Randomoize an associative array of element values and disable page caching.
   *
   * @param array $values
   *   An associative array of element values.
   *
   * @return array
   *   Randomized associative array of element values.
   */
  public static function randomize(array $values) {
    // Make sure randomized elements and options are never cached by the
    // current page.
    \Drupal::service('page_cache_kill_switch')->trigger();
    return WebformArrayHelper::shuffle($values);
  }

  /**
   * Form API callback. Remove unchecked options and returns an array of values.
   */
  public static function filterValues(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $values = $element['#value'];
    $values = array_filter($values, function ($value) {
      return $value !== 0;
    });
    $values = array_values($values);
    $element['#value'] = $values;
    $form_state->setValueForElement($element, $values);
  }

}
