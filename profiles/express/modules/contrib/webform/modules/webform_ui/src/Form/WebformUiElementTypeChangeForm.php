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

    /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
    $webform_element = $this->elementManager->getElementInstance($element);

    $related_types = $webform_element->getRelatedTypes($element);
    if (empty($related_types)) {
      throw new NotFoundHttpException();
    }

    $elements = $this->elementManager->getInstances();
    $definitions = $this->getDefinitions();

    $form = parent::buildForm($form, $form_state, $webform);
    
    $form['elements'] = [
      '#type' => 'table',
      '#header' => $this->getHeader(),
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

    foreach ($related_types as $element_type => $element_type_label) {
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $elements[$element_type];
      $plugin_definition = $definitions[$element_type];

      $url = Url::fromRoute(
        'entity.webform_ui.element.edit_form',
        ['webform' => $webform->id(), 'key' => $key],
        ['query' => ['type' => $element_type]]
      );
      $form['elements'][$element_type] = $this->buildRow($plugin_definition, $webform_element, $url, $this->t('Change'));
    }

    return $form;
  }

}
