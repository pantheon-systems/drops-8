<?php

namespace Drupal\webform\Utility;

use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Helper class webform based methods.
 */
class WebformFormHelper {

  /**
   * Build form jQuery UI tabs.
   *
   * @param array $form
   *   A form.
   * @param array $tabs
   *   An associative array contain tabs.
   * @param string $active_tab
   *   The active tab name.
   *
   * @return array
   *   The form with tabs.
   *
   * @see \Drupal\webform\Form\WebformHandlerFormBase::buildForm
   * @see \Drupal\webform\Plugin\WebformElementBase::buildConfigurationFormTabs
   */
  public static function buildTabs(array $form, array $tabs, $active_tab = '') {
    // Allow tabs to be disabled via $form['#tab'] = FALSE.
    if (isset($form['#tabs']) && $form['#tabs'] === FALSE) {
      return $form;
    }

    // Determine if the form has nested (configuration) settings.
    // Used by WebformHandlers.
    $has_settings = (isset($form['settings']) && !empty($form['settings']['#tree']));

    // Always include general tab.
    $tabs = [
      'general' => [
        'title' => t('General'),
        'elements' => [],
        'weight' => 0,
      ],
    ] + $tabs;

    // Sort tabs by weight.
    uasort($tabs, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Assign tabs to elements.
    foreach ($tabs as $tab_name => $tab) {
      foreach ($tab['elements'] as $element_key) {
        if ($has_settings && isset($form['settings'][$element_key])) {
          $form['settings'][$element_key]['#group'] = 'tab_' . $tab_name;
          $tabs[$tab_name]['has_tabs'] = TRUE;
        }
        elseif (isset($form[$element_key])) {
          $form[$element_key]['#group'] = 'tab_' . $tab_name;
          $tabs[$tab_name]['has_tabs'] = TRUE;
        }
      }
    }

    // Set default general tab for settings.
    if ($has_settings) {
      foreach (Element::children($form['settings']) as $element_key) {
        if (!isset($form['settings'][$element_key]['#group'])) {
          $form['settings'][$element_key]['#group'] = 'tab_general';
          $tabs['general']['has_tabs'] = TRUE;
        }
      }
      $form['settings']['#group'] = FALSE;
    }

    // Set default general tab for all other elements.
    foreach (Element::children($form) as $element_key) {
      if (!isset($form[$element_key]['#group'])) {
        $form[$element_key]['#group'] = 'tab_general';
        $tabs['general']['has_tabs'] = TRUE;
      }
    }

    // Build tabs.
    $tab_items = [];
    $index = 0;
    foreach ($tabs as $tab_name => $tab) {
      // Skip empty tab.
      if (empty($tab['has_tabs'])) {
        continue;
      }

      $tab_items[] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('<none>', [], ['fragment' => 'webform-tab--' . $tab_name]),
        '#title' => $tab['title'],
        '#attributes' => [
          'class' => ['webform-tab'],
          'data-tab-index' => $index++,
        ],
      ];
      $form['tab_' . $tab_name] = [
        '#type' => 'container',
        '#group' => 'tabs',
        '#attributes' => [
          'id' => 'webform-tab--' . $tab_name,
        ],
      ];
    }

    // Add tabs.
    $form['tabs'] = [
      '#weight' => -1000,
      '#type' => 'container',
      '#attributes' => ['class' => ['webform-tabs']],
      '#attached' => ['library' => ['webform/webform.form.tabs']],
    ];
    if ($active_tab) {
      $form['tabs']['#attributes']['data-tab-active'] = 'webform-tab--' . $active_tab;
    }

    $form['tabs']['items'] = [
      '#theme' => 'item_list',
      '#items' => $tab_items,
    ];

    return $form;
  }

  /**
   * Cleanup webform state values.
   *
   * @param array $values
   *   An array of webform state values.
   * @param array $keys
   *   (optional) An array of custom keys to be removed.
   *
   * @return array
   *   The values without default keys like
   *   'form_build_id', 'form_token', 'form_id', 'op', 'actions', etc...
   */
  public static function cleanupFormStateValues(array $values, array $keys = []) {
    // Remove default FAPI values.
    unset(
      $values['form_build_id'],
      $values['form_token'],
      $values['form_id'],
      $values['op']
    );

    // Remove any objects.
    foreach ($values as $key => $value) {
      if (is_object($value)) {
        unset($values[$key]);
      }
    }

    // Remove custom keys.
    foreach ($keys as $key) {
      unset($values[$key]);
    }
    return $values;
  }

  /**
   * Traverse a render array and collect references to all elements in an associative array keyed by element name.
   *
   * @param array $build
   *   An render array.
   *
   * @return array
   *   An associative array of elements by reference.
   */
  public static function &flattenElements(array &$build) {
    $elements = [];
    $duplicate_element_keys = [];
    self::flattenElementsRecursive($build, $elements, $duplicate_element_keys);
    return $elements;
  }

  /**
   * Traverse a render array and collect references to all elements in an associative array keyed by element name.
   *
   * @param array $build
   *   An render array.
   * @param array $elements
   *   An empty array that will be populated with references to all elements.
   * @param array $duplicate_element_keys
   *   An array used to track elements with duplicate keys.
   */
  protected static function flattenElementsRecursive(array &$build, array &$elements, array &$duplicate_element_keys) {
    foreach ($build as $key => &$element) {
      if (Element::child($key) && is_array($element)) {
        // If there are duplicate element keys create an array of referenced
        // elements.
        if (isset($elements[$key])) {
          // If this is the second element, we need to restructure to first
          // element's reference to be an array of references.
          if (empty($duplicate_element_keys[$key])) {
            // Store a temporary references to the first element.
            $first_element = &$elements[$key];
            // Use unset() to break the reference.
            unset($elements[$key]);
            // Create an array of element references.
            $elements[$key] = [];
            // Append the first to the array of element references.
            $elements[$key][] = &$first_element;
          }
          // Now append the current element to array of element references.
          $elements[$key][] = &$build[$key];
          // Finally track elements with duplicate keys.
          $duplicate_element_keys[$key] = TRUE;
        }
        else {
          $elements[$key] = &$build[$key];
        }

        self::flattenElementsRecursive($element, $elements, $duplicate_element_keys);
      }
    }
  }

}
