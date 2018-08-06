<?php

namespace Drupal\simplesamlphp_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the simplesamlphp_auth local settings form.
 */
class LocalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplesamlphp_auth_local_settings_form';
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

    $form['authentication'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal authentication'),
      '#collapsible' => FALSE,
    ];
    $form['authentication']['allow_default_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow authentication with local Drupal accounts'),
      '#default_value' => $config->get('allow.default_login'),
      '#description' => $this->t('Check this box if you want to let people log in with local Drupal accounts (without using simpleSAMLphp). If you want to restrict this privilege to certain users you can enter the Drupal user IDs in the field below.'),
    ];
    $form['authentication']['allow_set_drupal_pwd'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow SAML users to set Drupal passwords'),
      '#default_value' => $config->get('allow.set_drupal_pwd'),
      '#description' => $this->t('Check this box if you want to let people set passwords for their local Drupal accounts. This will allow users to log in using either SAML or a local Drupal account. Disabling this removes the password change fields from the user profile form.<br/>NOTE: In order for them to login using their local Drupal password you must allow local logins with the settings below.'),
    ];
    $form['authentication']['allow_default_login_roles'] = [
      '#type' => 'checkboxes',
      '#size' => 3,
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
      '#multiple' => TRUE,
      '#title' => $this->t('Which ROLES should be allowed to login with local accounts?'),
      '#default_value' => $config->get('allow.default_login_roles'),
      '#description' => $this->t('Roles that should be allowed to login without simpleSAMLphp. Examples are dev/admin roles or guest roles.'),
    ];
    $form['authentication']['allow_default_login_users'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Which users should be allowed to login with local accounts?'),
      '#default_value' => $config->get('allow.default_login_users'),
      '#description' => $this->t('Example: <i>1,2,3</i><br />A comma-separated list of user IDs that should be allowed to login without simpleSAMLphp.'),
    ];
    $form['authentication']['logout_goto_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL after logging out'),
      '#default_value' => $config->get('logout_goto_url'),
      '#description' => $this->t('Optionally, specify a URL for users to go to after logging out.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('simplesamlphp_auth.settings');

    $config->set('allow.default_login', $form_state->getValue('allow_default_login'));
    $config->set('allow.set_drupal_pwd', $form_state->getValue('allow_set_drupal_pwd'));
    $config->set('allow.default_login_roles', $form_state->getValue('allow_default_login_roles'));
    $config->set('allow.default_login_users', $form_state->getValue('allow_default_login_users'));
    $config->set('logout_goto_url', $form_state->getValue('logout_goto_url'));
    $config->save();
  }

}
