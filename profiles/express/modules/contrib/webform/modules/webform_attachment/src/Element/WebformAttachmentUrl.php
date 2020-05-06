<?php

namespace Drupal\webform_attachment\Element;

use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'webform_attachment_url' element.
 *
 * @FormElement("webform_attachment_url")
 */
class WebformAttachmentUrl extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#url' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    try {
      $url = $element['#url'];
      // URL can contain tokens.
      /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
      $token_manager = \Drupal::service('webform.token_manager');
      $url = $token_manager->replace($url, $webform_submission);
      // Prepend scheme and host to root relative path.
      if (strpos($url, '/') === 0) {
        $url = \Drupal::request()->getSchemeAndHttpHost() . $url;
      }
      $content = (string) \Drupal::httpClient()->get($url)->getBody();
    }
    catch (RequestException $exception) {
      $content = '';
    }
    return (!empty($element['#trim'])) ? trim($content) : $content;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileName(array $element, WebformSubmissionInterface $webform_submission) {
    if (!isset($element['#filename']) && !empty($element['#url'])) {
      $filename = basename($element['#url']);
      /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
      $token_manager = \Drupal::service('webform.token_manager');
      $filename = $token_manager->replace($filename, $webform_submission);
      return $filename;
    }
    else {
      return parent::getFileName($element, $webform_submission);
    }
  }

}
