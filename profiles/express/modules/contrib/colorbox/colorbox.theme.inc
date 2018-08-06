<?php

/**
 * @file
 * Colorbox theme functions.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Unicode;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Prepares variables for colorbox formatter templates.
 *
 * Default template: colorbox-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - entity: An entity object.
 *   - settings: Formatter settings array.
 */
function template_preprocess_colorbox_formatter(&$variables) {
  static $gallery_token = NULL;

  $item = $variables['item'];
  $item_attributes = isset($variables['item_attributes']) ? $variables['item_attributes'] : [];
  $entity = $variables['entity'];
  $settings = $variables['settings'];
  $image_uri = $item->entity->getFileUri();
  $classes_array = ['colorbox'];
  $data_cbox_img_attrs = [];

  // Build the caption.
  $entity_title = $entity->label();
  $entity_type = $entity->getEntityTypeId();
  $entity_bundle = $entity->bundle();

  switch ($settings['colorbox_caption']) {
    case 'auto':
      // If the title is empty use alt or the entity title in that order.
      if (!empty($item->title)) {
        $caption = $item->title;
      }
      elseif (!empty($item->alt)) {
        $caption = $item->alt;
      }
      elseif (!empty($entity_title)) {
        $caption = $entity_title;
      }
      else {
        $caption = '';
      }
      break;

    case 'title':
      $caption = $item->title;
      break;

    case 'alt':
      $caption = $item->alt;
      break;

    case 'entity_title':
      $caption = $entity_title;
      break;

    case 'custom':
      $token_service = \Drupal::token();
      $caption = $token_service->replace($settings['colorbox_caption_custom'], [$entity_type => $entity, 'file' => $item], ['clear' => TRUE]);
      break;

    default:
      $caption = '';
  }

  // Shorten the caption for the example styles or when caption
  // shortening is active.
  $config = \Drupal::config('colorbox.settings');
  $colorbox_style = $config->get('colorbox_style');
  $trim_length = $config->get('colorbox_caption_trim_length');
  if (((strpos($colorbox_style, 'colorbox/example') !== FALSE) || $config->get('colorbox_caption_trim')) && (Unicode::strlen($caption) > $trim_length)) {
    $caption = Unicode::substr($caption, 0, $trim_length - 5) . '...';
  }

  // Build the gallery id.
  $id = $entity->id();
  $entity_id = !empty($id) ? $entity_bundle . '-' . $id : 'entity-id';
  $field_name = $item->getParent()->getName();

  switch ($settings['colorbox_gallery']) {
    case 'post':
      $gallery_id = 'gallery-' . $entity_id;
      break;

    case 'page':
      $gallery_id = 'gallery-all';
      break;

    case 'field_post':
      $gallery_id = 'gallery-' . $entity_id . '-' . $field_name;
      break;

    case 'field_page':
      $gallery_id = 'gallery-' . $field_name;
      break;

    case 'custom':
      $token_service = \Drupal::token();
      $gallery_id = $token_service->replace($settings['colorbox_gallery_custom'], [$entity_type => $entity, 'file' => $item], ['clear' => TRUE]);
      break;

    default:
      $gallery_id = '';
  }

  // If gallery id is not empty add unique per-request token to avoid
  // images being added manually to galleries.
  if (!empty($gallery_id) && $config->get('advanced.unique_token')) {
    // Check if gallery token has already been set, we need to reuse
    // the token for the whole request.
    if (is_null($gallery_token)) {
      // We use a short token since randomness is not critical.
      $gallery_token = Crypt::randomBytesBase64(8);
    }
    $gallery_id = $gallery_id . '-' . $gallery_token;
  }

  // Set up the $variables['image'] parameter.
  if ($settings['style_name'] == 'hide') {
    $variables['image'] = [];
    $classes_array[] = 'js-hide';
  }
  elseif (!empty($settings['style_name'])) {
    $variables['image'] = [
      '#theme' => 'image_style',
      '#style_name' => $settings['style_name'],
    ];
  }
  else {
    $variables['image'] = [
      '#theme' => 'image',
    ];
  }

  if (!empty($variables['image'])) {
    $variables['image']['#attributes'] = $item_attributes;

    // Do not output an empty 'title' attribute.
    if (Unicode::strlen($item->title) != 0) {
      $variables['image']['#title'] = $item->title;
      $data_cbox_img_attrs['title'] = '"title":"' . $item->title . '"';
    }

    foreach (['width', 'height', 'alt'] as $key) {
      $variables['image']["#$key"] = $item->$key;
      if ($key == 'alt') {
        $data_cbox_img_attrs['alt'] = '"alt":"' . $item->alt . '"';
      }
    }

    $variables['image']['#uri'] = empty($item->uri) ? $image_uri : $item->uri;
  }

  if (!empty($settings['colorbox_image_style'])) {
    $style = ImageStyle::load($settings['colorbox_image_style']);
    $variables['url'] = $style->buildUrl($image_uri);
  }
  else {
    $variables['url'] = file_create_url($image_uri);
  }

  // If File Entity module is enabled, load attribute values from file entity.
  if (\Drupal::moduleHandler()->moduleExists('file_entity')) {
    // File id of the save file.
    $fid = $item->target_id;
    // Load file object.
    $file_obj = File::load($fid);
    $file_array = $file_obj->toArray();
    // Populate the image title.
    if (!empty($file_array['field_image_title_text'][0]['value']) && empty($item->title) && $settings['colorbox_caption'] == 'title') {
      $caption = $file_array['field_image_title_text'][0]['value'];
    }
    // Populate the image alt text.
    if (!empty($file_array['field_image_alt_text'][0]['value']) && empty($item->alt) && $settings['colorbox_caption'] == 'alt') {
      $caption = $file_array['field_image_alt_text'][0]['value'];
    }
  }

  $variables['attributes']['title'] = $caption;
  $variables['attributes']['data-colorbox-gallery'] = $gallery_id;
  $variables['attributes']['class'] = $classes_array;
  if (!empty($data_cbox_img_attrs)) {
    $variables['attributes']['data-cbox-img-attrs'] = '{' . implode(',', $data_cbox_img_attrs) . '}';
  }
}
