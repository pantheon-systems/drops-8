<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Base webform entity settings form.
 */
abstract class WebformEntitySettingsBaseForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $default_settings = $this->config('webform.settings')->get('settings');
    $this->appendDefaultValueToElementDescriptions($form, $default_settings);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // Only display delete button on Settings > General tab/form.
    if ($this->operation != 'settings') {
      unset($actions['delete']);
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'settings')->toString(),
    ];
    $this->logger('webform')->notice('Webform settings @label has been saved.', $context);

    drupal_set_message($this->t('Webform settings %label has been saved.', ['%label' => $webform->label()]));
  }

  /**
   * Append default value to an element's description.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $default_settings
   *   An associative array container default webform settings.
   */
  protected function appendDefaultValueToElementDescriptions(array &$form, array $default_settings) {
    foreach ($form as $key => &$element) {
      // Skip if not a FAPI element.
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      if (isset($element['#type']) && !empty($default_settings["default_$key"]) && empty($element['#disabled'])) {
        if (!isset($element['#description'])) {
          $element['#description'] = '';
        }
        $element['#description'] .= ($element['#description'] ? '<br /><br />' : '');
        // @todo: Stop quotes from being encoded. (i.e. "Submit" => &quot;Submit&quote;)
        $value = $default_settings["default_$key"];
        $element['#description'] .= $this->t('Defaults to: %value', ['%value' => $value]);
      }

      $this->appendDefaultValueToElementDescriptions($element, $default_settings);
    }
  }

  /**
   * Append behavior checkboxes to element.
   *
   * @param array $element
   *   An array of form elements.
   * @param array $behavior_elements
   *   An associative array of behavior elements.
   * @param array $settings
   *   The webform's settings.
   * @param array $default_settings
   *   The global webform default settings.
   */
  protected function appendBehaviors(array &$element, array $behavior_elements, array $settings, array $default_settings) {
    $weight = 0;
    foreach ($behavior_elements as $behavior_key => $behavior_element) {
      if (!empty($default_settings['default_' . $behavior_key])) {
        $element[$behavior_key . '_disabled'] = [
          '#type' => 'checkbox',
          '#title' => $behavior_element['title'],
          '#description' => $behavior_element['all_description'],
          '#disabled' => TRUE,
          '#default_value' => TRUE,
          '#weight' => $weight,
        ];
        $element[$behavior_key] = [
          '#type' => 'value',
          '#value' => $settings[$behavior_key],
        ];
      }
      else {
        $element[$behavior_key] = [
          '#type' => 'checkbox',
          '#title' => $behavior_element['title'],
          '#description' => $behavior_element['form_description'],
          '#return_value' => TRUE,
          '#default_value' => $settings[$behavior_key],
          '#weight' => $weight,
        ];
      }
      $weight += 10;
    }
  }

}
