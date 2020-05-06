<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TableSelect;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerManager;

/**
 * Base webform admin settings form.
 */
abstract class WebformAdminConfigBaseForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');
    _webform_config_update($config);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /****************************************************************************/
  // Exclude plugins.
  /****************************************************************************/

  /**
   * Build excluded plugins element.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform element, handler, or exporter plugin manager.
   * @param array $excluded_ids
   *   An array of excluded ids.
   *
   * @return array
   *   A table select element used to excluded plugins by id.
   */
  protected function buildExcludedPlugins(PluginManagerInterface $plugin_manager, array $excluded_ids) {
    $header = [
      'title' => ['data' => $this->t('Title')],
      'id' => ['data' => $this->t('Name'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
    ];

    $ids = [];
    $options = [];
    $plugins = $this->getPluginDefinitions($plugin_manager);
    foreach ($plugins as $id => $plugin_definition) {
      $ids[$id] = $id;

      $description = [
        'data' => [
          'content' => ['#markup' => $plugin_definition['description']],
        ],
      ];
      if (!empty($plugin_definition['deprecated'])) {
        $description['data']['deprecated'] = [
          '#type' => 'webform_message',
          '#message_message' => $plugin_definition['deprecated_message'],
          '#message_type' => 'warning',
        ];
      }
      $options[$id] = [
        'title' => $plugin_definition['label'],
        'id' => $plugin_definition['id'],
        'description' => $description,
      ];
    }

    $element = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#required' => TRUE,
      '#sticky' => TRUE,
      '#default_value' => array_diff($ids, $excluded_ids),
    ];
    TableSelect::setProcessTableSelectCallback($element);
    return $element;
  }

  /**
   * Convert included ids returned from table select element to excluded ids.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform element, handler, or exporter plugin manager.
   * @param array $included_ids
   *   An array of included_ids.
   *
   * @return array
   *   An array of excluded ids.
   *
   * @see \Drupal\webform\Form\WebformAdminSettingsForm::buildExcludedPlugins
   */
  protected function convertIncludedToExcludedPluginIds(PluginManagerInterface $plugin_manager, array $included_ids) {
    $ids = [];
    $plugins = $this->getPluginDefinitions($plugin_manager);
    foreach ($plugins as $id => $plugin) {
      $ids[$id] = $id;
    }

    $excluded_ids = array_diff($ids, array_filter($included_ids));
    ksort($excluded_ids);
    return $excluded_ids;
  }

  /**
   * Get plugin definitions.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform element, handler, or exporter plugin manager.
   *
   * @return array
   *   Plugin definitions.
   */
  protected function getPluginDefinitions(PluginManagerInterface $plugin_manager) {
    $plugins = $plugin_manager->getDefinitions();
    $plugins = $plugin_manager->getSortedDefinitions($plugins);
    if ($plugin_manager instanceof WebformElementManagerInterface) {
      unset($plugins['webform_element']);
    }
    elseif ($plugin_manager instanceof WebformHandlerManager || $plugin_manager instanceof WebformVariantManager) {
      unset($plugins['broken']);
    }
    return $plugins;
  }

}
