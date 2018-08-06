<?php

namespace Drupal\diff\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\diff\DiffBuilderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormState;

/**
 * Configure fields with their diff builder plugin settings.
 *
 * This form lists all the field types from the system and for every field type
 * it provides a select-box having as options all the FieldDiffBuilder plugins
 * that support that field type.
 */
class FieldsSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The field diff plugin manager service.
   *
   * @var \Drupal\diff\DiffBuilderManager
   */
  protected $diffBuilderManager;

  /**
   * Constructs a FieldsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The plugin manager service.
   * @param \Drupal\diff\DiffBuilderManager $diff_builder_manager
   *   The diff builder manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager, DiffBuilderManager $diff_builder_manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($config_factory);

    $this->fieldTypePluginManager = $plugin_manager;
    $this->diffBuilderManager = $diff_builder_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.diff.builder'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_admin_plugins';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['diff.plugins'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // The table containing all the field types discovered in the system.
    $form['fields'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => $this->getTableHeader(),
      '#empty' => $this->t('No field types found.'),
      '#prefix' => '<div id="field-display-overview-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-display-overview',
      ),
    );

    // Build a row in the table for each field of each entity type. Get all the
    // field plugins.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_name => $entity_type) {
      // Exclude non-revisionable entities.
      if (!$entity_type->isRevisionable()) {
        continue;
      }
      $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_name);
      foreach ($field_definitions as $field_name => $field_definition) {

        $show_diff = $this->diffBuilderManager->showDiff($field_definition);
        if (!$show_diff) {
          continue;
        }

        $key = $entity_type_name . '.' . $field_name;
        // Build a row in the table for this field.
        $form['fields'][$key] = $this->buildFieldRow($entity_type, $field_definition, $form_state);
      }
    }

    $this->diffBuilderManager->clearCachedDefinitions();
    // Submit button for the form.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    );
    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
    $form['#attached']['library'][] = 'diff/diff.general';

    return $form;
  }

  /**
   * Builds a row for the table. Each row corresponds to a field type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   Definition the field type.
   * @param FormStateInterface $form_state
   *   THe form state object.
   *
   * @return array
   *   A table row for the field type listing table.
   */
  protected function buildFieldRow(EntityTypeInterface $entity_type, FieldStorageDefinitionInterface $field_definition, FormStateInterface $form_state) {
    $entity_type_label = $entity_type->getLabel();
    $field_name = $field_definition->getName();
    $field_type = $field_definition->getType();
    $field_key = $entity_type->id() . '.' . $field_name;

    $display_options = $this->diffBuilderManager->getSelectedPluginForFieldStorageDefinition($field_definition);
    $plugin_options = $this->diffBuilderManager->getApplicablePluginOptions($field_definition);

    // Base button element for the various plugin settings actions.
    $base_button = [
      '#submit' => [[$this, 'multiStepSubmit']],
      '#ajax' => [
        'callback' => [$this, 'multiStepAjax'],
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
      '#field_key' => $field_key,
    ];

    $field_row['entity_type'] = [
      '#markup' => $entity_type_label,
    ];
    $labels = _diff_field_label($entity_type->id(), $field_name);
    $field_row['field_label'] = [
      '#markup' => array_shift($labels),
    ];

    $field_type_label = $this->fieldTypePluginManager->getDefinitions()[$field_type]['label'];
    $field_row['field_type'] = [
      '#markup' => $field_type_label,
    ];

    // Check the currently selected plugin, and merge persisted values for its
    // settings.
    if ($type = $form_state->getValue(['fields', $field_key, 'plugin', 'type'])) {
      $display_options['type'] = $type;
    }

    $plugin_settings = $form_state->get('plugin_settings');

    if (isset($plugin_settings[$field_key]['settings'])) {
      $modified = FALSE;
      if (!empty($display_options['settings'])) {
        foreach ($display_options['settings'] as $key => $value) {
          if ($plugin_settings[$field_key]['settings'][$key] != $value) {
            $modified = TRUE;
            break;
          }
        }
      }
      // In case settings are not identical to the ones in the config display
      // a warning message. Don't display it twice.
      if ($modified && empty($_SESSION['messages']['warning'])) {
        drupal_set_message($this->t('You have unsaved changes.'), 'warning');
      }
      $display_options['settings'] = $plugin_settings[$field_key]['settings'];
    }

    $field_row['plugin'] = array(
      'type' => array(
        '#type' => 'select',
        '#options' => $plugin_options,
        '#empty_option' => $this->t("- Don't compare -"),
        '#empty_value' => 'hidden',
        '#title_display' => 'invisible',
        '#attributes' => array(
          'class' => array('field-plugin-type'),
        ),
        '#default_value' => $display_options,
        '#ajax' => array(
          'callback' => [$this, 'multiStepAjax'],
          'method' => 'replace',
          'wrapper' => 'field-display-overview-wrapper',
          'effect' => 'fade',
        ),
        '#field_key' => $field_key,
      ),
      'settings_edit_form' => array(),
    );

    // Get a configured instance of the plugin.
    $plugin = $this->getPlugin($display_options);

    // We are currently editing this field's plugin settings. Display the
    // settings form and submit buttons.
    if ($form_state->get('plugin_settings_edit') == $field_key) {
      $field_row['plugin']['settings_edit_form'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
        '#parents' => ['fields', $field_key, 'settings_edit_form'],
        'label' => array(
          '#markup' => $this->t('Plugin settings:' . ' <span class="plugin-name">' . $plugin_options[$display_options['type']] . '</span>'),
        ),
        'settings' => $plugin->buildConfigurationForm(array(), $form_state),
        'actions' => array(
          '#type' => 'actions',
          'save_settings' => $base_button + [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#name' => $field_key . '_plugin_settings_update',
            '#value' => $this->t('Update'),
            '#op' => 'update',
          ],
          'cancel_settings' => $base_button + [
            '#type' => 'submit',
            '#name' => $field_key . '_plugin_settings_cancel',
            '#value' => $this->t('Cancel'),
            '#op' => 'cancel',
            // Do not check errors for the 'Cancel' button, but make sure we
            // get the value of the 'plugin type' select.
            '#limit_validation_errors' => [['fields', $field_key, 'plugin', 'type']],
          ],
        ),
      );
      $field_row['settings_edit'] = array();
      $field_row['#attributes']['class'][] = 'field-plugin-settings-editing';
    }
    else {
      $field_row['settings_edit'] = [];
      // Display the configure settings button only if a plugin is selected.
      if ($plugin) {
        $field_row['settings_edit'] = $base_button + array(
          '#type' => 'image_button',
          '#name' => $field_key . '_settings_edit',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => ['class' => ['field-plugin-settings-edit'], 'alt' => $this->t('Edit')],
          '#op' => 'edit',
          // Do not check errors for the 'Edit' button, but make sure we get
          // the value of the 'plugin type' select.
          '#limit_validation_errors' => [['fields', $field_key, 'plugin', 'type']],
          '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
          '#suffix' => '</div>',
        );
      }
    }

    return $field_row;
  }

  /**
   * Form submission handler for multi-step buttons.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function multiStepSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        // Store the field whose settings are currently being edited.
        $field_key = $trigger['#field_key'];
        $form_state->set('plugin_settings_edit', $field_key);
        break;

      case 'update':
        // Store the saved settings, and set the field back to 'non edit' mode.
        $field_key = $trigger['#field_key'];
        if ($plugin_settings = $form_state->getValue(['fields', $field_key, 'settings_edit_form', 'settings'])) {
          $form_state->set(['plugin_settings', $field_key, 'settings'], $plugin_settings);
        }
        $form_state->set('plugin_settings_edit', NULL);
        break;

      case 'cancel':
        // Set the field back to 'non edit' mode.
        $form_state->set('plugin_settings_edit', NULL);
        break;
    }

    $form_state->setRebuild();
  }

  /**
   * Ajax handler for multi-step buttons.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The fields form for a plugin.
   */
  public function multiStepAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#op'])) {
      $op = $trigger['#op'];

      // Pick the elements that need to receive the ajax-new-content effect.
      $updated_rows = [];
      $updated_columns = [];
      switch ($op) {
        case 'edit':
          $updated_rows = [$trigger['#field_key']];
          $updated_columns = array('plugin');
          break;

        case 'update':
        case 'cancel':
          $updated_rows = [$trigger['#field_key']];
          $updated_columns = array('plugin', 'settings_edit');
          break;
      }

      foreach ($updated_rows as $name) {
        foreach ($updated_columns as $key) {
          $element = &$form['fields'][$name][$key];
          $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
          $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';
        }
      }
    }
    // Return the whole table.
    return $form['fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $plugin_settings = $form_state->get('plugin_settings');
    $fields = $form_values['fields'];

    foreach ($fields as $field_key => $field_values) {
      // Validate only non-null plugins.
      if ($field_values['plugin']['type'] != 'hidden') {
        $settings = array();
        $key = NULL;
        // Form submitted without pressing update button on plugin settings form.
        if (isset($field_values['settings_edit_form']['settings'])) {
          $settings = $field_values['settings_edit_form']['settings'];
          $key = 1;
        }
        // Form submitted after settings were updated.
        elseif (isset($plugin_settings[$field_key]['settings'])) {
          $settings = $plugin_settings[$field_key]['settings'];
          $key = 2;
        }
        if (!empty($settings)) {
          // Build a new Form State object and populate it with values.
          $state = new FormState();
          $state->setValues($settings);
          $state->set('fields', $field_key);
          $plugin = $this->diffBuilderManager->createInstance($field_values['plugin']['type'], []);
          // Send the values to the plugins form validate handler.
          $plugin->validateConfigurationForm($form, $state);
          // Assign the validation messages back to the big table.
          if ($key == 1) {
            $form_state->setValue(['fields', $field_key, 'settings_edit_form', 'settings'], $state->getValues());
          }
          elseif ($key == 2) {
            $form_state->set(['plugin_settings', $field_key, 'settings'], $state->getValues());
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $plugin_settings = $form_state->get('plugin_settings');
    $fields = $form_values['fields'];

    $config = $this->config('diff.plugins');

    // Save the settings.
    foreach ($fields as $field_key => $field_values) {
      if ($field_values['plugin']['type'] == 'hidden') {
        $config->set('fields.' . $field_key, ['type' => 'hidden', 'settings' => []]);
      }
      else {

        // Initialize the plugin, if the type is unchanged then with the
        // existing settings, otherwise let it fall back to the default
        // settings.
        $configuration = [];
        if ($config->get('fields.' . $field_key . '.type') == $field_values['plugin']['type'] && $config->get('fields.' . $field_key . '.settings')) {
          $configuration = $config->get('fields.' . $field_key . '.settings');
        }
        $plugin = $this->diffBuilderManager->createInstance($field_values['plugin']['type'], $configuration);

        // Get plugin settings. They lie either directly in submitted form
        // values (if the whole form was submitted while some plugin settings
        // were being edited), or have been persisted in $form_state.

        $values = NULL;
        // Form submitted without pressing update button on plugin settings form.
        if (isset($field_values['settings_edit_form']['settings'])) {
          $values = $field_values['settings_edit_form']['settings'];
        }
        // Form submitted after settings were updated.
        elseif (isset($plugin_settings[$field_key]['settings'])) {
          $values = $plugin_settings[$field_key]['settings'];
        }

        // Build a FormState object and call the plugin submit handler.
        if ($values) {
          $state = new FormState();
          $state->setValues($values);
          $plugin->submitConfigurationForm($form, $state);
        }

        $config->set('fields.' . $field_key, [
          'type' => $field_values['plugin']['type'],
          'settings' => $plugin->getConfiguration(),
        ]);
      }
    }
    $config->save();

    drupal_set_message($this->t('Your settings have been saved.'));
  }

  /**
   * Returns a plugin object or NULL if no plugin could be found.
   *
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return \Drupal\diff\FieldDiffBuilderInterface|null
   *   The plugin.
   */
  protected function getPlugin(array $configuration) {
    if ($configuration && isset($configuration['type']) && $configuration['type'] != 'hidden') {
      if (!isset($configuration['settings'])) {
        $configuration['settings'] = array();
      }
      return $this->diffBuilderManager->createInstance(
        $configuration['type'], $configuration['settings']
      );
    }

    return NULL;
  }

  /**
   * Returns the header for the table.
   */
  protected function getTableHeader() {
    return array(
      'entity_type' => $this->t('Entity Type'),
      'field_name' => $this->t('Field'),
      'field_type' => $this->t('Field Type'),
      'plugin' => $this->t('Plugin'),
      'settings_edit' => '',
    );
  }

}
