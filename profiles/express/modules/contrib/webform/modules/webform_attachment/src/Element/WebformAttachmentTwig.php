<?php

namespace Drupal\webform_attachment\Element;

use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_attachment_twig' element.
 *
 * @FormElement("webform_attachment_twig")
 */
class WebformAttachmentTwig extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#template' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    $options = [];
    $template = $element['#template'];
    $content = WebformTwigExtension::renderTwigTemplate($webform_submission, $template, $options);
    return (!empty($element['#trim'])) ? trim($content) : $content;
  }

}
