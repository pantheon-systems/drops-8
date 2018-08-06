<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class RedirectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_settings_form';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['redirect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redirect.settings');
    $form['redirect_auto_redirect'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create redirects when URL aliases are changed.'),
      '#default_value' => $config->get('auto_redirect'),
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('path'),
    );
    $form['redirect_passthrough_querystring'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Retain query string through redirect.'),
      '#default_value' => $config->get('passthrough_querystring'),
      '#description' => $this->t('For example, given a redirect from %source to %redirect, if a user visits %sourcequery they would be redirected to %redirectquery. The query strings in the redirection will always take precedence over the current query string.', array('%source' => 'source-path', '%redirect' => 'node?a=apples', '%sourcequery' => 'source-path?a=alligators&b=bananas', '%redirectquery' => 'node?a=apples&b=bananas')),
    );
    $form['redirect_warning'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display a warning message to users when they are redirected.'),
      '#default_value' => $config->get('warning'),
      '#access' => FALSE,
    );
    $form['redirect_default_status_code'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default redirect status'),
      '#description' => $this->t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', array('@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection')),
      '#options' => redirect_status_code_options(),
      '#default_value' => $config->get('default_status_code'),
    );
    $form['globals'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Global redirects'),
      '#description' => $this->t('(formerly Global Redirect features)'),
    );
    $form['globals']['redirect_route_normalizer_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce clean and canonical URLs.'),
      '#description' => $this->t('Enabling this will automatically redirect to the canonical URL of any page. That includes redirecting to an alias if existing, removing trainling slashes, ensure the language prefix is set and similar clean-up.'),
      '#default_value' => $config->get('route_normalizer_enabled'),
    );
    $form['globals']['redirect_canonical'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect from non-canonical URLs to the canonical URLs.'),
      '#default_value' => $config->get('canonical'),
    );
    $form['globals']['redirect_ignore_admin_path'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow redirections on admin paths.'),
      '#default_value' => $config->get('ignore_admin_path'),
    );
    $form['globals']['redirect_access_check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check access to the redirected page'),
      '#description' => $this->t('This helps to stop redirection on protected pages and avoids giving away <em>secret</em> URL\'s. <strong>By default this feature is disabled to avoid any unexpected behavior</strong>'),
      '#default_value' => $config->get('access_check'),
    );

    $form['globals']['redirect_content_location_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Set Content Location Header'),
      '#description' => $this->t('If enabled, will add a <a href=":canonical">Content-Location</a> header.', array(':canonical' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.14')),
      '#default_value' => $config->get('content_location_header'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('redirect.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'redirect_') !== FALSE) {
        $config->set(str_replace('redirect_', '', $key), $value);
      }
    }
    $config->save();
    drupal_set_message(t('Configuration was saved.'));
  }

}
