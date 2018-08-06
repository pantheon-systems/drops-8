<?php

namespace Drupal\webform\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Provides a webform element for a selecting an image.
 *
 * @FormElement("webform_image_select")
 */
class WebformImageSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#images'] = [];
    $info['#images_randomize'] = FALSE;
    $info['#show_label'] = FALSE;
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    // Convert #images to #options.
    static::setOptions($element);

    // Set show label.
    if ($element['#show_label']) {
      $element['#attributes']['data-show-label'] = 'data-show-label';
    }

    // Set limit.
    if ($element['#multiple'] && $element['#multiple'] > 1) {
      $element['#attributes']['data-limit'] = $element['#multiple'];
      $element['#multiple'] = TRUE;
    }

    // Serialize images as JSON to 'data-images' attributes.
    $element['#attributes']['data-images'] = Json::encode($element['#images']);

    // Add classes.
    $element['#attributes']['class'][] = 'webform-image-select';
    $element['#attributes']['class'][] = 'js-webform-image-select';

    // Attach library.
    $element['#attached']['library'][] = 'webform/webform.element.image_select';

    return parent::processSelect($element, $form_state, $complete_form);
  }

  /**
   * Set element #options from #images.
   *
   * @param array $element
   *   A Webform image select element.
   */
  public static function setOptions(array &$element) {
    // Randomize images.
    if (!empty($element['#images_randomize'])) {
      $element['#images'] = array_values(WebformArrayHelper::shuffle($element['#images']));
    }

    // Convert #images to #options and make sure images are keyed by value.
    if (empty($element['#options'])) {
      $options = [];
      foreach ($element['#images'] as $value => &$image) {
        // Apply XSS filter to image text.
        $image['text'] = Xss::filter($image['text']);
        $options[$value] = $image['text'];
      }
      $element['#options'] = $options;
    }
  }

}
