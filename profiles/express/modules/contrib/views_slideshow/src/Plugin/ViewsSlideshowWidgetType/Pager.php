<?php

namespace Drupal\views_slideshow\Plugin\ViewsSlideshowWidgetType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_slideshow\ViewsSlideshowWidgetTypeBase;

/**
 * Provides a pager widget type.
 *
 * @ViewsSlideshowWidgetType(
 *   id = "views_slideshow_pager",
 *   label = @Translation("Pager"),
 *   accepts = {
 *     "transitionBegin" = {"required" = TRUE},
 *     "goToSlide",
 *     "previousSlide",
 *     "nextSlide"
 *   },
 *   calls = {"goToSlide", "pause", "play"}
 * )
 */
class Pager extends ViewsSlideshowWidgetTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $options = parent::defaultConfiguration() + [
      'type' => ['default' => 0],
      'views_slideshow_pager_numbered_hover' => ['default' => 0],
      'views_slideshow_pager_numbered_click_to_page' => ['default' => 0],
      'views_slideshow_pager_thumbnails_hover' => ['default' => 0],
      'views_slideshow_pager_thumbnails_click_to_page' => ['default' => 0],
    ];

    /* @var \Drupal\Component\Plugin\PluginManagerInterface */
    $widgetManager = \Drupal::service('plugin.manager.views_slideshow.widget');

    // Get default configuration of all Pager plugins.
    foreach ($widgetManager->getDefinitions($this->getPluginId()) as $widget_id => $widget_info) {
      $options += $widgetManager->createInstance($widget_id, [])->defaultConfiguration();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $view = $form_state->get('view')->get('executable');

    /* @var \Drupal\Component\Plugin\PluginManagerInterface */
    $widgetManager = \Drupal::service('plugin.manager.views_slideshow.widget');

    // Determine if this widget type is compatible with any slideshow type.
    $widgets = [];
    foreach ($widgetManager->getDefinitions($this->getPluginId()) as $widget_id => $widget_info) {
      if ($widgetManager->createInstance($widget_id, [])->checkCompatiblity($view)) {
        $widgets[$widget_id] = $widget_info['label'];
      }
    }

    if (!empty($widgets)) {

      // Need to wrap this so it indents correctly.
      $form['views_slideshow_pager_wrapper'] = [
        '#markup' => '<div class="vs-dependent">',
      ];

      // Create the widget type field.
      $form['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Pager Type'),
        '#description' => $this->t('Style of the pager'),
        '#default_value' => $this->getConfiguration()['type'],
        '#options' => $widgets,
        '#states' => [
          'visible' => [
            ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      foreach ($widgetManager->getDefinitions() as $widget_id => $widget_info) {
        // Get the current configuration of this widget.
        $configuration = [];
        if (!empty($this->getConfiguration()[$widget_id])) {
          $configuration = $this->getConfiguration()[$widget_id];
        }
        $configuration['dependency'] = $this->getConfiguration()['dependency'];
        $configuration['view'] = $view;
        $instance = $widgetManager->createInstance($widget_id, $configuration);

        // Get the configuration form of this widget type.
        $form[$widget_id] = isset($form[$widget_id]) ? $form[$widget_id] : [];
        $form[$widget_id] = $instance->buildConfigurationForm($form[$widget_id], $form_state);
      }

      $form['views_slideshow_pager_wrapper_close'] = [
        '#markup' => '</div>',
      ];
    }
    else {
      $form['enable_pager'] = [
        '#markup' => 'There are no pagers available.',
      ];
    }

    return $form;
  }

}
