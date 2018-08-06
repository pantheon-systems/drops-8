<?php

/**
 * @file
 * Theme & preprocess functions for the Views Slideshow: cycle module.
 */

use Drupal\Component\Utility\Html;

/**
 * Views Slideshow: Theme the main frame wrapper.
 *
 * @ingroup vss_theme
 */
function template_preprocess_views_slideshow_cycle_main_frame(&$vars) {
  $settings = $vars['settings'];
  $rows = $vars['rows'];
  $vss_id = $vars['vss_id'];

  // Cast the strings into int or bool as necessary.
  $new_settings = [];
  foreach ($settings as $key => $value) {
    if (is_string($value)) {
      $value = str_replace("\n", ' ', $value);

      $value = trim($value);

      if (is_numeric($value)) {
        $value = (int) $value;
      }
      elseif (strtolower($value) == 'true') {
        $value = TRUE;
      }
      elseif (strtolower($value) == 'false') {
        $value = FALSE;
      }
    }

    $new_settings[$key] = $value;
  }

  $num_divs = count($rows);
  if ($settings['items_per_slide_first']) {
    $num_divs -= $settings['items_per_slide_first_number'];
    $num_divs = $num_divs / $settings['items_per_slide'];
    $num_divs += 1;
  }
  else {
    $num_divs = $num_divs / $settings['items_per_slide'];
  }

  $settings = array_merge(
    [
      'num_divs' => ceil($num_divs),
      'id_prefix' => '#views_slideshow_cycle_main_',
      'div_prefix' => '#views_slideshow_cycle_div_',
      'vss_id' => $vss_id,
    ],
    $new_settings
  );

  // We need to go through the current js setting values to make sure the one we
  // want to add is not already there. If it is already there then append -[num]
  // to the id to make it unique.
  $slideshow_count = 1;
  global $_views_slideshow_processedCycles;
  if (is_array($_views_slideshow_processedCycles)) {
    foreach ($_views_slideshow_processedCycles as $processedCycle) {
      if (stristr($processedCycle, '#views_slideshow_cycle_main_' . $vss_id)) {
        $slideshow_count++;
      }
    }
  }

  if ($slideshow_count > 1) {
    $vss_id .= '-' . $slideshow_count;
    $settings['vss_id'] = $vss_id;
  }

  // Load the json2 library.
  if (!empty($settings['advanced_options'])) {
    $vars['#attached']['library'][] = 'views_slideshow_cycle/json2';
  }

  // Load the pause library.
  if (!empty($settings['pause_in_middle'])) {
    $vars['#attached']['library'][] = 'views_slideshow_cycle/jquery_pause';
  }

  // Load jQuery Cycle library.
  $vars['#attached']['library'][] = 'views_slideshow_cycle/jquery_cycle';

  // Load our custom cycle library.
  $vars['#attached']['library'][] = 'views_slideshow_cycle/views_slideshow_cycle';
  $vars['#attached']['drupalSettings']['viewsSlideshowCycle']['#views_slideshow_cycle_main_' . $vss_id] = $settings;

  $_views_slideshow_processedCycles['#views_slideshow_cycle_main_' . $vss_id] = '#views_slideshow_cycle_main_' . $vss_id;

  // Add hover intent library.
  if ($settings['pause']) {
    $vars['#attached']['library'][] = 'views_slideshow/jquery_hoverIntent';
  }

  // Add the slideshow elements.
  $vars['attributes']['class'][] = 'views_slideshow_cycle_teaser_section';

  $vars['items_per_slide'] = $settings['items_per_slide'];

  $items = [];
  $index = 0;
  $vars['rendered_rows'] = [];
  foreach ($rows as $count => $item) {
    $items[] = $item;
    if ($settings['items_per_slide_first'] && empty($vars['rendered_rows'])) {
      if (count($items) == $settings['items_per_slide_first_number'] || $count == (count($rows) - 1)) {
        $vars['rendered_rows'][] = [
          '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_cycle_main_frame_row'),
          '#vss_id' => $vss_id,
          '#items' => $items,
          '#count' => $index,
          '#view' => $vars['view'],
        ];

        $items = [];
        $index++;
      }
    }
    elseif (count($items) == $settings['items_per_slide'] || $count == (count($rows) - 1)) {
      $vars['rendered_rows'][] = [
        '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_cycle_main_frame_row'),
        '#vss_id' => $vss_id,
        '#items' => $items,
        '#count' => $index,
        '#view' => $vars['view'],
      ];

      $items = [];
      $index++;
    }
  }
}

/**
 * Views Slideshow slideshow rows.
 *
 * @ingroup vss_theme
 */
function template_preprocess_views_slideshow_cycle_main_frame_row(&$vars) {
  $current = $vars['count'] + 1;
  $vars['attributes']['class'][] = 'views_slideshow_cycle_slide';
  $vars['attributes']['class'][] = 'views_slideshow_slide views-row-' . $current;

  if ($vars['count']) {
    $vars['attributes']['class'][] = 'views_slideshow_cycle_hidden';
  }
  $vars['attributes']['class'][] = ($vars['count'] % 2) ? 'views-row-even' : 'views-row-odd';

  $vars['rendered_items'] = [];
  foreach ($vars['items'] as $item_count => $item) {
    $vars['rendered_items'][] = [
      '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_cycle_main_frame_row_item'),
      '#item' => $item,
      '#item_count' => $item_count,
      '#count' => $vars['count'],
      '#view' => $vars['view'],
      '#length' => count($vars['view']->result),
    ];
  }
}

/**
 * Views Slideshow slideshow row items.
 *
 * @ingroup vss_theme
 */
function template_preprocess_views_slideshow_cycle_main_frame_row_item(&$vars) {
  $vars['attributes']['class'][] = 'views-row views-row-' . $vars['count'];
  $vars['attributes']['class'][] = ($vars['count'] % 2) ? 'views-row-even' : 'views-row-odd';
  if ($vars['count'] == 0) {
    $vars['attributes']['class'][] = 'views-row-first';
  }
  elseif ($vars['count'] == $vars['length'] - 1) {
    $vars['attributes']['class'][] = 'views-row-last';
  }

  // Support custom row classes.
  if ($row_class = $vars['view']->style_plugin->getRowClass($vars['count'])) {
    $vars['attributes']['class'][] = $row_class;
  }
}

/**
 * Implements hook_preprocess_views_slideshow_pager_fields().
 *
 * @ingroup vss_theme
 */
function _views_slideshow_cycle_preprocess_views_slideshow_pager_fields(&$vars) {
  $slide_count = count($vars['view']->result);
  $options = $vars['view']->style_plugin->options['views_slideshow_cycle'];
  $items_per_slide = $options['items_per_slide'];

  if ($vars['settings']['hide_on_single_slide'] && $slide_count == 1) {
    return;
  }

  $vars['rendered_field_items'] = [];
  for ($slide = 0, $count = 0; $slide < $slide_count; $count++) {
    $rendered_fields = [];
    foreach ($vars['settings']['views_slideshow_pager_fields']['views_slideshow_pager_fields_fields'] as $field => $use) {
      if ($use !== 0 && is_object($vars['view']->field[$field])) {
        $rendered_fields[] = [
          '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_pager_field_field'),
          '#view' => $vars['view'],
          '#label' => $vars['view']->field[$field]->options['label'],
          '#output' => $vars['view']->style_plugin->getField($slide, $field),
          '#css_identifier' => Html::cleanCssIdentifier($vars['view']->field[$field]->field),
        ];
      }
    }
    if (empty($vars['rendered_field_items']) && $options['items_per_slide_first']) {
      $slide += $options['items_per_slide_first_number'];
    }
    elseif ($options['items_per_slide']) {
      $slide += $options['items_per_slide'];
    }
    else {
      $slide += 1;
    }
    $vars['rendered_field_items'][] = [
      '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_pager_field_item'),
      '#vss_id' => $vars['vss_id'],
      '#item' => $rendered_fields,
      '#count' => $count,
      '#location' => $vars['location'],
    ];
  }
}

/**
 * Implements hook_preprocess_views_slideshow_slide_counter().
 *
 * @ingroup vss_theme
 */
function _views_slideshow_cycle_preprocess_views_slideshow_slide_counter(&$vars) {
  $options = $vars['view']->style_plugin->options['views_slideshow_cycle'];
  if ($options['items_per_slide_first']) {
    $slide_count = $vars['slide_count'] - $options['items_per_slide_first_number'];
    $slide_count = $slide_count / $options['items_per_slide'];
    $slide_count += 1;
  }
  else {
    $slide_count = $vars['slide_count'] / $options['items_per_slide'];
  }
  $vars['slide_count'] = ceil($slide_count);
}
