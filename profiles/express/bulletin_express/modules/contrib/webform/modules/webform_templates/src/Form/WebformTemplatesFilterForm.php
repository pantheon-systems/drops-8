<?php

namespace Drupal\webform_templates\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the webform templates filter webform.
 */
class WebformtemplatesFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_templates_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $search = NULL, $category = NULL) {
    $form['#attributes'] = ['class' => ['webform-filter-form']];
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter templates'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter']['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by title, description, or elements'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Filter by title, description, or elements'),
      '#maxlength' => 128,
      '#size' => 40,
      '#autocomplete_route_name' => 'entity.webform.templates.autocomplete',
      '#default_value' => $search,
    ];
    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = \Drupal::service('entity_type.manager')->getStorage('webform');
    $form['filter']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#title_display' => 'invisible',
      '#options' => $webform_storage->getCategories(TRUE),
      '#empty_option' => ($category) ? $this->t('Show all webforms') : $this->t('Filter by category'),
      '#default_value' => $category,
    ];
    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($search)) {
      $form['filter']['reset'] = [
        '#type' => 'submit',
        '#submit' => ['::resetForm'],
        '#value' => $this->t('Reset'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [
      'search' => trim($form_state->getValue('search')),
      'category' => trim($form_state->getValue('category')),
    ];
    $form_state->setRedirect($this->getRouteMatch()->getRouteName(), $this->getRouteMatch()->getRawParameters()->all(), [
      'query' => $query ,
    ]);
  }

  /**
   * Resets the filter selection.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getRouteMatch()->getRouteName(), $this->getRouteMatch()->getRawParameters()->all());
  }

}
