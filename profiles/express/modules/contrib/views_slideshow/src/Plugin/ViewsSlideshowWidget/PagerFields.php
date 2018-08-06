<?php

namespace Drupal\views_slideshow\Plugin\ViewsSlideshowWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_slideshow\ViewsSlideshowWidgetBase;

/**
 * Provides a pager using fields.
 *
 * @ViewsSlideshowWidget(
 *   id = "views_slideshow_pager_fields",
 *   type = "views_slideshow_pager",
 *   label = @Translation("Fields"),
 * )
 */
class PagerFields extends ViewsSlideshowWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'views_slideshow_pager_fields_fields' => ['default' => []],
      'views_slideshow_pager_fields_hover' => ['default' => 0],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Settings for fields pager.
    $options = [];

    // Get each field and it's name.
    foreach ($this->getConfiguration()['view']->display_handler->getHandlers('field') as $field_name => $field) {
      $options[$field_name] = $field->adminLabel();
    }

    // Need to wrap this so it indents correctly.
    $form['views_slideshow_pager_fields_wrapper'] = [
      '#markup' => '<div class="vs-dependent">',
    ];

    // Add ability to choose which fields to show in the pager.
    $form['views_slideshow_pager_fields_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Pager fields'),
      '#options' => $options,
      '#default_value' => $this->getConfiguration()['views_slideshow_pager_fields_fields'],
      '#description' => $this->t('Choose the fields that will appear in the pager.'),
      '#states' => [
        'visible' => [
          ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
          ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' => ['value' => 'views_slideshow_pager_fields'],
        ],
      ],
    ];

    // Add field to see if they would like to activate slide and pause on pager
    // hover.
    $form['views_slideshow_pager_fields_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate Slide and Pause on Pager Hover'),
      '#default_value' => $this->getConfiguration()['views_slideshow_pager_fields_hover'],
      '#description' => $this->t('Should the slide be activated and paused when hovering over a pager item.'),
      '#states' => [
        'visible' => [
          ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
          ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' => ['value' => 'views_slideshow_pager_fields'],
        ],
      ],
    ];

    $form['views_slideshow_pager_fields_wrapper_close'] = [
      '#markup' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCompatiblity($view) {
    return $view->getStyle()->usesFields();
  }

}
