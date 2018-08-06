<?php

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'tab' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "tab",
 *   label = @Translation("Tab"),
 *   description = @Translation("This fieldgroup renders the content as a tab."),
 *   format_types = {
 *     "open",
 *     "closed",
 *   },
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   },
 * )
 */
class Tab extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $add = array(
      '#type' => 'details',
      '#title' => Html::escape($this->t($this->getLabel())),
      '#description' => $this->getSetting('description'),
    );

    if ($this->getSetting('id')) {
      $add['#id'] = Html::getId($this->getSetting('id'));
    }
    else {
      $add['#id'] = Html::getId('edit-' . $this->group->group_name);
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += array(
        '#attributes' => array('class' => $classes),
      );
    }

    if ($this->getSetting('formatter') == 'open') {
      $element['#open'] = TRUE;
    }

    // Front-end and back-end on configuration will lead
    // to vertical tabs nested in a separate vertical group.
    if (!empty($this->group->parent_name)) {
      $add['#group'] = $this->group->parent_name;
      $add['#parents'] = array($add['#group']);
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.tabs';
      $element['#attached']['library'][] = 'field_group/core';
    }

    $element += $add;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['formatter'] = array(
      '#title' => $this->t('Default state'),
      '#type' => 'select',
      '#options' => array_combine($this->pluginDefinition['format_types'], $this->pluginDefinition['format_types']),
      '#default_value' => $this->getSetting('formatter'),
      '#weight' => -4,
    );

    $form['description'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('description'),
      '#weight' => -4,
    );

    if ($this->context == 'form') {
      $form['required_fields'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Mark group as required if it contains required fields.'),
        '#default_value' => $this->getSetting('required_fields'),
        '#weight' => 2,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = array(
      'formatter' => 'closed',
      'description' => '',
    ) + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}
