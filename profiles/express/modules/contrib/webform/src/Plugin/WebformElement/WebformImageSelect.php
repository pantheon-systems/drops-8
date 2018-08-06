<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformImageSelect as WebformImageSelectElement;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'image_select' element.
 *
 * @WebformElement(
 *   id = "webform_image_select",
 *   label = @Translation("Image select"),
 *   description = @Translation("Provides a form element for selecting images."),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformImageSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties();
    unset(
      $properties['options'],
      $properties['options_randomize'],
      $properties['field_prefix'],
      $properties['field_suffix'],
      $properties['disabled'],
      $properties['select2']
    );

    $properties['images'] = [];
    $properties['images_randomize'] = FALSE;
    $properties['show_label'] = FALSE;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['images']);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    WebformImageSelectElement::setOptions($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);
    if ($format === 'image') {
      if (isset($element['#images'][$value]) && isset($element['#images'][$value]['src'])) {
        $src = $element['#images'][$value]['src'];

        // Always use absolute URLs for the src so that it will load via e-mail.
        if (strpos($src, '/') === 0) {
          $src = \Drupal::request()->getSchemeAndHttpHost() . $src;
        }

        $image = [
          '#theme' => 'image',
          // ISSUE:
          // Image src must be an absolute URL so that it can be sent
          // via e-mail but template_preprocess_image() converts the #uri to
          // a root-relative URL.
          // @see template_preprocess_image()
          //
          // SOLUTION:
          // Using 'src' attributes to prevent the #uri from being converted to
          // a root-relative paths.
          '#attributes' => ['src' => $src],
          '#title' => $element['#images'][$value]['text'],
        ];

        // Suppress all image size errors.
        if ($image_size = @getimagesize($element['#images'][$value]['src'])) {
          $image['#width'] = $image_size[0];
          $image['#height'] = $image_size[1];
        }

        $build = [
          '#prefix' => new FormattableMarkup('<figure style="display: inline-block; margin: 0 6px 6px 0; padding: 6px; border: 1px solid #ddd;' . (isset($image['#width']) ? 'width: ' . $image['#width'] . 'px' : '') . '">', []),
          '#suffix' => '</figure>',
          'image' => $image,
        ];

        if (!empty($element['#show_label'])) {
          $build['caption'] = [
            '#markup' => $element['#images'][$value]['text'],
            '#prefix' => '<figcaption>',
            '#suffix' => '</figcaption>',
          ];
        }

        return $build;
      }
      else {
        return $value;
      }
    }
    else {
      return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if ($this->getItemFormat($element) == 'image') {
      $element['#format'] = 'value';
    }
    return parent::formatTextItem($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'image' => $this->t('Image'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'image';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsDefaultFormat() {
    return 'space';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsFormats() {
    return parent::getItemsFormats() + [
      'br' => $this->t('Break'),
      'space' => $this->t('Space'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#show_label' => TRUE,
      '#images' => [
        'bear_1' => [
          'text' => 'Bear 1',
          'src' => 'https://www.placebear.com/80/100',
        ],
        'bear_2' => [
          'text' => 'Bear 2',
          'src' => 'https://www.placebear.com/100/100',
        ],
        'bear_3' => [
          'text' => 'Bear 3',
          'src' => 'https://www.placebear.com/120/100',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['options']['#title'] = $this->t('Image options');
    $form['options']['images'] = [
      '#type' => 'webform_multiple',
      '#key' => 'value',
      '#header' => [
        ['data' => t('Option value'), 'width' => '25%'],
        ['data' => t('Option text'), 'width' => '25%'],
        ['data' => t('Option src'), 'width' => '50%'],
      ],
      '#element' => [
        'value' => [
          '#type' => 'textfield',
          '#title' => t('Option value'),
          '#title_display' => t('invisible'),
          '#placeholder' => t('Enter value'),
        ],
        'text' => [
          '#type' => 'textfield',
          '#title' => t('Option text'),
          '#title_display' => t('invisible'),
          '#placeholder' => t('Enter text'),
        ],
        'src' => [
          '#type' => 'textfield',
          '#title' => t('Option image'),
          '#title_display' => t('invisible'),
          '#placeholder' => t('Enter image src'),
        ],
      ],
      '#weight' => -10,
    ];
    $form['options']['images_randomize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Randomize images'),
      '#description' => $this->t('Randomizes the order of the images when they are displayed in the webform'),
      '#return_value' => TRUE,
    ];
    $form['options']['show_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show labels'),
      '#description' => $this->t('If checked, the text of each option will be added as a paragraph below each image.'),
      '#return_value' => TRUE,
    ];

    if (function_exists('imce_process_url_element')) {
      $src_element = &$form['options']['images']['#element']['src'];
      imce_process_url_element($src_element, 'link');
      $form['#attached']['library'][] = 'webform/imce.input';
    }
    elseif ($this->currentUser->hasPermission('administer modules')) {
      $form['options']['imce_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Install the <a href=":href">IMCE module</a> to manage and uploaded image files.', [':href' => 'https://www.drupal.org/project/imce']),
        '#message_close' => TRUE,
        '#message_id' => 'webform_imce_message',
        '#message_storage' => WebformMessageElement::STORAGE_LOCAL,
        '#weight' => -100,
        '#access' => TRUE,
      ];
    }

    return $form;
  }

}
