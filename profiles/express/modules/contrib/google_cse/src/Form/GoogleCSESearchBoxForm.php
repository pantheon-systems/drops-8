<?php

namespace Drupal\google_cse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_cse\GoogleCSEServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GoogleCSESearchBoxForm.
 *
 * @package Drupal\google_cse\Form
 *
 * Form builder for the searchbox forms.
 */
class GoogleCSESearchBoxForm extends FormBase {

  /**
   * The object for Google CSE services.
   *
   * @var \Drupal\google_cse\GoogleCSEServices
   */
  protected $googleCSEServices;

  /**
   * RequestStack object for getting requests.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * GoogleCSESearchBoxForm constructor.
   *
   * @param \Drupal\google_cse\GoogleCSEServices $googleCSEServices
   *   The google cse services.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request object.
   */
  public function __construct(GoogleCSEServices $googleCSEServices, RequestStack $requestStack) {
    $this->googleCSEServices = $googleCSEServices;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_cse.services'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_cse_search_box_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('search.page.google_cse_search');
    if ($config->get('configuration')['results_display'] == 'here') {
      $cof = $config->get('configuration')['cof_here'];
    }
    else {
      $form['#action'] = 'http://' . $config->get('configuration')['domain'] . '/cse';
      $cof = $config->get('configuration')['cof_google'];
    }
    $form['#method'] = 'get';
    $form['cx'] = [
      '#type' => 'hidden',
      '#value' => $config->get('configuration')['cx'],
    ];
    $form['cof'] = [
      '#type' => 'hidden',
      '#value' => $cof,
    ];
    $form['query'] = [
      '#type' => 'textfield',
      '#default_value' => $this->requestStack->getCurrentRequest()->query->has('query') ? $this->requestStack->getCurrentRequest()->query->get('query') : '',
    ];
    $form['sa'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    foreach ($this->googleCSEServices->advancedSettings() as $parameter => $setting) {
      $form[$parameter] = [
        '#type' => 'hidden',
        '#value' => $setting,
      ];
    }
    $form['query']['#size'] = intval($config->get('configuration')['results_searchbox_width']);
    $form['query']['#title'] = $this->t('Enter your keywords');
    $this->googleCSEServices->siteSearchForm($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We leave it blank intentionally.
  }

}
