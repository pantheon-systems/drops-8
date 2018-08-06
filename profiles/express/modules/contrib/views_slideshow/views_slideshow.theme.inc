<?php

/**
 * @file
 * The theme system, which controls the output of views slideshow.
 */

/**
 * @defgroup vss_templates Templates
 * @{
 * Slideshow and component templates.
 *
 * @see vss_theme
 * @}
 */

/**
 * @defgroup vss_theme Theme Functions
 * @{
 * Theme processing and display generation.
 *
 * Most of the logic behind the generation of the slideshow is here.
 *
 * @see vss_templates
 */

use Drupal\Component\Utility\Html;

/**
 * Views Slideshow: slideshow.
 */
function _views_slideshow_preprocess_views_view_slideshow(&$vars) {
  $options = $vars['view']->style_plugin->options;
  $vars['skin'] = 'default';
  $vars['slideshow'] = '';
  $main_frame_module = $options['slideshow_type'];

  if (empty($main_frame_module)) {
    // Get all slideshow types.
    $typeManager = \Drupal::service('plugin.manager.views_slideshow.slideshow_type');
    $types = $typeManager->getDefinitions();

    if ($types) {
      foreach ($types as $id => $definition) {
        $main_frame_module = $id;
        break;
      }
    }
  }

  // Make sure the main slideshow settings are defined before building the
  // slideshow.
  if (empty($main_frame_module)) {
    drupal_set_message(
      t('No main frame module is enabled for views slideshow. This is often because another module which Views Slideshow needs is not enabled. For example, 4.x needs a module like "Views Slideshow: Cycle" enabled.'),
      'error'
    );
  }
  elseif (empty($options[$main_frame_module])) {
    drupal_set_message(t('The options for @module does not exists.', ['@module' => $main_frame_module]), 'error');
  }
  elseif (!empty($vars['rows'])) {
    $settings = $options[$main_frame_module];
    $view = $vars['view'];
    $rows = $vars['rows'];

    // The #name element is not available on Views edit pages.
    $view_element_name = (isset($view->element['#name'])) ? $view->element['#name'] : '';
    $vss_id = $view_element_name . '-' . $view->current_display;

    // Give each slideshow a unique id if there are more than one on the page.
    static $instances = [];
    if (isset($instances[$vss_id])) {
      $instances[$vss_id]++;
      $vss_id .= "_" . $instances[$vss_id];
    }
    else {
      $instances[$vss_id] = 1;
    }

    // Building our default methods.
    $methods = [
      'goToSlide' => [],
      'nextSlide' => [],
      'pause' => [],
      'play' => [],
      'previousSlide' => [],
      'transitionBegin' => [],
      'transitionEnd' => [],
    ];

    // Pull all widget info and slideshow info and merge them together.
    $widgetTypeManager = \Drupal::service('plugin.manager.views_slideshow.widget_type');
    $widgetTypes = $widgetTypeManager->getDefinitions();

    $slideshowTypeManager = \Drupal::service('plugin.manager.views_slideshow.slideshow_type');
    $slideshowTypes = $slideshowTypeManager->getDefinitions();

    $addons = array_merge($widgetTypes, $slideshowTypes);

    // Loop through all the addons and call their methods if needed.
    foreach ($addons as $addon_id => $addon_info) {
      foreach ($addon_info['accepts'] as $imp_key => $imp_value) {
        if (is_array($imp_value)) {
          $methods[$imp_key][] = \Drupal::service('views_slideshow.format_addons_name')->format($addon_id);
        }
        else {
          $methods[$imp_value][] = \Drupal::service('views_slideshow.format_addons_name')->format($addon_id);
        }
      }
    }

    $vars['#attached']['library'][] = 'views_slideshow/widget_info';
    $vars['#attached']['drupalSettings']['viewsSlideshow'][$vss_id] = [
      'methods' => $methods,
      'paused' => 0,
    ];

    // Process Skins.
    $skinManager = \Drupal::service('plugin.manager.views_slideshow.slideshow_skin');
    $skin = $skinManager->createInstance($options['slideshow_skin']);

    $vars['skin'] = $skin->getClass();

    foreach ($skin->getLibraries() as $library) {
      $vars['#attached']['library'][] = $library;
    }

    // Process Widgets.
    // Build weights.
    $weight = [];
    for ($i = 1; $i <= count($widgetTypes); $i++) {
      $weight['top'][$i] = [];
      $weight['bottom'][$i] = [];
    }

    $slide_count = count($view->result);
    if ($slide_count && $vars['view']->style_plugin->options['slideshow_type'] == 'views_slideshow_cycle') {
      $items_per_slide = $vars['view']->style_plugin->options['views_slideshow_cycle']['items_per_slide'];
      $slide_count = $slide_count / $items_per_slide;
    }
    foreach ($widgetTypes as $widgetTypeId => $widgetTypeName) {
      foreach ($weight as $location => $order) {
        if ($options['widgets'][$location][$widgetTypeId]['enable']) {
          // If hide on single slide and only a single slide skip rendering.
          if ($options['widgets'][$location][$widgetTypeId]['hide_on_single_slide'] && $slide_count <= 1) {
            continue;
          }
          $widgetWeight = ($options['widgets'][$location][$widgetTypeId]['weight'] > count($widgetTypes)) ? count($widgetTypes) : $options['widgets'][$location][$widgetTypeId]['weight'];

          $weight[$location][$widgetWeight][] = [
            'widgetId' => $widgetTypeId,
            'widgetSettings' => $options['widgets'][$location][$widgetTypeId],
          ];
        }
      }
    }

    // Build our widgets.
    foreach ($weight as $location => $order) {
      $vars[$location . '_widget_rendered'] = [];
      foreach ($order as $widgets) {
        if (is_array($widgets)) {
          foreach ($widgets as $widgetData) {
            $vars[$location . '_widget_rendered'][] = [
              '#theme' => $view->buildThemeFunctions($widgetData['widgetId'] . '_widget_render'),
              '#vss_id' => $vss_id,
              '#view' => $view,
              '#settings' => $widgetData['widgetSettings'],
              '#location' => $location,
              '#rows' => $rows,
            ];
          }
        }
      }
    }

    // Process Slideshow.
    $slides = [
      '#theme' => $view->buildThemeFunctions($main_frame_module . '_main_frame'),
      '#vss_id' => $vss_id,
      '#view' => $view,
      '#settings' => $settings,
      '#rows' => $rows,
    ];

    $vars['slideshow'] = [
      '#theme' => $view->buildThemeFunctions('views_slideshow_main_section'),
      '#vss_id' => $vss_id,
      '#slides' => $slides,
      '#plugin' => $main_frame_module,
    ];
  }
}

/**
 * Views Slideshow: pager.
 */
function template_preprocess_views_slideshow_pager_widget_render(&$vars) {
  // Add JavaScript settings for the pager type.
  $vars['#attached']['library'][] = 'views_slideshow/widget_info';
  $vars['#attached']['drupalSettings']['viewsSlideshowPager'][$vars['vss_id']] = [
    $vars['location'] => [
      'type' => \Drupal::service('views_slideshow.format_addons_name')->format($vars['settings']['type']),
    ],
  ];

  // Create some attributes.
  $attributes['class'][] = 'widget_pager widget_pager_' . $vars['location'];
  $attributes['id'] = 'widget_pager_' . $vars['location'] . '_' . $vars['vss_id'];

  $pager = [
    '#theme' => $vars['view']->buildThemeFunctions($vars['settings']['type']),
    '#vss_id' => $vars['vss_id'],
    '#view' => $vars['view'],
    '#settings' => $vars['settings'],
    '#location' => $vars['location'],
    '#attributes' => $attributes,
  ];

  return \Drupal::service('renderer')->render($pager);
}

/**
 * Theme pager fields.
 */
function template_preprocess_views_slideshow_pager_fields(&$vars) {
  // Add JavaScript settings for the field.
  $vars['#attached']['library'][] = 'views_slideshow/widget_info';
  $vars['#attached']['drupalSettings']['viewsSlideshowPagerFields'][$vars['vss_id']] = [
    $vars['location'] => [
      'activatePauseOnHover' => $vars['settings']['views_slideshow_pager_fields']['views_slideshow_pager_fields_hover'],
    ],
  ];

  // Add hover intent library.
  if ($vars['settings']['views_slideshow_pager_fields']['views_slideshow_pager_fields_hover']) {
    $vars['#attached']['library'][] = 'views_slideshow/jquery_hoverIntent';
  }

  $vars['widget_id'] = $vars['attributes']['id'];
  // Add our class to the wrapper.
  $vars['attributes']['class'][] = 'views_slideshow_pager_field';

  // Render all the fields unless there is only 1 slide and the user specified
  // to hide them when there is only one slide.
  $vars['rendered_field_items'] = [];
  foreach ($vars['view']->result as $count => $node) {
    $rendered_fields = [];
    foreach ($vars['settings']['views_slideshow_pager_fields']['views_slideshow_pager_fields_fields'] as $field => $use) {
      if ($use !== 0 && is_object($vars['view']->field[$field])) {
        $rendered_fields[] = [
          '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_pager_field_field'),
          '#view' => $vars['view'],
          '#label' => $vars['view']->field[$field]->options['label'],
          '#output' => $vars['view']->style_plugin->getField($count, $field),
          '#css_identifier' => Html::cleanCssIdentifier($vars['view']->field[$field]->field),
        ];
      }
    }

    $vars['rendered_field_items'][] = [
      '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_pager_field_item'),
      '#vss_id' => $vars['vss_id'],
      '#item' => $rendered_fields,
      '#count' => $count,
      '#location' => $vars['location'],
      '#length' => count($vars['view']->result),
    ];
  }
}

/**
 * Views Slideshow: pager item.
 */
function template_preprocess_views_slideshow_pager_field_item(&$vars) {
  $vars['attributes']['class'][] = 'views_slideshow_pager_field_item';
  $vars['attributes']['class'][] = ($vars['count'] % 2) ? 'views-row-even' : 'views-row-odd';
  if ($vars['count'] == 0) {
    $vars['attributes']['class'][] = 'views-row-first';
  }
  elseif ($vars['count'] == $vars['length'] - 1) {
    $vars['attributes']['class'][] = 'views-row-last';
  }
}

/**
 * Views Slideshow: Controls.
 */
function template_preprocess_views_slideshow_controls_widget_render(&$vars) {
  // Add JavaScript settings for the controls type.
  $vars['#attached']['library'][] = 'views_slideshow/widget_info';
  $vars['#attached']['drupalSettings']['viewsSlideshowControls'][$vars['vss_id']] = [
    $vars['location'] => [
      'type' => \Drupal::service('views_slideshow.format_addons_name')->format($vars['settings']['type']),
    ],
  ];

  $output = [
    '#theme' => $vars['view']->buildThemeFunctions($vars['settings']['type']),
    '#vss_id' => $vars['vss_id'],
    '#view' => $vars['view'],
    '#settings' => $vars['settings'],
    '#location' => $vars['location'],
    '#rows' => $vars['rows'],
  ];

  return \Drupal::service('renderer')->render($output);
}

/**
 * The slideshow controls.
 */
function template_preprocess_views_slideshow_controls_text(&$vars) {
  $vars['#attached']['library'][] = 'views_slideshow/controls_text';

  $vars['attributes']['class'][] = 'views_slideshow_controls_text';

  $vars['rendered_control_previous'] = [
    '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_controls_text_previous'),
    '#vss_id' => $vars['vss_id'],
    '#view' => $vars['view'],
    '#settings' => $vars['settings'],
  ];

  $vars['rendered_control_pause'] = [
    '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_controls_text_pause'),
    '#vss_id' => $vars['vss_id'],
    '#view' => $vars['view'],
    '#settings' => $vars['settings'],
  ];

  $vars['rendered_control_next'] = [
    '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_controls_text_next'),
    '#vss_id' => $vars['vss_id'],
    '#view' => $vars['view'],
    '#settings' => $vars['settings'],
  ];
}

/**
 * Views Slideshow: "previous" control.
 */
function template_preprocess_views_slideshow_controls_text_previous(&$vars) {
  $vars['attributes']['class'][] = 'views_slideshow_controls_text_previous';
}

/**
 * Views Slideshow: "pause" control.
 */
function template_preprocess_views_slideshow_controls_text_pause(&$vars) {
  $vars['attributes']['class'][] = 'views_slideshow_controls_text_pause  views-slideshow-controls-text-status-play';
  $vars['start_text'] = t('Pause');
}

/**
 * Views Slideshow: "next" control.
 */
function template_preprocess_views_slideshow_controls_text_next(&$vars) {
  $vars['attributes']['class'][] = 'views_slideshow_controls_text_next';
}

/**
 * Views Slideshow: Slide Counter.
 */
function template_preprocess_views_slideshow_slide_counter_widget_render(&$vars) {
  $slide = [
    '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_slide_counter'),
    '#vss_id' => $vars['vss_id'],
    '#view' => $vars['view'],
    '#settings' => $vars['settings'],
    '#location' => $vars['location'],
    '#rows' => $vars['rows'],
  ];

  return \Drupal::service('renderer')->render($slide);
}

/**
 * Views Slideshow: slide counter.
 */
function template_preprocess_views_slideshow_slide_counter(&$vars) {
  $vars['attributes']['class'][] = 'views_slideshow_slide_counter';
  $vars['slide_count'] = count($vars['rows']);
}

/**
 * @} End of "defgroup vss_theme".
 */
