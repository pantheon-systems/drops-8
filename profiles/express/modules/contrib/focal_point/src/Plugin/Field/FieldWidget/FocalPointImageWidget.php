<?php

namespace Drupal\focal_point\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\crop\Entity\Crop;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'image_focal_point' widget.
 *
 * @FieldWidget(
 *   id = "image_focal_point",
 *   label = @Translation("Image (Focal Point)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class FocalPointImageWidget extends ImageWidget {

  const PREVIEW_TOKEN_NAME = 'focal_point_preview';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'progress_indicator' => 'throbber',
      'preview_image_style' => 'thumbnail',
      'preview_link' => TRUE,
      'offsets' => '50,50',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    // We need a preview image for this widget.
    $form['preview_image_style']['#required'] = TRUE;
    unset($form['preview_image_style']['#empty_option']);
    // @todo Implement https://www.drupal.org/node/2872960
    //   The preview image should not be generated using a focal point effect
    //   and should maintain teh aspect ratio of the original image.
    $form['preview_image_style']['#description'] = t(
      $form['preview_image_style']['#description']->getUntranslatedString() . "<br/>Do not choose an image style that alters the aspect ratio of the original image nor an image style that uses a focal point effect.",
      $form['preview_image_style']['#description']->getArguments(),
      $form['preview_image_style']['#description']->getOptions()
    );

    $form['preview_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display preview link'),
      '#default_value' => $this->getSetting('preview_link'),
      '#weight' => 30,
    ];

    $form['offsets'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default focal point value'),
      '#default_value' => $this->getSetting('offsets'),
      '#description' => $this->t('Specify the default focal point of this widget in the form "leftoffset,topoffset" where offsets are in percentages. Ex: 25,75.'),
      '#size' => 7,
      '#maxlength' => 7,
      '#element_validate' => [[$this, 'validateFocalPointWidget']],
      '#required' => TRUE,
      '#weight' => 35,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $status = $this->getSetting('preview_link') ? $this->t('Yes') : $this->t('No');
    $summary[] = $this->t('Preview link: @status', ['@status' => $status]);

    $offsets = $this->getSetting('offsets');
    $summary[] = $this->t('Default focal point: @offsets', ['@offsets' => $offsets]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#process'][] = [static::class, 'process'];
    $element['#focal_point'] = [
      'preview_link' => $this->getSetting('preview_link'),
      'offsets' => $this->getSetting('offsets'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Processes an image_focal_point field Widget.
   *
   * Expands the image_focal_point Widget to include the focal_point field.
   * This method is assigned as a #process callback in formElement() method.
   *
   * @todo Implement https://www.drupal.org/node/2657592
   *   Convert focal point selector tool into a standalone form element.
   * @todo Implement https://www.drupal.org/node/2848511
   *   Focal Point offsets not accessible by keyboard.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];
    $element_selectors = [
      'focal_point' => 'focal-point-' . implode('-', $element['#parents']),
    ];

    $default_focal_point_value = isset($item['focal_point']) ? $item['focal_point'] : $element['#focal_point']['offsets'];

    // Add the focal point indicator to preview.
    if (isset($element['preview'])) {
      $preview = [
        'indicator' => self::createFocalPointIndicator($element['#delta'], $element_selectors),
        'thumbnail' => $element['preview'],
      ];

      // Even for image fields with a cardinality higher than 1 the correct fid
      // can always be found in $item['fids'][0].
      $fid = isset($item['fids'][0]) ? $item['fids'][0] : '';
      if ($element['#focal_point']['preview_link'] && !empty($fid)) {
        $preview['preview_link'] = self::createPreviewLink($fid, $element['#field_name'], $element_selectors, $default_focal_point_value);
      }

      // Use the existing preview weight value so that the focal point indicator
      // and thumbnail appear in the correct order.
      $preview['#weight'] = isset($element['preview']['#weight']) ? $element['preview']['#weight'] : 0;
      unset($preview['thumbnail']['#weight']);

      $element['preview'] = $preview;
    }

    // Add the focal point field.
    $element['focal_point'] = self::createFocalPointField($element['#field_name'], $element_selectors, $default_focal_point_value);

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    $return = parent::value($element, $input, $form_state);

    // When an element is loaded, focal_point needs to be set. During a form
    // submission the value will already be there.
    if (isset($return['target_id']) && !isset($return['focal_point'])) {
      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::service('entity_type.manager')
        ->getStorage('file')
        ->load($return['target_id']);
      if ($file) {
        $crop_type = \Drupal::config('focal_point.settings')->get('crop_type');
        $crop = Crop::findCrop($file->getFileUri(), $crop_type);
        if ($crop) {
          $anchor = \Drupal::service('focal_point.manager')
            ->absoluteToRelative($crop->x->value, $crop->y->value, $return['width'], $return['height']);
          $return['focal_point'] = "{$anchor['x']},{$anchor['y']}";
        }
      }
      else {
        \Drupal::logger('focal_point')->notice("Attempted to get a focal point value for an invalid or temporary file.");
        $return['focal_point'] = $element['#focal_point']['offsets'];
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * Validation Callback; Focal Point process field.
   */
  public static function validateFocalPoint($element, FormStateInterface $form_state) {
    if (empty($element['#value']) || (FALSE === \Drupal::service('focal_point.manager')->validateFocalPoint($element['#value']))) {
      $replacements = ['@title' => strtolower($element['#title'])];
      $form_state->setError($element, new TranslatableMarkup('The @title field should be in the form "leftoffset,topoffset" where offsets are in percentages. Ex: 25,75.', $replacements));
    }
  }

  /**
   * {@inheritdoc}
   *
   * Validation Callback; Focal Point widget setting.
   */
  public function validateFocalPointWidget(array &$element, FormStateInterface $form_state) {
    static::validateFocalPoint($element, $form_state);
  }

  /**
   * Create and return a token to use for accessing the preview page.
   *
   * @return string
   *   A valid token.
   *
   * @codeCoverageIgnore
   */
  public static function getPreviewToken() {
    return \Drupal::csrfToken()->get(self::PREVIEW_TOKEN_NAME);
  }

  /**
   * Validate a preview token.
   *
   * @param string $token
   *   A drupal generated token.
   *
   * @return bool
   *   True if the token is valid.
   *
   * @codeCoverageIgnore
   */
  public static function validatePreviewToken($token) {
    return \Drupal::csrfToken()->validate($token, self::PREVIEW_TOKEN_NAME);
  }

  /**
   * Create the focal point form element.
   *
   * @param string $field_name
   *   The name of the field element for the image field.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   *
   * @return array The preview link form element.
   *   The preview link form element.
   */
  private static function createFocalPointField($field_name, $element_selectors, $default_focal_point_value) {
    $field = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Focal point'),
      '#description' => new TranslatableMarkup('Specify the focus of this image in the form "leftoffset,topoffset" where offsets are in percents. Ex: 25,75'),
      '#default_value' => $default_focal_point_value,
      '#element_validate' => [[static::class, 'validateFocalPoint']],
      '#attributes' => [
        'class' => ['focal-point', $element_selectors['focal_point']],
        'data-selector' => $element_selectors['focal_point'],
        'data-field-name' => $field_name,
      ],
      '#attached' => [
        'library' => ['focal_point/drupal.focal_point'],
      ],
    ];

    return $field;
  }

  /**
   * Create the focal point form element.
   *
   * @param int $delta
   *   The delta of the image field widget.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   *
   * @return array
   *   The focal point field form element.
   */
  private static function createFocalPointIndicator($delta, $element_selectors) {
    $indicator = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['focal-point-indicator'],
        'data-selector' => $element_selectors['focal_point'],
        'data-delta' => $delta,
      ],
    ];

    return $indicator;
  }

  /**
   * Create the preview link form element.
   *
   * @param int $fid
   *   The fid of the image file.
   * @param string $field_name
   *   The name of the field element for the image field.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   *
   * @return array The preview link form element.
   *   The preview link form element.
   */
  private static function createPreviewLink($fid, $field_name, $element_selectors, $default_focal_point_value) {
    // Replace comma (,) with an x to make javascript handling easier.
    $preview_focal_point_value = str_replace(',', 'x', $default_focal_point_value);

    // Create a token to be used during an access check on the preview page.
    $token = self::getPreviewToken();

    $preview_link = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup('Preview'),
      '#url' => new Url('focal_point.preview',
        [
          'fid' => $fid,
          'focal_point_value' => $preview_focal_point_value,
        ],
        [
          'query' => ['focal_point_token' => $token],
        ]),
      '#attributes' => [
        'class' => ['focal-point-preview-link'],
        'data-selector' => $element_selectors['focal_point'],
        'data-field-name' => $field_name,
        'target' => '_blank',
      ],
    ];

    return $preview_link;
  }


}
