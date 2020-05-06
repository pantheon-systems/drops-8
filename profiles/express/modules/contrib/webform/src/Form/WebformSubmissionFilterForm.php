<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides the webform submission filter form.
 */
class WebformSubmissionFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_submission_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $search = NULL, $state = NULL, array $state_options = [], $source_entity = NULL, $source_entity_options = []) {
    $form['#attributes'] = ['class' => ['webform-filter-form']];
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter submissions'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter']['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Keyword'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Filter by submitted data and/or notes'),
      '#maxlength' => 128,
      '#size' => 40,
      '#default_value' => $search,
    ];
    if ($source_entity_options) {
      if ($source_entity_options instanceof WebformInterface) {
        $form['filter']['entity'] = [
          '#type' => 'search',
          '#title' => $this->t('Submitted to'),
          '#title_display' => 'invisible',
          '#autocomplete_route_name' => 'entity.webform.results.source_entity.autocomplete',
          '#autocomplete_route_parameters' => ['webform' => $source_entity_options->id()],
          '#placeholder' => $this->t('Enter submitted to…'),
          '#size' => 20,
          '#default_value' => $source_entity,
        ];
      }
      else {
        $form['filter']['entity'] = [
          '#type' => 'select',
          '#title' => $this->t('Submitted to'),
          '#title_display' => 'invisible',
          '#options' => ['' => $this->t('Filter by submitted to')] + $source_entity_options,
          '#default_value' => $source_entity,
        ];
      }
    }
    $form['filter']['state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#title_display' => 'invisible',
      '#options' => $state_options,
      '#default_value' => $state,
    ];
    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($search) || !empty($state)) {
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
      'state' => trim($form_state->getValue('state')),
      'entity' => trim($form_state->getValue('entity')),
    ];
    $query = array_filter($query);
    if (!empty($query['entity']) && preg_match('#\(([^)]+)\)#', $query['entity'], $match)) {
      $query['entity'] = $match[1];
    }
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
