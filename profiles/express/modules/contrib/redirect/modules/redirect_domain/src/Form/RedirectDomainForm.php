<?php

namespace Drupal\redirect_domain\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a redirect domain configuration form.
 */
class RedirectDomainForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_redirect_domain_form';
  }

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      'redirect_domain.domains',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$form_state->has('maximum_domains')) {
      $form_state->set('maximum_domains', 1);
    }

    $form['redirects'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [
        $this->t('From domain'),
        $this->t('Sub path'),
        $this->t('Destination')
      ],
      '#prefix' => '<div id="redirect-domain-wrapper">',
      '#suffix' => '</div>',
    ];

    $rows = [];
    // Obtain domain redirects from configuration.
    if ($domain_redirects = $this->config('redirect_domain.domains')->get('domain_redirects')) {
      foreach ($domain_redirects as $key => $value) {
        foreach ($value as $item) {
          $form['redirects'][] = [
            'from' => [
              '#type' => 'textfield',
              '#value' => str_replace(':','.',$key),
            ],
            'sub_path' => [
              '#type' => 'textfield',
              '#value' => $item['sub_path'],
            ],
            'destination' => [
              '#type' => 'textfield',
              '#value' => $item['destination'],
            ],
          ];
        }
      }
    }

    // Fields for the new domain redirects.
    for ($i = 0; $i < $form_state->get('maximum_domains'); $i++) {
      $form['redirects'][] = [
        'from' => [
          '#type' => 'textfield',
        ],
        'sub_path' => [
          '#type' => 'textfield',
          '#value' => '/',
        ],
        'destination' => [
          '#type' => 'textfield',
        ],
      ];
    }

    $form['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another'),
      '#submit' => ['::addAnotherSubmit'],
      '#ajax' => [
        'callback' => '::ajaxAddAnother',
        'wrapper' => 'redirect-domain-wrapper',
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * Ajax callback for adding another domain redirect.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The new domain redirect form part.
   */
  public function ajaxAddAnother(array $form, FormStateInterface $form_state) {
    return $form['redirects'];
  }

  /**
   * Submit callback for adding a new domain field.
   */
  public function addAnotherSubmit(array $form, FormStateInterface $form_state) {
    $form_state->set('maximum_domains', $form_state->get('maximum_domains') + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($redirects = $form_state->getValue('redirects')) {
      foreach ($redirects as $redirect) {
        if (strpos($redirect['from'], '://') !== FALSE) {
          $form_state->setErrorByName('redirects', t('No protocol should be included in the redirect domain.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $domain_redirects = [];
    $domain_config = $this->config('redirect_domain.domains');

    if ($redirects = $form_state->getValue('redirects')) {
      foreach ($redirects as $redirect) {
        if (!empty($redirect['from']) && !empty($redirect['destination'])) {
          // Replace '.' with ':' for an eligible key.
          $redirect['from'] = str_replace('.', ':', $redirect['from']);
          $domain_redirects[$redirect['from']][] = [
            'sub_path' => '/' . ltrim($redirect['sub_path'], '/'),
            'destination' => $redirect['destination']
          ];
        }
      }
    }
    $domain_config->set('domain_redirects', $domain_redirects);
    $domain_config->save();
    drupal_set_message(t('The domain redirects have been saved.'));
  }
}
