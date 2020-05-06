<?php

namespace Drupal\webform_image_select\Plugin\WebformElement;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Select;
use Drupal\webform_image_select\Element\WebformImageSelect as WebformImageSelectElement;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_image_select\Entity\WebformImageSelectImages;

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
  protected function defineDefaultProperties() {
    $properties = [
      'images' => [],
      'images_randomize' => FALSE,
      'show_label' => FALSE,
      'filter' => FALSE,
      'filter__placeholder' => (string) $this->t('Filter images by label'),
      'filter__singlular' => (string) $this->t('image'),
      'filter__plural' => (string) $this->t('images'),
      'filter__no_results' => (string) $this->t('No images found.'),
    ] + parent::defineDefaultProperties();
    unset(
      $properties['options'],
      $properties['options_randomize'],
      $properties['field_prefix'],
      $properties['field_suffix'],
      $properties['disabled'],
      $properties['select2']
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), [
      'images',
      'filter__placeholder',
      'filter__singlular',
      'filter__plural',
      'filter__no_results',
    ]);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Set element images.
    $element['#images'] = WebformImageSelectImages::getElementImages($element);

    WebformImageSelectElement::setOptions($element);

    parent::initialize($element);
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
          '#alt' => $element['#images'][$value]['text'],
        ];

        // Suppress all image size errors.
        if ($image_size = @getimagesize($element['#images'][$value]['src'])) {
          $image['#width'] = $image_size[0];
          $image['#height'] = $image_size[1];
        }

        // For the Results table always just return the image with tooltip.
        if (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.results_submissions') !== FALSE) {
          $image['#attached']['library'][] = 'webform/webform.tooltip';
          $image['#attributes']['class'] = ['js-webform-tooltip-link'];
          return $image;
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
      '#title' => $this->t('Images'),
      '#type' => 'webform_image_select_element_images',
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
      '#description' => $this->t('If checked, the image text will be displayed below each image.'),
      '#return_value' => TRUE,
    ];
    $form['options']['filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include filter by label'),
      '#description' => $this->t('If checked, users will be able search/filter images by their labels.'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[show_label]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['options']['filter_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="properties[filter]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['options']['filter_container']['filter__placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter placeholder label'),
    ];
    $form['options']['filter_container']['filter__singlular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter single item label'),
    ];
    $form['options']['filter_container']['filter__plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter plural items label'),
    ];
    $form['options']['filter_container']['filter__no_results'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter no results label'),
    ];
    return $form;
  }

}
