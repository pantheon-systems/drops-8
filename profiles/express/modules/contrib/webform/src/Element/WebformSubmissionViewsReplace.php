<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for selecting webform submission views replacement routes.
 *
 * @FormElement("webform_submission_views_replace")
 */
class WebformSubmissionViewsReplace extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformSubmissionViewsReplace'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value'])) {
        $element['#default_value'] = [];
      }
      return $element['#default_value'];
    }
    else {
      return $input;
    }
  }

  /**
   * Processes a webform submission views replacement element.
   */
  public static function processWebformSubmissionViewsReplace(&$element, FormStateInterface $form_state, &$complete_form) {
    $is_global = (!empty($element['#global'])) ? TRUE : FALSE;
    $element['#tree'] = TRUE;

    $element['#value'] = (!is_array($element['#value'])) ? [] : $element['#value'];
    $element['#value'] += [
      'global_routes' => [],
      'webform_routes' => [],
      'node_routes' => [],
    ];

    // Global routes.
    if ($is_global) {
      $element['global_routes'] = [
        '#type' => 'checkboxes',
        '#title' => t('Replace the global results with submission views'),
        '#options' => [
          'entity.webform_submission.collection' => t('Submissions'),
          'entity.webform_submission.user' => t('User'),
        ],
        '#default_value' => $element['#value']['global_routes'],
        '#element_validate' => [['\Drupal\webform\Utility\WebformElementHelper', 'filterValues']],
      ];
    }

    // Webform routes.
    $webform_routes_options = [
      'entity.webform.results_submissions' => t('Submissions'),
      'entity.webform.user.drafts' => t('User drafts'),
      'entity.webform.user.submissions' => t('User submissions'),
    ];
    if (!$is_global) {
      $default_webform_routes = \Drupal::configFactory()->get('webform.settings')->get('settings.default_submission_views_replace.webform_routes') ?: [];
      if ($webform_routes_options) {
        $webform_routes_options = array_diff_key($webform_routes_options, array_flip($default_webform_routes));
      }
    }
    $element['webform_routes'] = [
      '#type' => 'checkboxes',
      '#title' => t('Replace the webform results with submission views'),
      '#options' => $webform_routes_options,
      '#default_value' => ($webform_routes_options) ? $element['#value']['webform_routes'] : [],
      '#access' => ($webform_routes_options) ? TRUE : FALSE,
      '#element_validate' => [['\Drupal\webform\Utility\WebformElementHelper', 'filterValues']],
    ];

    // Node routes.
    $node_routes_options = [
      'entity.node.webform.results_submissions' => t('Submissions'),
      'entity.node.webform.user.drafts' => t('User drafts'),
      'entity.node.webform.user.submissions' => t('User submissions'),
    ];
    if (!$is_global) {
      $default_node_routes = \Drupal::configFactory()->get('webform.settings')->get('settings.default_submission_views_replace.node_routes') ?: [];
      if ($default_node_routes) {
        $node_routes_options = array_diff_key($node_routes_options, array_flip($default_node_routes));
      }
    }
    $element['node_routes'] = [
      '#type' => 'checkboxes',
      '#title' => t('Replace the node results with submission views'),
      '#options' => $node_routes_options,
      '#default_value' => ($node_routes_options) ? $element['#value']['node_routes'] : [],
      '#access' => ($node_routes_options && \Drupal::moduleHandler()->moduleExists('webform_node')) ? TRUE : FALSE,
      '#element_validate' => [['\Drupal\webform\Utility\WebformElementHelper', 'filterValues']],
    ];

    // Add validate callback that extracts the array of items.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformSubmissionViewsReplace']);

    return $element;
  }

  /**
   * Validates webform submission views replacement element.
   */
  public static function validateWebformSubmissionViewsReplace(&$element, FormStateInterface $form_state, &$complete_form) {
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Remove empty view replace references.
    if (empty($values['global_routes']) && empty($values['webform_routes']) && empty($values['node_routes'])) {
      $values = [];
    }

    $element['#value'] = $values;
    $form_state->setValueForElement($element, $values);
  }

}
