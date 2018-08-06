<?php

namespace Drupal\simplesamlphp_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the simplesamlphp_auth basic settings form.
 */
class BasicSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplesamlphp_auth_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simplesamlphp_auth.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simplesamlphp_auth.settings');

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic settings'),
      '#collapsible' => FALSE,
    ];
    $form['basic']['activate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate authentication via SimpleSAMLphp'),
      '#default_value' => $config->get('activate'),
      '#description' => $this->t('Checking this box before configuring the module could lock you out of Drupal.'),
    ];
    $form['basic']['auth_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authentication source for this SP'),
      '#default_value' => $config->get('auth_source'),
      '#description' => $this->t('The name of the source to use (Usually in authsources.php).'),
    ];
    $form['basic']['login_link_display_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Federated Log In Link Display Name'),
      '#default_value' => $config->get('login_link_display_name'),
      '#description' => $this->t('Text to display as the link to the external federated login page.'),
    ];

    $form['basic']['header_no_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Header with: Cache-Control: no-cache'),
      '#default_value' => $config->get('header_no_cache'),
      '#description' => $this->t('Use a "Cache-Control: no-cache" header in the HTTP response to avoid the redirection be cached (e.g. when using a reverse-proxy layer).'),
    ];

    $form['debugging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debugging'),
      '#collapsible' => FALSE,
    ];
    $form['debugging']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Turn on debugging messages'),
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('Expand the level of Drupal logging to include debugging information.'),
    ];

    $form['user_provisioning'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User Provisioning'),
      '#collapsible' => FALSE,
    ];
    $form['user_provisioning']['register_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Register users (i.e., auto-provisioning)'),
      '#default_value' => $config->get('register_users'),
      '#description' => $this->t('Determines whether or not the module should automatically create/register new Drupal accounts for users that authenticate using SimpleSAMLphp. Unless you\'ve done some custom work to provision Drupal accounts with the necessary authmap entries you will want this checked.<br /><br />NOTE: If unchecked each user must already have been provisioned a Drupal account correctly linked to the SAML authname attribute (e.g. by creating Drupal users with "Enable this user to leverage SAML authentication" checked). Otherwise they will receive a notice and be denied access.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('simplesamlphp_auth.settings');

    $config->set('activate', $form_state->getValue('activate'));
    $config->set('auth_source', $form_state->getValue('auth_source'));
    $config->set('login_link_display_name', $form_state->getValue('login_link_display_name'));
    $config->set('debug', $form_state->getValue('debug'));
    $config->set('register_users', $form_state->getValue('register_users'));
    $config->set('header_no_cache', $form_state->getValue('header_no_cache'));
    $config->save();
  }

}
