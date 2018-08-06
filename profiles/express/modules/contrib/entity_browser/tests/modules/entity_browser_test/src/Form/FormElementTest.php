<?php

namespace Drupal\entity_browser_test\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;

/**
 * Provides a user login form.
 */
class FormElementTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_test_element';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#cache']['max-age'] = 0;

    $form['fancy_entity_browser'] = [
      '#type' => 'entity_browser',
      '#entity_browser' => 'test_entity_browser_iframe',
    ];

    if ($default = \Drupal::request()->get('default_entity')) {
      $form['fancy_entity_browser']['#default_value'] = [EntityBrowserElement::processEntityId($default)];
    }

    if ($selection_mode = \Drupal::request()->get('selection_mode')) {
      $form['fancy_entity_browser']['#selection_mode'] = $selection_mode;
    }

    $form['main_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entities = $form_state->getValue(['fancy_entity_browser', 'entities']);

    $message = 'Selected entities: ';
    $message .= implode(', ', array_map(
      function (EntityInterface $item) {
        return $item->label();
      },
      $entities
    ));
    drupal_set_message($message);
  }

}
