<?php

namespace Drupal\webform_devel\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\Utility\WebformYaml;

/**
 * Export a webform's element to Form API (FAPI).
 */
class WebformDevelEntityFormApiTestForm extends WebformDevelEntityFormApiBaseForm {

  use WebformDialogFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $elements = $webform->getElementsDecoded();

    // Cleanup element and set default data for testing.
    $this->cleanupElements($elements);
    $this->setDefaultValues($elements);

    // Process elements and replace tokens.
    $this->elementManager->processElements($elements);
    $elements = $this->tokenManager->replace($elements);

    // Set elements.
    $form += $elements;

    // Append submit actions.
    $form['actions'] = [
      '#type' => 'actions',
      '#tree' => TRUE,
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Test'),
        '#button_type' => 'primary',
      ],
    ];

    // Attach the webform library.
    $form['#attached']['library'][] = 'webform/webform.form';

    // Autofocus: Save details open/close state.
    $form['#attributes']['class'][] = 'js-webform-autofocus';
    $form['#attached']['library'][] = 'webform/webform.form.auto_focus';

    // Unsaved: Warn users about unsaved changes.
    $form['#attributes']['class'][] = 'js-webform-unsaved';
    $form['#attached']['library'][] = 'webform/webform.form.unsaved';

    // Details save: Attach details element save open/close library.
    $form['#attached']['library'][] = 'webform/webform.element.details.save';

    // Details toggle: Display collapse/expand all details link.
    $form['#attributes']['class'][] = 'js-webform-details-toggle';
    $form['#attributes']['class'][] = 'webform-details-toggle';
    $form['#attached']['library'][] = 'webform/webform.element.details.toggle';

    return $this->buildDialogConfirmForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->isDialog()) {
      $form_state->setRebuild();
    }

    // Display submission values.
    $values = $form_state->getValues();
    unset(
      $values['form_build_id'],
      $values['form_token'],
      $values['form_id'],
      $values['op'],
      $values['actions']
    );
    $build = ['#markup' => 'Submitted values are:<pre>' . WebformYaml::encode($values) . '</pre>'];
    $this->messenger()->addWarning($this->renderer->renderPlain($build));
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Set webform elements default values using test data..
   *
   * @param array $elements
   *   An render array representing elements.
   */
  protected function setDefaultValues(array &$elements) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $data = $this->generate->getData($webform);
    $flattened_elements =& WebformFormHelper::flattenElements($elements);
    foreach ($flattened_elements as $element_key => &$element) {
      if (isset($data[$element_key])) {
        $element['#default_value'] = $data[$element_key];
      }
    }
  }

}
