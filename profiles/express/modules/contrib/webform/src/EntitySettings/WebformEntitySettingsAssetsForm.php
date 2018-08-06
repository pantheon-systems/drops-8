<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Webform CSS and JS assets.
 */
class WebformEntitySettingsAssetsForm extends WebformEntitySettingsBaseForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    $form['description'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('The below CSS and JavasScript will be loaded on all pages that references and loads this webform.'),
      '#message_type' => 'info',
    ];
    $form['css'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom CSS'),
      '#description' => $this->t('Enter custom CSS to be attached to the webform.'),
    ];
    $form['css']['css'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'css',
      '#title' => $this->t('CSS'),
      '#title_display' => 'invisible',
      '#default_value' => $webform->getCss(),
    ];
    $form['javascript'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom JavaScript'),
      '#description' => $this->t('Enter custom JavaScript to be attached to the webform.'),
    ];
    $form['javascript']['javascript'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'javascript',
      '#title' => $this->t('JavaScript'),
      '#title_display' => 'invisible',
      '#default_value' => $webform->getJavaScript(),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $webform->setCss($form_state->getValue('css'));
    $webform->setJavaScript($form_state->getValue('javascript'));

    // Invalidate library_info cache tag.
    // @see webform_library_info_build()
    Cache::invalidateTags(['library_info']);

    parent::save($form, $form_state);
  }

}
