<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a change element type webform for a webform element.
 */
class WebformUiElementTypeChangeForm extends WebformUiElementTypeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_ui_element_type_change_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL) {
    $element = $webform->getElement($key);

    /** @var \Drupal\webform\WebformElementInterface $webform_element */
    $webform_element = $this->elementManager->getElementInstance($element);

    $related_types = $webform_element->getRelatedTypes($element);
    if (empty($related_types)) {
      throw new NotFoundHttpException();
    }

    $headers = [];
    $headers[] = ['data' => $this->t('Element')];
    $headers[] = ['data' => $this->t('Category')];
    if (!$this->isOffCanvasDialog()) {
      $headers[] = ['data' => $this->t('Operations')];
    }

    $definitions = $this->getDefinitions();
    $rows = [];
    foreach ($related_types as $related_type_name => $related_type_label) {
      $plugin_definition = $definitions[$related_type_name];

      $row = [];
      $row['title']['data'] = [
        '#type' => 'link',
        '#title' => $plugin_definition['label'],
        '#url' => Url::fromRoute('entity.webform_ui.element.edit_form', ['webform' => $webform->id(), 'key' => $key], ['query' => ['type' => $related_type_name]]),
        '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['webform-tooltip-link', 'js-webform-tooltip-link']) + ['title' => $plugin_definition['description']],
      ];
      $row['category']['data'] = (isset($plugin_definition['category'])) ? $plugin_definition['category'] : $this->t('Other');
      if (!$this->isOffCanvasDialog()) {
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => [
            'change' => [
              'title' => $this->t('Change'),
              'url' => Url::fromRoute('entity.webform_ui.element.edit_form', [
                'webform' => $webform->id(),
                'key' => $key
              ], ['query' => ['type' => $related_type_name]]),
              'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
            ],
          ],
        ];

        // Issue #2741877 Nested modals don't work: when using CKEditor in a
        // modal, then clicking the image button opens another modal,
        // which closes the original modal.
        // @todo Remove the below workaround once this issue is resolved.
        if ($related_type_name == 'processed_text') {
          unset($row['operations']['data']['#links']['change']['attributes']);
        }
      }
      $rows[] = $row;
    }

    $form = [];
    $form['elements'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['webform-ui-element-type-table'],
      ],
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button']),
      '#url' => Url::fromRoute('entity.webform_ui.element.edit_form', ['webform' => $webform->id(), 'key' => $key]),
    ];
    $form['#attached']['library'][] = 'webform/webform.tooltip';
    return $form;
  }

}
