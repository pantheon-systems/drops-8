<?php

namespace Drupal\webform_attachment\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'webform_attachment_token' element.
 *
 * @WebformElement(
 *   id = "webform_attachment_token",
 *   label = @Translation("Attachment token"),
 *   description = @Translation("Generates an attachment using tokens."),
 *   category = @Translation("File attachment elements"),
 * )
 */
class WebformAttachmentToken extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'template' => '',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['attachment']['template'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'text',
      '#title' => $this->t('Template'),
    ];
    return $form;
  }

}
