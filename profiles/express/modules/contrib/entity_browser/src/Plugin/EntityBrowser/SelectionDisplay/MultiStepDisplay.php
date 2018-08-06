<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Drupal\entity_browser\SelectionDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Show current selection and delivers selected entities.
 *
 * @EntityBrowserSelectionDisplay(
 *   id = "multi_step_display",
 *   label = @Translation("Multi step selection display"),
 *   description = @Translation("Shows the current selection display, allowing to mix elements selected through different widgets in several steps."),
 *   acceptPreselection = TRUE,
 *   js_commands = TRUE
 * )
 */
class MultiStepDisplay extends SelectionDisplayBase {

  /**
   * Field widget display plugin manager.
   *
   * @var \Drupal\entity_browser\FieldWidgetDisplayManager
   */
  protected $fieldDisplayManager;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\FieldWidgetDisplayManager $field_display_manager
   *   Field widget display plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, FieldWidgetDisplayManager $field_display_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager);
    $this->fieldDisplayManager = $field_display_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.field_widget_display')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type' => 'node',
      'display' => 'label',
      'display_settings' => [],
      'select_text' => 'Use selected',
      'selection_hidden' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state) {

    // Check if trigger element is dedicated to handle front-end commands.
    if (($triggering_element = $form_state->getTriggeringElement()) && $triggering_element['#name'] === 'ajax_commands_handler' && !empty($triggering_element['#value'])) {
      $this->executeJsCommand($form_state);
    }

    $selected_entities = $form_state->get(['entity_browser', 'selected_entities']);

    $form = [];
    $form['#attached']['library'][] = 'entity_browser/multi_step_display';
    $form['selected'] = [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['entities-list']],
      '#tree' => TRUE,
    ];
    if ($this->configuration['selection_hidden']) {
      $form['selected']['#attributes']['class'][] = 'hidden';
    }
    foreach ($selected_entities as $id => $entity) {
      $display_plugin = $this->fieldDisplayManager->createInstance(
        $this->configuration['display'],
        $this->configuration['display_settings'] + ['entity_type' => $this->configuration['entity_type']]
      );
      $display = $display_plugin->view($entity);
      if (is_string($display)) {
        $display = ['#markup' => $display];
      }

      $form['selected']['items_' . $entity->id() . '_' . $id] = [
        '#theme_wrappers' => ['container'],
        '#attributes' => [
          'class' => ['item-container'],
          'data-entity-id' => $entity->id(),
        ],
        'display' => $display,
        'remove_button' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#submit' => [[get_class($this), 'removeItemSubmit']],
          '#name' => 'remove_' . $entity->id() . '_' . $id,
          '#attributes' => [
            'class' => ['entity-browser-remove-selected-entity'],
            'data-row-id' => $id,
            'data-remove-entity' => 'items_' . $entity->id(),
          ],
        ],
        'weight' => [
          '#type' => 'hidden',
          '#default_value' => $id,
          '#attributes' => ['class' => ['weight']],
        ],
      ];
    }

    // Add hidden element used to make execution of front-end commands.
    $form['ajax_commands_handler'] = [
      '#type' => 'hidden',
      '#name' => 'ajax_commands_handler',
      '#id' => 'ajax_commands_handler',
      '#attributes' => ['id' => 'ajax_commands_handler'],
      '#ajax' => [
        'callback' => [get_class($this), 'handleAjaxCommand'],
        'wrapper' => 'edit-selected',
        'event' => 'execute_js_commands',
        'progress' => [
          'type' => 'fullscreen',
        ],
      ],
    ];

    $form['use_selected'] = [
      '#type' => 'submit',
      '#value' => $this->t($this->configuration['select_text']),
      '#name' => 'use_selected',
      '#attributes' => [
        'class' => ['entity-browser-use-selected'],
      ],
      '#access' => empty($selected_entities) ? FALSE : TRUE,
    ];

    $form['show_selection'] = [
      '#type' => 'button',
      '#value' => $this->t('Show selected'),
      '#attributes' => [
        'class' => ['entity-browser-show-selection'],
      ],
      '#access' => empty($selected_entities) ? FALSE : TRUE,
    ];

    return $form;
  }

  /**
   * Execute command generated by front-end.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function executeJsCommand(FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $commands = json_decode($triggering_element['#value'], TRUE);

    // Process Remove command.
    if (isset($commands['remove'])) {
      $entity_ids = $commands['remove'];

      // Remove weight of entity being removed.
      foreach ($entity_ids as $entity_info) {
        $entity_id_info = explode('_', $entity_info['entity_id']);

        $form_state->unsetValue([
          'selected',
          $entity_info['entity_id'],
        ]);

        // Remove entity itself.
        $selected_entities = &$form_state->get(['entity_browser', 'selected_entities']);
        unset($selected_entities[$entity_id_info[2]]);
      }

      static::saveNewOrder($form_state);
    }

    // Process Add command.
    if (isset($commands['add'])) {
      $entity_ids = $commands['add'];

      $entities_to_add = [];
      $added_entities = [];

      // Generate list of entities grouped by type, to speed up loadMultiple.
      foreach ($entity_ids as $entity_pair_info) {
        $entity_info = explode(':', $entity_pair_info['entity_id']);

        if (!isset($entities_to_add[$entity_info[0]])) {
          $entities_to_add[$entity_info[0]] = [];
        }

        $entities_to_add[$entity_info[0]][] = $entity_info[1];
      }

      // Load Entities and add into $added_entities, so that we have list of
      // entities with key - "type:id".
      foreach ($entities_to_add as $entity_type => $entity_type_ids) {
        $indexed_entities = $this->entityTypeManager->getStorage($entity_type)
          ->loadMultiple($entity_type_ids);

        foreach ($indexed_entities as $entity_id => $entity) {
          $added_entities[implode(':', [
            $entity_type,
            $entity_id,
          ])] = $entity;
        }
      }

      // Array is accessed as reference, so that changes are propagated.
      $selected_entities = &$form_state->get([
        'entity_browser',
        'selected_entities',
      ]);

      // Fill list of selected entities in correct order with loaded entities.
      // In this case, order is preserved and multiple entities with same ID
      // can be selected properly.
      foreach ($entity_ids as $entity_pair_info) {
        $selected_entities[] = $added_entities[$entity_pair_info['entity_id']];
      }
    }
  }

  /**
   * Handler to generate Ajax response, after command is executed.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return Ajax response with commands.
   */
  public static function handleAjaxCommand(array $form, FormStateInterface $form_state) {
    $ajax = new AjaxResponse();

    if (($triggering_element = $form_state->getTriggeringElement()) && $triggering_element['#name'] === 'ajax_commands_handler' && !empty($triggering_element['#value'])) {
      $commands = json_decode($triggering_element['#value'], TRUE);

      // Entity IDs that are affected by this command.
      if (isset($commands['add'])) {
        /** @var \Drupal\Core\Render\RendererInterface $renderer */
        $renderer = \Drupal::service('renderer');
        $entity_ids = $commands['add'];

        $selected_entities = &$form_state->get([
          'entity_browser',
          'selected_entities',
        ]);

        // Get entities added by this command and generate JS commands for them.
        $selected_entity_keys = array_keys($selected_entities);
        $key_index = count($selected_entity_keys) - count($entity_ids);
        foreach ($entity_ids as $entity_pair_info) {
          $last_entity_id = $selected_entities[$selected_entity_keys[$key_index]]->id();

          $html = $renderer->render($form['selection_display']['selected']['items_' . $last_entity_id . '_' . $selected_entity_keys[$key_index]]);

          $ajax->addCommand(
            new ReplaceCommand('div[id="' . $entity_pair_info['proxy_id'] . '"]', static::trimSingleHtmlTag($html))
          );

          $key_index++;
        }

        // Check if action buttons should be added to form. When number of added
        // entities is equal to number of selected entities. Then form buttons
        // should be also rendered: use_selected and show_selection.
        if (count($selected_entities) === count($entity_ids)) {

          // Order is important, since commands are executed one after another.
          $ajax->addCommand(
            new AfterCommand('.entities-list', static::trimSingleHtmlTag($renderer->render($form['selection_display']['show_selection'])))
          );

          $ajax->addCommand(
            new AfterCommand('.entities-list', static::trimSingleHtmlTag($renderer->render($form['selection_display']['use_selected'])))
          );
        }
      }

      // Add Invoke command to trigger loading of entities that are queued
      // during execution of current Ajax request.
      $ajax->addCommand(
        new InvokeCommand('[name=ajax_commands_handler]', 'trigger', ['execute-commands'])
      );
    }

    return $ajax;
  }

  /**
   * Make HTML with single tag suitable for Ajax response.
   *
   * Comments will be removed and also whitespace characters, because Ajax JS
   * "insert" command handling checks number of base elements in response and
   * wraps it in a "div" tag if there are more then one base element.
   *
   * @param string $html
   *   HTML content.
   *
   * @return string
   *   Returns cleaner HTML content, suitable for Ajax responses.
   */
  protected static function trimSingleHtmlTag($html) {
    $clearHtml = trim($html);

    // Remove comments around main single HTML tag. RegEx flag 's' is there to
    // allow matching on whitespaces too. That's needed, because generated HTML
    // contains a lot newlines.
    if (preg_match_all('/(<(?!(!--)).+((\\/)|(<\\/[a-z]+))>)/is', $clearHtml, $matches)) {
      if (!empty($matches) && !empty($matches[0])) {
        $clearHtml = $matches[0][0];
      }
    }

    return $clearHtml;
  }

  /**
   * Submit callback for remove buttons.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function removeItemSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Remove weight of entity being removed.
    $form_state->unsetValue([
      'selected',
      $triggering_element['#attributes']['data-remove-entity'] . '_' . $triggering_element['#attributes']['data-row-id'],
    ]);

    // Remove entity itself.
    $selected_entities = &$form_state->get(['entity_browser', 'selected_entities']);
    unset($selected_entities[$triggering_element['#attributes']['data-row-id']]);

    static::saveNewOrder($form_state);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, FormStateInterface $form_state) {
    $this->saveNewOrder($form_state);
    if ($form_state->getTriggeringElement()['#name'] == 'use_selected') {
      $this->selectionDone($form_state);
    }
  }

  /**
   * Saves new ordering of entities based on weight.
   *
   * @param FormStateInterface $form_state
   *   Form state.
   */
  public static function saveNewOrder(FormStateInterface $form_state) {
    $selected = $form_state->getValue('selected');
    if (!empty($selected)) {
      $weights = array_column($selected, 'weight');
      $selected_entities = $form_state->get(['entity_browser', 'selected_entities']);

      // If we added new entities to the selection at this step we won't have
      // weights for them so we have to fake them.
      $diff_selected_size = count($selected_entities) - count($weights);
      if ($diff_selected_size > 0) {
        $max_weight = (max($weights) + 1);
        for ($new_weight = $max_weight; $new_weight < ($max_weight + $diff_selected_size); $new_weight++) {
          $weights[] = $new_weight;
        }
      }

      $ordered = array_combine($weights, $selected_entities);
      ksort($ordered);
      $form_state->set(['entity_browser', 'selected_entities'], $ordered);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_entity_type = $form_state->getValue('entity_type', $this->configuration['entity_type']);
    $default_display = $form_state->getValue('display', $this->configuration['display']);
    $default_display_settings = $form_state->getValue('display_settings', $this->configuration['display_settings']);
    $default_display_settings += ['entity_type' => $default_entity_type];

    if ($form_state->isRebuilding()) {
      $form['#prefix'] = '<div id="multi-step-form-wrapper">';
    } else {
      $form['#prefix'] .= '<div id="multi-step-form-wrapper">';
    }
    $form['#suffix'] = '</div>';

    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_types[$entity_type_id] = $entity_type->getLabel();
    }
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#description' => $this->t("Entity browser itself does not need information about entity type being selected. It can actually select entities of different type. However, some of the display plugins need to know which entity type they are operating with. Display plugins that do not need this info will ignore this configuration value."),
      '#default_value' => $default_entity_type,
      '#options' => $entity_types,
      '#ajax' => [
        'callback' => [$this, 'updateSettingsAjax'],
        'wrapper' => 'multi-step-form-wrapper',
      ],
    ];

    $displays = [];
    foreach ($this->fieldDisplayManager->getDefinitions() as $display_plugin_id => $definition) {
      $entity_type = $this->entityTypeManager->getDefinition($default_entity_type);
      if ($this->fieldDisplayManager->createInstance($display_plugin_id)->isApplicable($entity_type)) {
        $displays[$display_plugin_id] = $definition['label'];
      }
    }
    $form['display'] = [
      '#title' => $this->t('Entity display plugin'),
      '#type' => 'select',
      '#default_value' => $default_display,
      '#options' => $displays,
      '#ajax' => [
        'callback' => [$this, 'updateSettingsAjax'],
        'wrapper' => 'multi-step-form-wrapper',
      ],
    ];

    $form['display_settings'] = [
      '#type' => 'container',
      '#title' => $this->t('Entity display plugin configuration'),
      '#tree' => TRUE,
    ];
    if ($default_display_settings) {
      $display_plugin = $this->fieldDisplayManager
        ->createInstance($default_display, $default_display_settings);

      $form['display_settings'] += $display_plugin->settingsForm($form, $form_state);
    }
    $form['select_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Select button text'),
      '#default_value' => $this->configuration['select_text'],
      '#description' => $this->t('Text to display on the entity browser select button.'),
    ];

    $form['selection_hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Selection hidden by default'),
      '#default_value' => $this->configuration['selection_hidden'],
      '#description' => $this->t('Whether or not the selection should be hidden by default.'),
    ];

    return $form;
  }

  /**
   * Ajax callback that updates multi-step plugin configuration form on AJAX updates.
   */
  public function updateSettingsAjax(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
