<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Provides a trait for webform composite form elements.
 *
 * Any form element that is comprised of several distinct parts can use this
 * trait to add support for a composite title or description.
 *
 * The Webform overrides any element that is using the CompositeFormElementTrait
 * and applies the below pre renderer which adds support for
 * #wrapper_attributes and additional some classes.
 *
 * @see \Drupal\Core\Render\Element\CompositeFormElementTrait
 * @see \Drupal\webform\Plugin\WebformElementBase::prepareCompositeFormElement
 */
trait WebformCompositeFormElementTrait {

  /**
   * Adds form element theming to an element if its title or description is set.
   *
   * This is used as a pre render function for checkboxes and radios.
   */
  public static function preRenderWebformCompositeFormElement($element) {
    $has_content = (isset($element['#title']) || isset($element['#description']));

    if (!$has_content) {
      return $element;
    }
    // Set attributes.
    if (!isset($element['#attributes'])) {
      $element['#attributes'] = [];
    }

    // Apply wrapper attributes to attributes.
    if (isset($element['#wrapper_attributes'])) {
      $element['#attributes'] = NestedArray::mergeDeep($element['#attributes'], $element['#wrapper_attributes']);
    }

    // Remove .js-webform-states-hidden from attributes if the element has
    // a states wrapper.
    // @see \Drupal\webform\Plugin\WebformElement\WebformAddress
    // @see \Drupal\webform\Plugin\WebformElement\WebformName
    if (isset($element['#states']) && isset($element['#attributes']['class'])) {
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $element_plugin = $element_manager->getElementInstance($element);
      if ($element_plugin->getPluginDefinition()['states_wrapper']) {
        WebformArrayHelper::removeValue($element['#attributes']['class'], 'js-webform-states-hidden');
      }
    }

    // Set id and classes.
    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $element['#id'] . '--wrapper';
    }
    $element['#attributes']['class'][] = Html::getClass($element['#type']) . '--wrapper';
    $element['#attributes']['class'][] = 'fieldgroup';
    $element['#attributes']['class'][] = 'form-composite';

    // Add composite library.
    $element['#attached']['library'][] = 'webform/webform.composite';

    // Set theme wrapper to wrapper type.
    $wrapper_type = (isset($element['#wrapper_type'])) ? $element['#wrapper_type'] : 'fieldset';
    $element['#theme_wrappers'][] = $wrapper_type;

    // Apply wrapper specific enhancements.
    switch ($wrapper_type) {
      case 'fieldset':
        // Set the element's title attribute to show #title as a tooltip, if needed.
        if (isset($element['#title']) && $element['#title_display'] == 'attribute') {
          $element['#attributes']['title'] = $element['#title'];
          if (!empty($element['#required'])) {
            // Append an indication that this fieldset is required.
            $element['#attributes']['title'] .= ' (' . t('Required') . ')';
          }
        }

        // Add hidden and visible title class to fix composite fieldset
        // top/bottom margins.
        if (isset($element['#title'])) {
          if (!empty($element['#title_display']) && in_array($element['#title_display'], ['invisible', 'attribute'])) {
            $element['#attributes']['class'][] = 'webform-composite-hidden-title';
          }
          else {
            $element['#attributes']['class'][] = 'webform-composite-visible-title';
          }
        }
        break;

      case 'form_element':
        // Process #states for #wrapper_attributes.
        // @see template_preprocess_form_element().
        webform_process_states($element, '#wrapper_attributes');
        break;
    }

    // Issue #3007132: [accessibility] Radios and checkboxes the WAI-ARIA
    // 'aria-describedby' attribute has a reference to an ID that does not
    // exist or an ID that is not unique
    // https://www.drupal.org/project/webform/issues/3007132
    // @see \Drupal\Core\Form\FormBuilder::doBuildForm
    if (!empty($element['#description'])) {
      $fix_aria_describedby = (preg_match('/^(?:webform_)?(?:radios|checkboxes|buttons)(?:_other)?$/', $element['#type']));
      foreach (Element::children($element) as $key) {
        // Skip if child element has a dedicated description.
        if (!empty($element[$key]['#description'])) {
          continue;
        }

        // Skip if 'aria-describedby' is not set.
        if (empty($element[$key]['#attributes']['aria-describedby'])) {
          continue;
        }

        // Only fix 'aria-describedby' attribute if it pointing to a broken id.
        if ($element[$key]['#attributes']['aria-describedby'] === $element['#id'] . '--description') {
          if ($fix_aria_describedby) {
            $element[$key]['#attributes']['aria-describedby'] = $element['#attributes']['id'] . '--description';
          }
          else {
            unset($element[$key]['#attributes']['aria-describedby']);
          }
        }
      }
    }

    return $element;
  }

}
