<?php

namespace Drupal\webform_options_custom\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Markup;
use Drupal\webform\Element\WebformCompositeFormElementTrait;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Drupal\webform_options_custom\Entity\WebformOptionsCustom as WebformOptionsCustomEntity;

/**
 * Provides an element for a selecting custom options from HTML or SVG markup.
 *
 * @FormElement("webform_options_custom")
 */
class WebformOptionsCustom extends FormElement implements WebformOptionsCustomInterface {

  use WebformCompositeFormElementTrait;

  /**
   * The properties of the element.
   *
   * @var array
   */
  protected static $properties = [
    '#title',
    '#options',
    '#default_value',
    '#multiple',
    '#attributes',
    '#empty_option',
    '#empty_value',
    '#select2',
    '#chosen',
    // NOTE:
    // Choices is not supported by custom options because of <option> being
    // removed inside the <select>.
    // @see https://github.com/jshjohnson/Choices/issues/601
    '#placeholder',
    '#help_display',
    '#size',
    '#required',
    '#required_error',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformOptionsCustom'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCompositeFormElement'],
      ],
      '#options_custom' => NULL,
      '#options' => [],
      '#template' => '',
      '#value_attributes' => 'data-option-value,data-value,data-id,id',
      '#text_attributes' => 'data-option-text,data-text,data-name,name,title',
      '#fill' => TRUE,
      '#zoom' => FALSE,
      '#tooltip' => FALSE,
      '#show_select' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element += ['#default_value' => ''];
    if ($input === FALSE) {
      return [
        'value' => $element['#default_value'],
      ];
    }
    else {
      if (isset($input['select'])) {
        $input['value'] = $input['select'];
      }
      return $input;
    }
  }

  /**
   * Processes an 'other' element.
   *
   * See select list webform element for select list properties.
   *
   * @see \Drupal\Core\Render\Element\Select
   */
  public static function processWebformOptionsCustom(&$element, FormStateInterface $form_state, &$complete_form) {
    // Load config entity and set the element's #options and #template.
    static::setTemplateOptions($element);

    // Sanitize option descriptions which may have been altered.
    // Note: Option text is escaped via JavaScript.
    // @see webform_options_custom.element.js#initializeTemplateTooltip
    $descriptions = [];
    foreach ($element['#options'] as $option_value => $option_text) {
      if (strpos($option_text, WebformOptionsHelper::DESCRIPTION_DELIMITER) !== FALSE) {
        list($option_text, $option_description) = explode(WebformOptionsHelper::DESCRIPTION_DELIMITER, $option_text);
        $element['#options'][$option_value] = $option_text;
        $descriptions[$option_value] = Xss::filterAdmin($option_description);
      }
    }

    // Get inline template context.
    $template_context = WebformArrayHelper::removePrefix($element) + [
      'descriptions' => $descriptions,
    ];

    $element['#tree'] = TRUE;

    // Select menu.
    $element['select'] = [
      '#type' => 'select',
      '#options' => $element['#options'],
      '#title' => $element['#title'],
      '#webform_element' => TRUE,
      '#title_display' => 'invisible',
      '#error_no_message' => TRUE,
    ];
    $properties = static::$properties;
    $element['select'] += array_intersect_key($element, array_combine($properties, $properties));

    // Apply #parents to select element.
    if (isset($element['#parents'])) {
      $element['select']['#parents'] = array_merge($element['#parents'], ['select']);
    }

    // Initialize the select to allow for webform enhancements.
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element_manager->buildElement($element['select'], $complete_form, $form_state);

    if (preg_match('/(\{\{|\{%)/', $element['#template'])) {
      // Build template using Twig when Twig syntax is found.
      $element['template'] = [
        '#type' => 'inline_template',
        '#template' => $element['#template'],
        '#context' => $template_context,
        '#prefix' => '<div class="webform-options-custom-template">',
        '#suffic' => '</div>',
      ];
    }
    else {
      // Build template using markup.
      $element['template'] = [
        '#markup' => Markup::create($element['#template']),
        '#prefix' => '<div class="webform-options-custom-template">',
        '#suffic' => '</div>',
      ];
    }

    // Set classes.
    $element['#attributes']['class'][] = 'js-webform-options-custom';
    $element['#attributes']['class'][] = 'webform-options-custom';
    if (!empty($element['#options_custom'])) {
      $webform_options_custom_class = Html::getClass($element['#options_custom']);
      $element['#attributes']['class'][] = 'js-webform-options-custom--' . $webform_options_custom_class;
      $element['#attributes']['class'][] = 'webform-options-custom--' . $webform_options_custom_class;
    }

    // Apply the element id to the wrapper so that inline form errors point
    // to the correct element.
    $element['#attributes']['id'] = $element['#id'];

    // Set SVG fill, zoom, tooltip, and show/hide select.
    if ($element['#fill']) {
      $element['#attributes']['data-fill'] = TRUE;
    }
    if ($element['#zoom']) {
      $element['#attributes']['data-zoom'] = TRUE;
    }
    if ($element['#tooltip']) {
      $element['#attributes']['data-tooltip'] = TRUE;
    }
    if (empty($element['#show_select'])) {
      $element['#attributes']['data-select-hidden'] = TRUE;
    }

    // Set descriptions.
    if ($descriptions) {
      $element['#attributes']['data-descriptions'] = Json::encode($descriptions);
    }

    // Remove options to prevent option validation on the element.
    unset($element['#options']);

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformOptionsCustom']);

    // Attach libraries.
    $element['#attached']['library'][] = 'webform_options_custom/webform_options_custom.element';
    if ($element['#zoom']) {
      $element['#attached']['library'][] = 'webform_options_custom/libraries.svg-pan-zoom';
    }

    // Process states.
    webform_process_states($element, '#wrapper_attributes');

    return $element;
  }

  /**
   * Validates an other element.
   */
  public static function validateWebformOptionsCustom(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = NestedArray::getValue($form_state->getValues(), $element['select']['#parents']);

    // Determine if the element is visible. (#access !== FALSE)
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);

    // Determine if the element has multiple values.
    $is_multiple = (empty($element['#multiple'])) ? FALSE : TRUE;

    // Determine if the return value is empty.
    if ($is_multiple) {
      $is_empty = (empty($value)) ? TRUE : FALSE;
    }
    else {
      $is_empty = ($value === '' || $value === NULL) ? TRUE : FALSE;
    }

    // Validate on elements with #access.
    if ($has_access && !empty($element['#required']) && $is_empty) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }

    $form_state->setValueForElement($element['select'], NULL);
    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

  /**
   * Set a custom options element #options property.
   *
   * @param array $element
   *   A custom options element.
   */
  public static function setOptions(array &$element) {
    // Do nothing.
  }

  /**
   * Set a custom options element #options property.
   *
   * @param array $element
   *   A custom options element.
   */
  public static function setTemplateOptions(array &$element) {
    if (isset($element['#_options_custom'])) {
      return;
    }
    $element['#_options_custom'] = TRUE;

    // Set options. Used by entity references.
    static::setOptions($element);

    // Load custom options from config entity.
    if (!empty($element['#options_custom'])) {
      $webform_option_custom = WebformOptionsCustomEntity::load($element['#options_custom']);
      if ($webform_option_custom) {
        $custom_element = $webform_option_custom->getElement();
        $element += $custom_element;
        $element['#options'] += $custom_element['#options'];
      }
    }

    // Set default properties.
    $element += [
      '#options' => [],
      '#value_attributes' => 'data-option-value,data-value,data-id,id',
      '#text_attributes' => 'data-option-text,data-text,data-name,name,title',
    ];

    // Get options.
    $options =& $element['#options'];

    // Build options by text look up.
    $options_by_text = [];
    foreach ($options as $option_value => $option_text) {
      $option_description = '';
      if (strpos($option_text, WebformOptionsHelper::DESCRIPTION_DELIMITER) !== FALSE) {
        list($option_text, $option_description) = explode(WebformOptionsHelper::DESCRIPTION_DELIMITER, $option_text);
      }
      $options_by_text[$option_text] = ['value' => $option_value, 'text' => $option_text, 'description' => $option_description];
    }

    // Get option value and text attributes.
    $value_attribute_name = NULL;
    if ($element['#value_attributes']) {
      $value_attributes = preg_split('/\s*,\s*/', trim($element['#value_attributes']));
      foreach ($value_attributes as $value_attribute) {
        if (strpos($element['#template'], $value_attribute) !== FALSE) {
          $value_attribute_name = $value_attribute;
          break;
        }
      }
    }
    $text_attribute_name = NULL;
    if ($element['#text_attributes']) {
      $text_attributes = preg_split('/\s*,\s*/', trim($element['#text_attributes']));
      foreach ($text_attributes as $text_attribute) {
        if (strpos($element['#template'], $text_attribute) !== FALSE) {
          $text_attribute_name = $text_attribute;
          break;
        }
      }
    }

    $custom_attributes = [];
    if ($value_attribute_name) {
      $custom_attributes[] = $value_attribute_name;
    }
    if ($text_attribute_name) {
      $custom_attributes[] = $text_attribute_name;
    }
    if (empty($custom_attributes)) {
      return;
    }

    // Combine text and value attributes into an Xpath query that finds all
    // DOM element which contain any of the attributes.
    $css_attributes = array_map(
      function ($value) {
        return '[' . $value . ']';
      },
      $custom_attributes
    );
    $css_selector_converter = new CssSelectorConverter();
    $xpath_expression = $css_selector_converter->toXPath(implode(',', $css_attributes));

    // Remove XML tag from SVG file.
    $xml_tag = NULL;
    $template = $element['#template'];
    if (preg_match('/<\?xml[^>]+\?>\s+/', $element['#template'], $match)) {
      $xml_tag = $match[0];
      $template = str_replace($xml_tag, '', $template);
    }
    $dom = Html::load($template);
    $xpath = new \DOMXPath($dom);
    foreach ($xpath->query($xpath_expression) as $dom_node) {
      if (in_array($dom_node->tagName, ['svg'])) {
        continue;
      }

      $dom_attributes = [];
      foreach ($dom_node->attributes as $attribute_name => $attribute_node) {
        /** @var \DOMNode $attribute_node */
        $dom_attributes[$attribute_name] = $attribute_node->nodeValue;
      }
      $dom_attributes += [
        $value_attribute_name => '',
        $text_attribute_name => '',
      ];

      // Get value and text attribute.
      $option_value = $dom_attributes[$value_attribute_name];
      $option_text = $dom_attributes[$text_attribute_name];

      // Set missing options value based on options text.
      if ($option_value === '' && $option_text !== '') {
        // Look up options value using the option's text.
        if (isset($options_by_text[$option_text])) {
          $option_value = $options_by_text[$option_text]['value'];
        }
        else {
          $option_value = $option_text;
        }
      }

      // Append value and text to the options array.
      $options += [$option_value => $option_text ?: $option_value];

      // Always set the data-option-value attribute.
      // Note: The select menu's option text is the canonical source for
      // the option text.
      $dom_node->setAttribute('data-option-value', $option_value);
    }

    // Set template with tweaked or additional attributes.
    $element['#template'] = $xml_tag . Html::serialize($dom);
  }

}
