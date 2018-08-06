<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform to inject CSS and JS assets.
 */
class WebformEntityAssetsForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    $form['css'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom CSS'),
      '#open' => TRUE,
    ];
    $form['css']['css'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'css',
      '#title' => $this->t('CSS'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Enter custom CSS to be attached to the webform.'),
      '#default_value' => $webform->getCss(),
    ];
    $form['javascript'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom JavaScript'),
      '#open' => TRUE,
    ];
    $form['javascript']['javascript'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'javascript',
      '#title' => $this->t('JavaScript'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Enter custom JavaScript to be attached to the webform.'),
      '#default_value' => $webform->getJavaScript(),
    ];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $webform->setCss($form_state->getValue('css'));
    $webform->setJavaScript($form_state->getValue('javascript'));
    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'assets-form')->toString()
    ];
    $this->logger('webform')->notice('Webform assets for @label saved.', $context);

    drupal_set_message($this->t('Webform assets for %label saved.', ['%label' => $webform->label()]));
  }

}
