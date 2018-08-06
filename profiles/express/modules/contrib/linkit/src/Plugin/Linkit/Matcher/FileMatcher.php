<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Matcher\FileMatcher.
 */

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\linkit\Utility\LinkitXss;

/**
 * @Matcher(
 *   id = "entity:file",
 *   target_entity = "file",
 *   label = @Translation("File"),
 *   provider = "file"
 * )
 */
class FileMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summery = parent::getSummary();

    $summery[] = $this->t('Show image dimensions: @show_image_dimensions', [
      '@show_image_dimensions' => $this->configuration['images']['show_dimensions'] ? $this->t('Yes') : $this->t('No'),
    ]);

    $summery[] = $this->t('Show image thumbnail: @show_image_thumbnail', [
      '@show_image_thumbnail' => $this->configuration['images']['show_thumbnail'] ? $this->t('Yes') : $this->t('No'),
    ]);

    if ($this->moduleHandler->moduleExists('image') && $this->configuration['images']['show_thumbnail']) {
      $image_style = ImageStyle::load($this->configuration['images']['thumbnail_image_style']);
        if (!is_null($image_style)) {
          $summery[] = $this->t('Thumbnail style: @thumbnail_style', [
          '@thumbnail_style' =>  $image_style->label(),
        ]);
      }
    }

    return $summery;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'images' => [
        'show_dimensions' => FALSE,
        'show_thumbnail' => FALSE,
        'thumbnail_image_style' => 'linkit_result_thumbnail',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies() + [
      'module' => ['file'],
    ];

    if ($this->configuration['images']['show_thumbnail']) {
      $dependencies['module'][] = 'image';
      $dependencies['config'][] = 'image.style.' . $this->configuration['images']['thumbnail_image_style'];
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['images'] = array(
      '#type' => 'details',
      '#title' => t('Image file settings'),
      '#description' => t('Extra settings for image files in the result.'),
      '#tree' => TRUE,
    );

    $form['images']['show_dimensions'] = [
      '#title' => t('Show pixel dimensions'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['images']['show_dimensions'],
    ];

    if ($this->moduleHandler->moduleExists('image')) {
      $form['images']['show_thumbnail'] = [
        '#title' => t('Show thumbnail'),
        '#type' => 'checkbox',
        '#default_value' => $this->configuration['images']['show_thumbnail'],
      ];

      $form['images']['thumbnail_image_style'] = [
        '#title' => t('Thumbnail image style'),
        '#type' => 'select',
        '#default_value' => $this->configuration['images']['thumbnail_image_style'],
        '#options' => image_style_options(FALSE),
        '#states' => [
          'visible' => [
            ':input[name="images[show_thumbnail]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue('images');
    if (!$values['show_thumbnail']) {
      $values['thumbnail_image_style'] = NULL;
    }

    $this->configuration['images'] = $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match) {
    $query = parent::buildEntityQuery($match);
    $query->condition('status', FILE_STATUS_PERMANENT);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDescription($entity) {
    $description_array = array();

    $description_array[] = parent::buildDescription($entity);

    /** @var \Drupal\file\FileInterface $entity */
    $file = $entity->getFileUri();

    /** @var \Drupal\Core\Image\ImageInterface $image */
    $image = \Drupal::service('image.factory')->get($file);
    if ($image->isValid()) {
      if ($this->configuration['images']['show_dimensions']) {
        $description_array[] = $image->getWidth() . 'x' . $image->getHeight() . 'px';
      }

      if ($this->configuration['images']['show_thumbnail'] && $this->moduleHandler->moduleExists('image')) {
        $image_element = array(
          '#weight' => -10,
          '#theme' => 'image_style',
          '#style_name' => $this->configuration['images']['thumbnail_image_style'],
          '#uri' => $entity->getFileUri(),
        );

        $description_array[] = (string) \Drupal::service('renderer')->render($image_element);
      }
    }

    $description = implode('<br />' , $description_array);
    return LinkitXss::descriptionFilter($description);
  }

  /**
   * {@inheritdoc}
   *
   * The file entity still uses url() even though it's deprecated in the
   * entity interface.
   */
  protected function buildPath($entity) {
    /** @var \Drupal\file\FileInterface $entity */
    return file_url_transform_relative(file_create_url($entity->getFileUri()));
  }
}
