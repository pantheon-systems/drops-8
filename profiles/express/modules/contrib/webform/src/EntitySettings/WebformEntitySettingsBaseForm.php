<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Base webform entity settings form.
 */
abstract class WebformEntitySettingsBaseForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $default_settings = $form_state->get('default_settings') ?: $this->config('webform.settings')->get('settings');

    $this->setElementDescriptionsRecursive($form, $default_settings);

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

    // Open delete button in a modal dialog.
    if (isset($actions['delete'])) {
      $actions['delete']['#attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, $actions['delete']['#attributes']['class']);
      WebformDialogHelper::attachLibraries($actions['delete']);
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

    $this->messenger()->addStatus($this->t('Webform settings %label has been saved.', ['%label' => $webform->label()]));
  }

  /**
   * Append [none] message and default value to an element's description.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $default_settings
   *   An associative array container default webform settings.
   */
  protected function setElementDescriptionsRecursive(array &$form, array $default_settings) {
    foreach ($form as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      if (isset($element['#type']) && !empty($default_settings["default_$key"]) && empty($element['#disabled'])) {
        if (!isset($element['#description'])) {
          $element['#description'] = '';
        }

        // Append default value to an element's description.
        $value = $default_settings["default_$key"];
        if (!is_array($value)) {
          $element['#description'] .= ($element['#description'] ? '<br /><br />' : '');
          $element['#description'] .= $this->t('Defaults to: %value', ['%value' => $value]);
        }

        // Append [none] message to an element's description.
        if (preg_match('/_message$/', $key)) {
          $none_translated = (string) $this->t('[none]');
          $element['#description'] .= ($element['#description'] ? ' ' : '');
          $t_args = [
            '@none' => '[none]',
            '@none_translated' => $none_translated,
          ];
          if ('[none]' === $none_translated) {
            $element['#description'] .= $this->t('Enter @none to hide this message.', $t_args);
          }
          else {
            $element['#description'] .= $this->t('Enter @none or @none_translated to hide this message.', $t_args);
          }
        }
      }

      $this->setElementDescriptionsRecursive($element, $default_settings);
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
      // Add group.
      if (isset($behavior_element['group'])) {
        $group = (string) $behavior_element['group'];
        if (!isset($element[$group])) {
          $element[$group] = [
            '#markup' => $group,
            '#prefix' => '<div><strong>',
            '#suffix' => '</strong></div>',
            '#weight' => $weight,
          ];
          $weight += 10;
        }
      }
      // Add behavior checkbox.
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
        if (isset($behavior_element['access'])) {
          $element[$behavior_key . '_disabled']['#access'] = $behavior_element['access'];
        }
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
        if (isset($behavior_element['access'])) {
          $element[$behavior_key]['#access'] = $behavior_element['access'];
        }
      }
      $weight += 10;
    }
  }

}
