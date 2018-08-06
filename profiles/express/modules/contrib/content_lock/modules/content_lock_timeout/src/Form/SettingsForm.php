<?php

namespace Drupal\content_lock_timeout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class SettingsForm.
 *
 * @package Drupal\content_lock_timeout\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_lock_timeout.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_lock_timeout_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_lock_timeout.settings');

    $form['content_lock_timeout'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Lock Timeouts'),
      '#description' => $this->t('Configure automatic stale lock breaking.'),
    ];

    $form['content_lock_timeout']['content_lock_timeout_minutes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lock timeout'),
      '#description' => $this->t('The maximum time in minutes that each lock may be kept. To disable breaking locks after a timeout, please %disable the Content Lock Timeout module.', ['%disable' => Link::fromTextAndUrl($this->t('disable'), Url::fromRoute('system.modules_list'))->toString()]),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('content_lock_timeout_minutes'),
    ];

    $form['content_lock_timeout']['content_lock_timeout_on_edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Break stale locks on edit'),
      '#description' => $this->t("By default, stale locks will be broken when cron is run. This option enables checking for stale locks when a user clicks a node's Edit link, enabling lower lock timeout values without having to run cron every few minutes."),
      '#default_value' => $config->get('content_lock_timeout_on_edit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('content_lock_timeout.settings')
      ->set('content_lock_timeout', $form_state->getValue('content_lock_timeout'))
      ->set('content_lock_timeout_minutes', $form_state->getValue('content_lock_timeout_minutes'))
      ->set('content_lock_timeout_on_edit', $form_state->getValue('content_lock_timeout_on_edit'))
      ->save();
  }

}
