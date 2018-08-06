<?php

/**
 * @file
 * Theme functions for the media_entity module.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;

/**
 * Prepares variables for list of available media bundles.
 *
 * Default template: media-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of content types.
 */
function template_preprocess_media_add_list(&$variables) {
  $variables['bundles'] = [];
  if (!empty($variables['content'])) {
    foreach ($variables['content'] as $bundle) {
      /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
      $variables['bundles'][$bundle->id()] = [
        'type' => $bundle->id(),
        'add_link' => Link::createFromRoute($bundle->label(), 'media.add', ['media_bundle' => $bundle->id()]),
        'description' => [
          '#markup' => $bundle->getDescription(),
        ],
      ];
    }
  }
}

/**
 * Prepares variables for media templates.
 *
 * Default template: media.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - media: An individual media for display.
 */
function template_preprocess_media(&$variables) {
  /** @var \Drupal\media_entity\MediaInterface $media */
  $media = $variables['elements']['#media'];

  $variables['name'] = $media->label();

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['attributes']['class'][] = 'media';
  $variables['attributes']['class'][] = Html::getClass('media-' . $media->bundle());
  if (!$media->isPublished()) {
    $variables['attributes']['class'][] = 'unpublished';
  }
  if ($variables['elements']['#view_mode']) {
    $variables['attributes']['class'][] = Html::getClass('view-mode-' . $variables['elements']['#view_mode']);
  }
}
