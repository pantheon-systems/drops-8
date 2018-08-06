<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the webform filter webform.
 */
class WebformEntityFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_filter_form';
  }

  /**
   * The webform storage.
   *
   * @var \Drupal\webform\WebformEntityStorageInterface
   */
  protected $webformStorage;

  /**
   * Constructs a WebformResultsCustomForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->webformStorage = $entity_type_manager->getStorage('webform');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $search = NULL, $category = NULL, $state = NULL, array $state_options = []) {
    $form['#attributes'] = ['class' => ['webform-filter-form']];
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter webforms'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter']['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by title, description, or elements'),
      '#title_display' => 'invisible',
      '#autocomplete_route_name' => 'entity.webform.autocomplete',
      '#placeholder' => $this->t('Filter by title, description, or elements'),
      '#maxlength' => 128,
      '#size' => 40,
      '#default_value' => $search,
    ];
    $form['filter']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#title_display' => 'invisible',
      '#options' => $this->webformStorage->getCategories(FALSE),
      '#empty_option' => ($category) ? $this->t('Show all webforms') : $this->t('Filter by category'),
      '#default_value' => $category,
    ];
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
    if (!empty($search) || !empty($category) || !empty($state)) {
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
      'state' => trim($form_state->getValue('state')),
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
