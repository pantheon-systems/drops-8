<?php

namespace Drupal\webform_image_select\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Utility\WebformElementHelper;

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
    $info['#filter'] = FALSE;
    $info['#filter__placeholder'] = NULL;
    $info['#filter__singular'] = NULL;
    $info['#filter__plural'] = NULL;
    $info['#filter__no_result'] = NULL;
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

    // Add label filter.
    if ($element['#show_label'] && $element['#filter']) {
      $field_prefix = (isset($element['#field_prefix'])) ? $element['#field_prefix'] : NULL;

      $wrapper_class = 'js-' . Html::getClass($element['#name'] . '-filter');
      $element['#wrapper_attributes']['class'][] = $wrapper_class;
      $singular = (!empty($element['#filter__singular'])) ? $element['#filter__singular'] : t('image');
      $plural = (!empty($element['#filter__plural'])) ? $element['#filter__plural'] : t('images');
      $count = count($element['#images']);
      $element['#field_prefix'] = [
        'filter' => [
          '#type' => 'search',
          '#id' => $element['#id'] . '-filter',
          '#name' => $element['#name'] . '_filter',
          '#title' => t('Filter'),
          '#title_display' => 'invisible',
          '#size' => 30,
          '#placeholder' => (!empty($element['#filter__placeholder'])) ? $element['#filter__placeholder'] : t('Filter images by label'),
          '#attributes' => [
            'class' => ['webform-form-filter-text'],
            'data-focus' => 'false',
            'data-item-singlular' => $singular,
            'data-item-plural' => $plural,
            'data-summary' => ".$wrapper_class .webform-image-select-summary",
            'data-no-results' => ".$wrapper_class .webform-image-select-no-results",
            'data-element' => ".$wrapper_class .thumbnails",
            'data-source' => ".thumbnail p",
            'data-parent' => 'li',
            'data-selected' => '.selected',
            'title' => t('Enter a keyword to filter by.'),
          ],
          '#wrapper_attributes' => ['class' => ['webform-image-select-filter']],
          '#field_suffix' => [
            'info' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#attributes' => ['class' => ['webform-image-select-summary']],
              'content' => [
                '#markup' => t('@count @items', [
                  '@count' => $count,
                  '@items' => ($count === 1) ? $singular : $plural,
                ]),
              ],
            ],
            'no_results' => [
              '#type' => 'webform_message',
              '#attributes' => ['style' => 'display:none', 'class' => ['webform-image-select-no-results']],
              '#message_message' => (!empty($element['#filter__no_results'])) ? $element['#filter__no_results'] : t('No images found.'),
              '#message_type' => 'info',
            ],
          ],
        ],
      ];

      if ($field_prefix) {
        $element['#field_prefix']['field_prefix'] = (is_array($element['#field_prefix']))
          ? $element['#field_prefix']
          : ['#markup' => $element['#field_prefix']];
      }
    }

    // Serialize images as JSON to 'data-images' attributes.
    $element['#attributes']['data-images'] = Json::encode($element['#images']);

    // Add classes.
    $element['#attributes']['class'][] = 'webform-image-select';
    $element['#attributes']['class'][] = 'js-webform-image-select';

    // Attach library.
    $element['#attached']['library'][] = 'webform_image_select/webform_image_select.element';
    if ($element['#show_label'] && $element['#filter']) {
      $element['#attached']['library'][] = 'webform/webform.filter';
    }

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
      $element['#images'] = WebformElementHelper::randomize($element['#images']);
    }

    // Convert #images to #options and make sure images are keyed by value.
    if (empty($element['#options'])) {
      $options = [];
      foreach ($element['#images'] as $value => &$image) {
        if (isset($image['text'])) {
          // Apply XSS filter to image text.
          $image['text'] = WebformHtmlEditor::stripTags($image['text']);
          // Strip all HTML tags from the option.
          $options[$value] = strip_tags($image['text']);
        }
        else {
          $options[$value] = $value;
        }
      }
      $element['#options'] = $options;
    }
  }

}
