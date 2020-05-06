<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'item' element.
 *
 * @WebformElement(
 *   id = "webform_more",
 *   label = @Translation("More"),
 *   description = @Translation("Provides a more slideout element."),
 *   category = @Translation("Markup elements"),
 * )
 */
class WebformMore extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'more_title' => \Drupal::config('webform.settings')->get('element.default_more_title'),
      'more' => '',
      'attributes' => [],
      // Markup settings.
      'display_on' => static::DISPLAY_ON_FORM,
    ] + $this->defineDefaultBaseProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup']['#title'] = $this->t('Webform settings');
    $form['markup']['more_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('More title'),
      '#description' => $this->t('The click-able label used to open and close more text.'),
      '#required' => TRUE,
    ];
    $form['markup']['more'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('More text'),
      '#description' => $this->t('A long description of the element that provides form additional information which can opened and closed.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#more' => 'This is more content',
    ];
  }

}
