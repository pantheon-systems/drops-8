<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;

/**
 * Provides a form element for selecting webform submission views.
 *
 * @FormElement("webform_submission_views")
 */
class WebformSubmissionViews extends WebformMultiple {

  /**
   * {@inheritdoc}
   */
  public static function processWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!\Drupal::moduleHandler()->moduleExists('views')) {
      $element['#element_validate'] = [[get_called_class(), 'emptyValue']];
      return $element;
    }

    $element['#key'] = 'name';
    $element['#header'] = TRUE;
    $element['#empty_items'] = 0;
    $element['#min_items'] = 1;
    $element['#add_more_input_label'] = t('more submission views');

    // Build element.
    $element['#element'] = [];

    // Name / Title / View.
    $view_options = [];
    /** @var \Drupal\views\ViewEntityInterface[] $views */
    $views = View::loadMultiple();
    foreach ($views as $view) {
      // Only include webform submission views.
      if ($view->get('base_table') !== 'webform_submission' || $view->get('base_field') !== 'sid') {
        continue;
      }

      $optgroup = $view->label();
      $displays = $view->get('display');
      foreach ($displays as $display_id => $display) {
        // Only include embed displays.
        if ($display['display_plugin'] === 'embed') {
          $view_options[$optgroup][$view->id() . ':' . $display_id] = $optgroup . ': ' . $display['display_title'];
        }
      }
    }
    $element['#element']['name_title_view'] = [
      '#type' => 'container',
      '#title' => t('View / Name / Title'),
      '#help' => '<b>' . t('View') . ':</b> ' . t('A webform submission embed display. The selected view should also include contextual filters. {webform_id}/{source_entity_type}/{source_entity_id}/{account_id}/{in_draft}') .
        '<hr/>' . '<b>' . t('Name') . ':</b> ' . t('The name to be displayed in the URL when there are multiple submission views available.') .
        '<hr/>' . '<b>' . t('Options') . ':</b> ' . t('The title to be display in the dropdown menu when there are multiple submission views available.'),
      'view' => [
        '#type' => 'select',
        '#title' => t('View'),
        '#title_display' => 'invisible',
        '#empty_option' => t('Select view…'),
        '#options' => $view_options,
        '#error_no_message' => TRUE,
      ],
      'name' => [
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#title_display' => 'invisible',
        '#placeholder' => t('Enter name…'),
        '#size' => 20,
        '#pattern' => '^[-_a-z0-9]+$',
        '#error_no_message' => TRUE,
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#title_display' => 'invisible',
        '#placeholder' => t('Enter title…'),
        '#size' => 20,
        '#error_no_message' => TRUE,
      ],
    ];

    // Global routes.
    if (!empty($element['#global'])) {
      $global_route_options = [
        'entity.webform_submission.collection' => t('Submissions'),
        'entity.webform_submission.user' => t('User'),
      ];
      $element['#element']['global_routes'] = [
        '#type' => 'checkboxes',
        '#title' => t('Apply to global'),
          '#help' => t('Display the selected view on the below paths') .
            '<hr/><b>' . t('Submissions') . ':</b><br/>/admin/structure/webform/submissions/manage' .
            '<hr/><b>' . t('User') . ':</b><br/>/user/{user}/submissions',
        '#options' => $global_route_options,
        '#element_validate' => [['\Drupal\webform\Utility\WebformElementHelper', 'filterValues']],
        '#error_no_message' => TRUE,
      ];
    }

    // Webform routes.
    $webform_route_options = [
      'entity.webform.results_submissions' => t('Submissions'),
      'entity.webform.user.drafts' => t('User drafts'),
      'entity.webform.user.submissions' => t('User submissions'),
    ];
    $element['#element']['webform_routes'] = [
      '#type' => 'checkboxes',
      '#title' => t('Apply to webform'),
        '#help' => t('Display the selected view on the below paths') .
          '<hr/><b>' . t('Submissions') . ':</b><br/>/admin/structure/webform/manage/{webform}/results/submissions' .
          '<hr/><b>' . t('User drafts') . ':</b><br/>/webform/{webform}/drafts' .
          '<hr/><b>' . t('User submissions') . ':</b><br/>/webform/{webform}/submissions',
      '#options' => $webform_route_options,
      '#element_validate' => [['\Drupal\webform\Utility\WebformElementHelper', 'filterValues']],
      '#error_no_message' => TRUE,
    ];

    // Node routes.
    if (\Drupal::moduleHandler()->moduleExists('webform_node')) {
      $node_route_options = [
        'entity.node.webform.results_submissions' => t('Submissions'),
        'entity.node.webform.user.drafts' => t('User drafts'),
        'entity.node.webform.user.submissions' => t('User submissions'),
      ];
      $element['#element']['node_routes'] = [
        '#type' => 'checkboxes',
        '#title' => t('Apply to node'),
        '#help' =>
          t('Display the selected view on the below paths') .
          '<hr/><b>' . t('Submissions') . ':</b><br/>/node/{node}/webform/results/submissions' .
          '<hr/>' . '<b>' . t('User drafts') . ':</b><br/>/node/{node}/webform/drafts' .
          '<hr/>' . '<b>' . t('User submissions') . ':</b><br/>/node/{node}/webform/submissions',
        '#options' => $node_route_options,
        '#element_validate' => [['\Drupal\webform\Utility\WebformElementHelper', 'filterValues']],
        '#error_no_message' => TRUE,
      ];
    }

    parent::processWebformMultiple($element, $form_state, $complete_form);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!\Drupal::moduleHandler()->moduleExists('views')) {
      $element['#value'] = [];
      $form_state->setValueForElement($element, []);
      return;
    }

    parent::validateWebformMultiple($element, $form_state, $complete_form);
    $items = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    foreach ($items as $name => &$item) {
      // Remove empty view references.
      if ($name === '' && empty($item['view']) && empty($item['global_routes']) && empty($item['webform_routes']) && empty($item['node_routes'])) {
        unset($items[$name]);
        continue;
      }

      if ($name === '') {
        $form_state->setError($element, t('Name is required.'));
      }
      if (empty($item['title'])) {
        $form_state->setError($element, t('Title is required.'));
      }
      if (empty($item['view'])) {
        $form_state->setError($element, t('View name/display id is required.'));
      }
    }

    $element['#value'] = $items;
    $form_state->setValueForElement($element, $items);
  }

  /**
   * Form validate callback which clears the submitted value.
   */
  public static function emptyValue(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#value'] = [];
    $form_state->setValueForElement($element, []);
  }

}
