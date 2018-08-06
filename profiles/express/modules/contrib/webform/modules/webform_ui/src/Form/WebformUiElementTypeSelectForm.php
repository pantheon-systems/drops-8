<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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

    $elements = $this->elementManager->getInstances();
    $definitions = $this->getDefinitions();
    $category_index = 0;
    $categories = [];

    $form = parent::buildForm($form, $form_state, $webform);

    foreach ($definitions as $plugin_id => $plugin_definition) {
      $element_type = $plugin_id;

      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $elements[$element_type];

      // Skip hidden plugins.
      if ($webform_element->isHidden()) {
        continue;
      }

      // Skip wizard page which has a dedicated URL.
      if ($element_type == 'webform_wizard_page') {
        continue;
      }

      $category_name = (string) $plugin_definition['category'];
      if (!isset($categories[$category_name])) {
        $categories[$category_name] = $category_index++;
        $category_id = $categories[$category_name];
        $form[$category_id] = [
          '#type' => 'details',
          '#title' => $plugin_definition['category'],
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
      $form[$category_id]['elements'][$element_type] = $this->buildRow($plugin_definition, $webform_element, $url, $this->t('Add element'));
    }

    return $form;
  }

}
