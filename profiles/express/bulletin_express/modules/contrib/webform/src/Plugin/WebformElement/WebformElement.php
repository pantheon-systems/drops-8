<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformElementBase;
use Drupal\Core\Url as UrlGenerator;

/**
 * Provides a 'generic' element. Used as a fallback.
 *
 * @WebformElement(
 *   id = "webform_element",
 *   label = @Translation("Generic element"),
 *   description = @Translation("Provides a generic form element."),
 * )
 */
class WebformElement extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['element'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['custom']['#title'] = $this->t('Element settings');
    $form['custom']['custom']['#title'] = $this->t('Properties');

    // Add link to theme API documentation.
    $theme = (isset($this->configuration['#theme'])) ? $this->configuration['#theme'] : '';
    if (function_exists('template_preprocess_' . $theme)) {
      $t_args = [
        ':href' => UrlGenerator::fromUri('https://api.drupal.org/api/drupal/core!includes!theme.inc/function/template_preprocess_' . $theme)->toString(),
        '%label' => $theme,
      ];
      $form['custom']['#description'] = $this->t('Read the the %label template\'s <a href=":href">API documentation</a>.', $t_args);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
