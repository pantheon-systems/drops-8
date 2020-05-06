<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_markup' element.
 *
 * @WebformElement(
 *   id = "webform_markup",
 *   default_key = "markup",
 *   label = @Translation("Basic HTML"),
 *   description = @Translation("Provides an element to render basic HTML markup."),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformMarkup extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'wrapper_attributes' => [],
      // Markup settings.
      'markup' => '',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($element['#markup'])) {
      $element['#markup'] = MailFormatHelper::htmlToText($element['#markup']);
    }
    return parent::buildText($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup']['markup'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('HTML markup'),
      '#description' => $this->t('Enter custom HTML into your webform.'),
    ];
    return $form;
  }

}
