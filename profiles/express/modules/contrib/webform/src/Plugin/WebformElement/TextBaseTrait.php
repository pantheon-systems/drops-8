<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Text base trait contains methods that are applicable to any text elements.
 */
trait TextBaseTrait {

  /**
   * Build counter widget used by text elements and other element.
   *
   * @param string $name
   *   Property name prefix.
   * @param string $title
   *   Property title prefix.
   *
   * @return array
   *   A renderable array containing a counter configuration form.
   *
   * @see \Drupal\webform\Plugin\WebformElement\TextBase::form
   * @see \Drupal\webform\Plugin\WebformElement\OptionsBase::form
   */
  public function buildCounterForm($name = '', $title = NULL) {
    if ($title === NULL) {
      $title = t('Counter');
    }
    $t_args = ['@title' => $title];

    $build[$name . 'counter_type'] = [
      '#type' => 'select',
      '#title' => $title,
      '#description' => $this->t('Limit entered value to a maximum number of characters or words.'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        'character' => $this->t('Characters'),
        'word' => $this->t('Words'),
      ],
    ];
    $build['counter_container'] = $this->getFormInlineContainer();
    $build['counter_container']['#states'] = [
      'invisible' => [
        ':input[name="properties[' . $name . 'counter_type]"]' => ['value' => ''],
      ],
    ];
    $build['counter_container'][$name . 'counter_minimum'] = [
      '#type' => 'number',
      '#title' => $this->t('@title minimum', $t_args),
      '#min' => 1,
      '#states' => [
        'required' => [
          ':input[name="properties[' . $name . 'counter_type]"]' => ['!value' => ''],
          ':input[name="properties[' . $name . 'counter_maximum]"]' => ['value' => ''],
        ],
      ],
    ];
    $build['counter_container'][$name . 'counter_maximum'] = [
      '#type' => 'number',
      '#title' => $this->t('@title maximum', $t_args),
      '#min' => 1,
      '#states' => [
        'required' => [
          ':input[name="properties[' . $name . 'counter_type]"]' => ['!value' => ''],
          ':input[name="properties[' . $name . 'counter_minimum]"]' => ['value' => ''],
        ],
      ],
    ];
    $build['counter_message_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="properties[' . $name . 'counter_type]"]' => ['value' => ''],
        ],
      ],
    ];
    $build['counter_message_container'][$name . 'counter_minimum_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('@title minimum message', $t_args),
      '#description' => $this->t('Defaults to: %value', ['%value' => $this->t('%d characters/word(s) entered')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[' . $name . 'counter_minimum]"]' => ['!value' => ''],
          ':input[name="properties[' . $name . 'counter_maximum]"]' => ['value' => ''],
        ],
      ],
    ];
    $build['counter_message_container'][$name . 'counter_maximum_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('@title maximum message', $t_args),
      '#description' => $this->t('Defaults to: %value', ['%value' => $this->t('%d characters/word(s) remaining')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[' . $name . 'counter_maximum]"]' => ['!value' => ''],
        ],
      ],
    ];
    if ($this->librariesManager->isExcluded('jquery.textcounter')) {
      $build[$name . 'counter_type']['#access'] = FALSE;
      $build[$name . 'counter_container']['#access'] = FALSE;
      $build[$name . 'counter_message_container']['#access'] = FALSE;
    }
    return $build;
  }

}
