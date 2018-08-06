<?php

namespace Drupal\fitvids\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Fitvids settings for this site.
 */
class FitvidsAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fitvids_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fitvids.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fitvids.settings');
    
    $form['fitvids_intro'] = array(
      '#markup' => '<p>FitVids is a jQuery plugin for fluid width video embeds. By default, it supports YouTube, Vimeo and Kickstarter.</p>',
    );
    
    $form['fitvids_selectors'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Video containers'),
      '#default_value' => $config->get('selectors'),
      '#rows' => 3,
      '#description' => $this->t('Enter some jQuery selectors for your video containers. Use a new line for each selector.'),
      '#required' => TRUE,
    );

    $form['fitvids_custom_vendors'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Additional video providers'),
      '#default_value' => $config->get('custom_vendors'),
      '#rows' => 3,
      '#description' => $this->t('YouTube, Vimeo, Blip.tv, Viddler and Kickstarter are supported by default. Tell FitVids about videos from other sites by adding the domain of the player. Use a new line for each URL. Don\'t add trailing slashes.<br />E.g., "http://www.dailymotion.com".'),
    );

    $form['fitvids_ignore_selectors'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Ignore these videos'),
      '#default_value' => $config->get('ignore_selectors'),
      '#rows' => 3,
      '#description' => $this->t('Enter some jQuery selectors for any videos or containers that you want to ignore. Use a new line for each selector.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fitvids.settings');
    $config
      ->set('selectors', $form_state->getValue('fitvids_selectors'))
      ->set('custom_vendors', $form_state->getValue('fitvids_custom_vendors'))
      ->set('ignore_selectors', $form_state->getValue('fitvids_ignore_selectors'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
