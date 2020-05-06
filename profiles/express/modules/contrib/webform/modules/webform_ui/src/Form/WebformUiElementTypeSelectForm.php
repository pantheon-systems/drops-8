<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformElementWizardPageInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides a select element type webform for a webform element.
 */
class WebformUiElementTypeSelectForm extends WebformUiElementTypeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_ui_element_type_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $parent = $this->getRequest()->query->get('parent');

    if ($parent) {
      $parent_element = $webform->getElement($parent);
      $t_args = ['@parent' => $parent_element['#admin_title'] ?: $parent_element['#title'] ?: $parent_element['#webform_key']];
      $form['#title'] = $this->t('Select an element to add to "@parent"', $t_args);
    }

    $elements = $this->elementManager->getInstances();
    $definitions = $this->getDefinitions();
    $category_index = 0;
    $categories = [];

    $form = parent::buildForm($form, $form_state, $webform);

    foreach (array_keys($definitions) as $element_type) {
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $elements[$element_type];

      // Skip disabled or hidden.
      if ($webform_element->isDisabled() || $webform_element->isHidden()) {
        continue;
      }

      // Skip wizard-type pages, which have a dedicated URL.
      if ($webform_element instanceof WebformElementWizardPageInterface) {
        continue;
      }

      $category_name = (string) $webform_element->getPluginCategory();
      if (!isset($categories[$category_name])) {
        $categories[$category_name] = $category_index++;
        $category_id = $categories[$category_name];
        $form[$category_id] = [
          '#type' => 'details',
          '#title' => $webform_element->getPluginCategory(),
          '#open' => TRUE,
          '#attributes' => ['data-webform-element-id' => 'webform-ui-element-type-' . $category_id],
        ];
        $form[$category_id]['elements'] = [
          '#type' => 'table',
          '#header' => $this->getHeader(),
          '#rows' => [],
          '#empty' => $this->t('No element available.'),
          '#attributes' => [
            'class' => ['webform-ui-element-type-table'],
          ],
        ];
      }
      else {
        $category_id = $categories[$category_name];
      }

      $url = Url::fromRoute(
        'entity.webform_ui.element.add_form',
        ['webform' => $webform->id(), 'type' => $element_type],
        ($parent) ? ['query' => ['parent' => $parent]] : []
      );
      $form[$category_id]['elements'][$element_type] = $this->buildRow($webform_element, $url, $this->t('Add element'));
    }

    return $form;
  }

}
