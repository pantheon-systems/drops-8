<?php

namespace Drupal\webform_attachment\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a 'webform_attachment_twig' element.
 *
 * @WebformElement(
 *   id = "webform_attachment_twig",
 *   label = @Translation("Attachment Twig"),
 *   description = @Translation("Generates an attachment using Twig."),
 *   category = @Translation("File attachment elements"),
 * )
 */
class WebformAttachmentTwig extends WebformAttachmentBase {

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
      '#mode' => 'twig',
      '#title' => $this->t('Twig'),
    ];
    $form['attachment']['help'] = WebformTwigExtension::buildTwigHelp();
    WebformElementHelper::setPropertyRecursive($form['attachment']['help'], '#access', TRUE);

    return $form;
  }

}
