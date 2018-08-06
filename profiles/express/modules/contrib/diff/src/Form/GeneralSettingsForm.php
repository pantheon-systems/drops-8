<?php

namespace Drupal\diff\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure global diff settings.
 */
class GeneralSettingsForm extends ConfigFormBase {

  /**
   * The field diff layout plugin manager service.
   *
   * @var \Drupal\diff\DiffLayoutManager
   */
  protected $diffLayoutManager;

  /**
   * GeneralSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\diff\DiffLayoutManager $diff_layout_manager
   *   The diff layout manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DiffLayoutManager $diff_layout_manager) {
    parent::__construct($config_factory);

    $this->diffLayoutManager = $diff_layout_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.diff.layout')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'diff.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    $config = $this->config('diff.settings');

    $form['radio_behavior'] = array(
      '#type' => 'select',
      '#title' => $this->t('Diff radio behavior'),
      '#default_value' => $config->get('general_settings' . '.' . 'radio_behavior'),
      '#options' => array(
        'simple' => $this->t('Simple exclusion'),
        'linear' => $this->t('Linear restrictions'),
      ),
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('<em>Simple exclusion</em> means that users will not be able to select the same revision, <em>Linear restrictions</em> means that users can only select older or newer revisions of the current selections.'),
    );

    $layout_plugins = $this->diffLayoutManager->getDefinitions();
    $weight = count($layout_plugins) + 1;
    $layout_plugins_order = [];
    foreach ($layout_plugins as $id => $layout_plugin) {
      $layout_plugin_settings = $config->get('general_settings.layout_plugins')[$id];
      $layout_plugins_order[$id] = [
        'label' => $layout_plugin['label'],
        'description' => $layout_plugin['description'] ?: '',
        'enabled' => $layout_plugin_settings['enabled'],
        'weight' => isset($layout_plugin_settings['weight']) ? $layout_plugin_settings['weight'] : $weight,
      ];
      $weight++;
    }

    $form['layout_plugins'] = [
      '#type' => 'table',
      '#header' => [t('Layout'), t('Description'), t('Weight')],
      '#empty' => t('There are no items yet. Add an item.'),
      '#suffix' => '<div class="description">' . $this->t('The layout plugins that are enabled to display the revision comparison.') . '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'diff-layout-plugins-order-weight',
        ],
      ],
    ];

    uasort($layout_plugins_order, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($layout_plugins_order as $id => $layout_plugin) {
      $form['layout_plugins'][$id] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $layout_plugin['label'],
          '#title_display' => 'after',
          '#default_value' => $layout_plugin['enabled'],
        ],
        'description' => [
          '#type' => 'markup',
          '#markup' => Xss::filter($layout_plugin['description']),
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => t('Weight for @title', ['@title' => $layout_plugin['label']]),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => (int) $layout_plugin['weight'],
          '#array_parents' => [
            'settings',
            'sites',
            $id,
          ],
          '#attributes' => ['class' => ['diff-layout-plugins-order-weight']],
        ],
      ];
    }

    $form['field_based_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Field based layout settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="layout_plugins[split_fields][enabled]"]' => ['checked' => TRUE]],
          [':input[name="layout_plugins[unified_fields][enabled]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $context_lines = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
    $options = array_combine($context_lines, $context_lines);
    $form['field_based_settings']['context_lines_leading'] = array(
      '#type' => 'select',
      '#title' => $this->t('Leading'),
      '#description' => $this->t('This governs the number of unchanged <em>leading context "lines"</em> to preserve.'),
      '#default_value' => $config->get('general_settings' . '.' . 'context_lines_leading'),
      '#options' => $options,
    );
    $form['field_based_settings']['context_lines_trailing'] = array(
      '#type' => 'select',
      '#title' => $this->t('Trailing'),
      '#description' => $this->t('This governs the number of unchanged <em>trailing context "lines"</em> to preserve.'),
      '#default_value' => $config->get('general_settings' . '.' . 'context_lines_trailing'),
      '#options' => $options,
    );

    // Check if Visual inline layout is installed.
    if ($this->diffLayoutManager->hasDefinition('visual_inline')) {
      $form['visual_inline_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Visual layout settings'),
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="layout_plugins[visual_inline][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      // Get the theme data to display the related theme name.
      $default_theme_name = $this->config('system.theme')->get('default');
      $admin_theme_name = $this->config('system.theme')->get('admin');
      $form['visual_inline_settings']['visual_inline_theme'] = [
        '#type' => 'select',
        '#title' => $this->t('Theme'),
        '#options' => [
          'default' => $this->t('Default'),
          'admin' => $this->t('Admin'),
        ],
        '#description' => $this->t('Use Default to display the comparison as %default theme, or Admin as %admin theme.', [
          '%default' => $default_theme_name,
          '%admin' => $admin_theme_name,
        ]),
        '#default_value' => $config->get('general_settings')['visual_inline_theme'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Make sure there is at least one layout enabled.
    $enabled_layouts = [];
    foreach ($form_state->getValue('layout_plugins') as $key => $layout) {
      if ($layout['enabled']) {
        $enabled_layouts[] = $key;
      }
    }
    if (!$enabled_layouts) {
      $form_state->setErrorByName('layout_plugins', t('At least one layout plugin needs to be enabled.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('diff.settings');

    $keys = array(
      'radio_behavior',
      'context_lines_leading',
      'context_lines_trailing',
      'layout_plugins',
      'visual_inline_theme',
    );
    foreach ($keys as $key) {
      $config->set('general_settings.' . $key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
