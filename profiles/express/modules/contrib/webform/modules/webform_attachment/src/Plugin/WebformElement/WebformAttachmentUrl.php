<?php

namespace Drupal\webform_attachment\Plugin\WebformElement;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Provides a 'webform_attachment_url' element.
 *
 * @WebformElement(
 *   id = "webform_attachment_url",
 *   label = @Translation("Attachment URL"),
 *   description = @Translation("Generates an attachment using a URL."),
 *   category = @Translation("File attachment elements"),
 * )
 */
class WebformAttachmentUrl extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'url' => '',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['url']);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['attachment']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL/path'),
      '#description' => $this->t("Make sure the attachment URL/Path is publicly accessible. The attachment's URL/path will never be displayed to end users."),
      '#required' => TRUE,
      '#element_validate' => [[get_class($this), 'validateAttachmentUrl']],
    ];
    if (function_exists('imce_process_url_element')) {
      $url_element = &$form['attachment']['url'];
      imce_process_url_element($url_element, 'link');
      $form['#attached']['library'][] = 'webform/imce.input';
    }
    return $form;
  }

  /**
   * Form API callback. Validate url/path.
   *
   * @see \Drupal\Core\Render\Element\Url::validateUrl
   */
  public static function validateAttachmentUrl(&$element, FormStateInterface &$form_state) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    // Prepend scheme and host to root relative path.
    if (strpos($value, '/') === 0) {
      $value = \Drupal::request()->getSchemeAndHttpHost() . $value;
    }

    // Skip validating [webform_submission] tokens which can't be replaced.
    if (strpos($value, '[webform_submission:') !== FALSE) {
      return;
    }

    // Validate URL formatting.
    if ($value !== '' && !UrlHelper::isValid($value, TRUE)) {
      $form_state->setError($element, t('The URL %url is not valid.', ['%url' => $value]));
    }

    // Validate URL access.
    try {
      \Drupal::httpClient()->head($value);
    }
    catch (ClientException $e) {
      $form_state->setError($element, t('The URL <a href=":url">@url</a> is not available.', [':url' => $value, '@url' => $value]));
    }
  }

}
