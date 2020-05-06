<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides a webform element for entering a signature.
 *
 * @FormElement("webform_signature")
 */
class WebformSignature extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformSignature'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformSignature'],
      ],
      '#theme' => 'input__webform_signature',
      '#theme_wrappers' => ['form_element'],
      // Add '#markup' property to add an 'id' attribute to the form element.
      // @see template_preprocess_form_element()
      '#markup' => '',
    ];
  }

  /**
   * Processes a signature webform element.
   */
  public static function processWebformSignature(&$element, FormStateInterface $form_state, &$complete_form) {
    // Remove 'for' from the element's label.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformSignature']);

    return $element;
  }

  /**
   * Prepares a #type 'webform_signature' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #attributes,
   *   #step.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderWebformSignature(array $element) {
    $element['#attributes']['type'] = 'hidden';
    Element::setAttributes($element, ['name', 'value']);
    static::setAttributes($element, ['js-webform-signature', 'form-webform-signature']);

    $build = [
      '#prefix' => '<div class="js-webform-signature-pad webform-signature-pad">',
      '#suffix' => '</div>',
    ];
    $build['reset'] = [
      '#type' => 'button',
      '#value' => t('Reset'),
    ];
    $build['canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
    ];
    $element['#children'] = $build;

    $element['#attached']['library'][] = 'webform/webform.element.signature';
    return $element;
  }

  /**
  +   * Webform element validation handler for #type 'signature'.
  +   */
  public static function validateWebformSignature(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    if (!static::isSignatureValid($value)) {
      $t_args = ['@title' => isset($element['#title']) ? $element['#title'] : t('Form')];
      $form_state->setError($element, t('@title contains an invalid signature.', $t_args));
    }
  }

  /**
   * Determine that signature PNG is valid.
   *
   * @param string $value
   *   Upload base64 png image.
   *
   * @return bool
   *   TRUE if signature PNG is valid.
   */
  public static function isSignatureValid($value) {
    if (empty($value)) {
      return TRUE;
    }

    // Make sure the signature is a png.
    if (strpos($value, 'data:image/png;base64,') !== 0) {
      return FALSE;
    }

    // Make sure signature's image size can be read.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $temp_image = $file_system->tempnam('temporary://', 'webform_signature_');
    $encoded_image = explode(',', $value)[1];
    $decoded_image = base64_decode($encoded_image);
    file_put_contents($temp_image, $decoded_image);
    $image_size = getimagesize($temp_image);
    if (!$image_size) {
      return FALSE;
    }

    // Make sure signature is not larger than a 500 kb.
    if (filesize($temp_image) > 500000) {
      return FALSE;
    }

    // Make sure the signature contains no colors.
    $image = imagecreatefrompng($temp_image);
    $number_of_colors = imagecolorstotal($image);
    imagedestroy($image);
    if ($number_of_colors > 0) {
      return FALSE;
    }

    return TRUE;
  }

}
