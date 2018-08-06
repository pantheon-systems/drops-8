<?php

namespace Drupal\entity_browser\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'entity_reference' widget for entity browser.
 *
 * @FieldWidget(
 *   id = "entity_browser_entity_reference",
 *   label = @Translation("Entity browser"),
 *   description = @Translation("Uses entity browser to select entities."),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceBrowserWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Field widget display plugin manager.
   *
   * @var \Drupal\entity_browser\FieldWidgetDisplayManager
   */
  protected $fieldDisplayManager;

  /**
   * The depth of the delete button.
   *
   * This property exists so it can be changed if subclasses.
   *
   * @var int
   */
  protected static $deleteDepth = 4;

  /**
   * The module handler interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs widget plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\entity_browser\FieldWidgetDisplayManager $field_display_manager
   *   Field widget display plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, FieldWidgetDisplayManager $field_display_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldDisplayManager = $field_display_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.entity_browser.field_widget_display'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'entity_browser' => NULL,
      'open' => FALSE,
      'field_widget_display' => 'label',
      'field_widget_edit' => TRUE,
      'field_widget_remove' => TRUE,
      'field_widget_display_settings' => [],
      'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $browsers = [];
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    foreach ($this->entityTypeManager->getStorage('entity_browser')->loadMultiple() as $browser) {
      $browsers[$browser->id()] = $browser->label();
    }

    $element['entity_browser'] = [
      '#title' => $this->t('Entity browser'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('entity_browser'),
      '#options' => $browsers,
    ];

    $target_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $entity_type = $this->entityTypeManager->getStorage($target_type)->getEntityType();

    $displays = [];
    foreach ($this->fieldDisplayManager->getDefinitions() as $id => $definition) {
      if ($this->fieldDisplayManager->createInstance($id)->isApplicable($entity_type)) {
        $displays[$id] = $definition['label'];
      }
    }

    $id = Html::getUniqueId('field-' . $this->fieldDefinition->getName() . '-display-settings-wrapper');
    $element['field_widget_display'] = [
      '#title' => $this->t('Entity display plugin'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('field_widget_display'),
      '#options' => $displays,
      '#ajax' => [
        'callback' => array($this, 'updateSettingsAjax'),
        'wrapper' => $id,
      ],
    ];

    $edit_button_access = TRUE;
    if ($entity_type->id() == 'file') {
      // For entities of type "file", it only makes sense to have the edit
      // button if the module "file_entity" is present.
      $edit_button_access = $this->moduleHandler->moduleExists('file_entity');
    }
    $element['field_widget_edit'] = [
      '#title' => $this->t('Display Edit button'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('field_widget_edit'),
      '#access' => $edit_button_access,
    ];

    $element['field_widget_remove'] = [
      '#title' => $this->t('Display Remove button'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('field_widget_remove'),
    ];

    $element['open'] = [
      '#title' => $this->t('Show widget details as open by default'),
      '#description' => $this->t('If marked, the fieldset container that wraps the browser on the entity form will be loaded initially expanded.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('open'),
    ];

    $element['selection_mode'] = [
      '#title' => $this->t('Selection mode'),
      '#description' => $this->t('Determines how selection in entity browser will be handled. Will selection be appended/prepended or it will be replaced in case of editing.'),
      '#type' => 'select',
      '#options' => EntityBrowserElement::getSelectionModeOptions(),
      '#default_value' => $this->getSetting('selection_mode'),
    ];

    $element['field_widget_display_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity display plugin configuration'),
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $id . '">',
      '#suffix' => '</div>',
    ];

    if ($this->getSetting('field_widget_display')) {
      $element['field_widget_display_settings'] += $this->fieldDisplayManager
        ->createInstance(
          $form_state->getValue(
            ['fields', $this->fieldDefinition->getName(), 'settings_edit_form', 'settings', 'field_widget_display'],
            $this->getSetting('field_widget_display')
          ),
          $form_state->getValue(
            ['fields', $this->fieldDefinition->getName(), 'settings_edit_form', 'settings', 'field_widget_display_settings'],
            $this->getSetting('field_widget_display_settings')
          ) + ['entity_type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')]
        )
        ->settingsForm($form, $form_state);
    }

    return $element;
  }

  /**
   * Ajax callback that updates field widget display settings fieldset.
   */
  public function updateSettingsAjax(array $form, FormStateInterface $form_state) {
    return $form['fields'][$this->fieldDefinition->getName()]['plugin']['settings_edit_form']['settings']['field_widget_display_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = $this->summaryBase();
    $field_widget_display = $this->getSetting('field_widget_display');

    if (!empty($field_widget_display)) {
      $plugin = $this->fieldDisplayManager->getDefinition($field_widget_display);
      $summary[] = $this->t('Entity display: @name', ['@name' => $plugin['label']]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    if ($violations->count() > 0) {
      /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
      foreach ($violations as $offset => $violation) {
        // The value of the required field is checked through the "not null"
        // constraint, whose message is not very useful. We override it here for
        // better UX.
        if ($violation->getConstraint() instanceof NotNullConstraint) {
          $violations->set($offset, new ConstraintViolation(
            $this->t('@name field is required.', ['@name' => $items->getFieldDefinition()->getLabel()]),
            '',
            [],
            $violation->getRoot(),
            $violation->getPropertyPath(),
            $violation->getInvalidValue(),
            $violation->getPlural(),
            $violation->getCode(),
            $violation->getConstraint(),
            $violation->getCause()
          ));
        }
      }
    }

    parent::flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * Returns a key used to store the previously loaded entity.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   *
   * @return string
   *   A key for form state storage.
   */
  protected function getFormStateKey(FieldItemListInterface $items) {
    return $items->getEntity()->uuid() . ':' . $items->getFieldDefinition()->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $entities = $this->formElementEntities($items, $element, $form_state);

    // Get correct ordered list of entity IDs.
    $ids = array_map(
      function (EntityInterface $entity) {
        return $entity->id();
      },
      $entities
    );

    // We store current entity IDs as we might need them in future requests. If
    // some other part of the form triggers an AJAX request with
    // #limit_validation_errors we won't have access to the value of the
    // target_id element and won't be able to build the form as a result of
    // that. This will cause missing submit (Remove, Edit, ...) elements, which
    // might result in unpredictable results.
    $form_state->set(['entity_browser_widget', $this->getFormStateKey($items)], $ids);

    $hidden_id = Html::getUniqueId('edit-' . $this->fieldDefinition->getName() . '-target-id');
    $details_id = Html::getUniqueId('edit-' . $this->fieldDefinition->getName());

    $element += [
      '#id' => $details_id,
      '#type' => 'details',
      '#open' => !empty($entities) || $this->getSetting('open'),
      '#required' => $this->fieldDefinition->isRequired(),
      // We are not using Entity browser's hidden element since we maintain
      // selected entities in it during entire process.
      'target_id' => [
        '#type' => 'hidden',
        '#id' => $hidden_id,
        // We need to repeat ID here as it is otherwise skipped when rendering.
        '#attributes' => ['id' => $hidden_id],
        '#default_value' => implode(' ', array_map(
            function (EntityInterface $item) {
              return $item->getEntityTypeId() . ':' . $item->id();
            },
            $entities
        )),
        // #ajax is officially not supported for hidden elements but if we
        // specify event manually it works.
        '#ajax' => [
          'callback' => [get_class($this), 'updateWidgetCallback'],
          'wrapper' => $details_id,
          'event' => 'entity_browser_value_updated',
        ],
      ],
    ];

    // Get configuration required to check entity browser availability.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $selection_mode = $this->getSetting('selection_mode');

    // Enable entity browser if requirements for that are fulfilled.
    if (EntityBrowserElement::isEntityBrowserAvailable($selection_mode, $cardinality, count($ids))) {
      $persistentData = $this->getPersistentData();

      $element['entity_browser'] = [
        '#type' => 'entity_browser',
        '#entity_browser' => $this->getSetting('entity_browser'),
        '#cardinality' => $cardinality,
        '#selection_mode' => $selection_mode,
        '#default_value' => $entities,
        '#entity_browser_validators' => $persistentData['validators'],
        '#widget_context' => $persistentData['widget_context'],
        '#custom_hidden_id' => $hidden_id,
        '#process' => [
          ['\Drupal\entity_browser\Element\EntityBrowserElement', 'processEntityBrowser'],
          [get_called_class(), 'processEntityBrowser'],
        ],
      ];
    }

    $element['#attached']['library'][] = 'entity_browser/entity_reference';

    $field_parents = $element['#field_parents'];

    $element['current'] = $this->displayCurrentSelection($details_id, $field_parents, $entities);

    return $element;
  }

  /**
   * Render API callback: Processes the entity browser element.
   */
  public static function processEntityBrowser(&$element, FormStateInterface $form_state, &$complete_form) {
    $uuid = key($element['#attached']['drupalSettings']['entity_browser']);
    $element['#attached']['drupalSettings']['entity_browser'][$uuid]['selector'] = '#' . $element['#custom_hidden_id'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $entities = empty($values['target_id']) ? [] : explode(' ', trim($values['target_id']));
    $return = [];
    foreach ($entities as $entity) {
      $return[]['target_id'] = explode(':', $entity)[1];
    }

    return $return;
  }

  /**
   * AJAX form callback.
   */
  public static function updateWidgetCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // AJAX requests can be triggered by hidden "target_id" element when
    // entities are added or by one of the "Remove" buttons. Depending on that
    // we need to figure out where root of the widget is in the form structure
    // and use this information to return correct part of the form.
    if (!empty($trigger['#ajax']['event']) && $trigger['#ajax']['event'] == 'entity_browser_value_updated') {
      $parents = array_slice($trigger['#array_parents'], 0, -1);
    }
    elseif ($trigger['#type'] == 'submit' && strpos($trigger['#name'], '_remove_')) {
      $parents = array_slice($trigger['#array_parents'], 0, -static::$deleteDepth);
    }

    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    if (($trigger = $form_state->getTriggeringElement())) {
      // Can be triggered by "Remove" button.
      if (end($trigger['#parents']) === 'remove_button') {
        return FALSE;
      }
    }
    return parent::errorElement($element, $violation, $form, $form_state);
  }

  /**
   * Submit callback for remove buttons.
   */
  public static function removeItemSubmit(&$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#attributes']['data-entity-id']) && isset($triggering_element['#attributes']['data-row-id'])) {
      $id = $triggering_element['#attributes']['data-entity-id'];
      $row_id = $triggering_element['#attributes']['data-row-id'];
      $parents = array_slice($triggering_element['#parents'], 0, -static::$deleteDepth);
      $array_parents = array_slice($triggering_element['#array_parents'], 0, -static::$deleteDepth);

      // Find and remove correct entity.
      $values = explode(' ', $form_state->getValue(array_merge($parents, ['target_id'])));
      foreach ($values as $index => $item) {
        if ($item == $id && $index == $row_id) {
          array_splice($values, $index, 1);

          break;
        }
      }
      $target_id_value = implode(' ', $values);

      // Set new value for this widget.
      $target_id_element = &NestedArray::getValue($form, array_merge($array_parents, ['target_id']));
      $form_state->setValueForElement($target_id_element, $target_id_value);
      NestedArray::setValue($form_state->getUserInput(), $target_id_element['#parents'], $target_id_value);

      // Rebuild form.
      $form_state->setRebuild();
    }
  }

  /**
   * Builds the render array for displaying the current results.
   *
   * @param string $details_id
   *   The ID for the details element.
   * @param string[] $field_parents
   *   Field parents.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Array of referenced entities.
   *
   * @return array
   *   The render array for the current selection.
   */
  protected function displayCurrentSelection($details_id, $field_parents, $entities) {

    $field_widget_display = $this->fieldDisplayManager->createInstance(
      $this->getSetting('field_widget_display'),
      $this->getSetting('field_widget_display_settings') + ['entity_type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')]
    );

    $classes = ['entities-list'];
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1) {
      $classes[] = 'sortable';
    }

    return [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => $classes],
      'items' => array_map(
        function (ContentEntityInterface $entity, $row_id) use ($field_widget_display, $details_id, $field_parents) {
          $display = $field_widget_display->view($entity);
          $edit_button_access = $this->getSetting('field_widget_edit') && $entity->access('update', $this->currentUser);
          if ($entity->getEntityTypeId() == 'file') {
            // On file entities, the "edit" button shouldn't be visible unless
            // the module "file_entity" is present, which will allow them to be
            // edited on their own form.
            $edit_button_access &= $this->moduleHandler->moduleExists('file_entity');
          }
          if (is_string($display)) {
            $display = ['#markup' => $display];
          }
          return [
            '#theme_wrappers' => ['container'],
            '#attributes' => [
              'class' => ['item-container', Html::getClass($field_widget_display->getPluginId())],
              'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
              'data-row-id' => $row_id,
            ],
            'display' => $display,
            'remove_button' => [
              '#type' => 'submit',
              '#value' => $this->t('Remove'),
              '#ajax' => [
                'callback' => [get_class($this), 'updateWidgetCallback'],
                'wrapper' => $details_id,
              ],
              '#submit' => [[get_class($this), 'removeItemSubmit']],
              '#name' => $this->fieldDefinition->getName() . '_remove_' . $entity->id() . '_' . $row_id . '_' . md5(json_encode($field_parents)),
              '#limit_validation_errors' => [array_merge($field_parents, [$this->fieldDefinition->getName()])],
              '#attributes' => [
                'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
                'data-row-id' => $row_id,
              ],
              '#access' => (bool) $this->getSetting('field_widget_remove'),
            ],
            'edit_button' => [
              '#type' => 'submit',
              '#value' => $this->t('Edit'),
              '#ajax' => [
                'url' => Url::fromRoute(
                  'entity_browser.edit_form', [
                    'entity_type' => $entity->getEntityTypeId(),
                    'entity' => $entity->id(),
                  ]
                ),
                'options' => [
                  'query' => [
                    'details_id' => $details_id,
                  ],
                ],
              ],
              '#access' => $edit_button_access,
            ],
          ];
        },
        $entities,
        empty($entities) ? [] : range(0, count($entities) - 1)
      ),
    ];
  }

  /**
   * Gets data that should persist across Entity Browser renders.
   *
   * @return array
   *   Data that should persist after the Entity Browser is rendered.
   */
  protected function getPersistentData() {
    return [
      'validators' => [
        'entity_type' => ['type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')],
      ],
      'widget_context' => [],
    ];
  }

  /**
   * Gets options that define where newly added entities are inserted.
   *
   * @return array
   *   Mode labels indexed by key.
   */
  protected function selectionModeOptions() {
    return ['append' => $this->t('Append'), 'prepend' => $this->t('Prepend')];
  }

  /**
   * Provides base for settings summary shared by all EB widgets.
   *
   * @return array
   *   A short summary of the widget settings.
   */
  protected function summaryBase() {
    $summary = [];

    $entity_browser_id = $this->getSetting('entity_browser');
    if (empty($entity_browser_id)) {
      return [$this->t('No entity browser selected.')];
    }
    else {
      if ($browser = $this->entityTypeManager->getStorage('entity_browser')->load($entity_browser_id)) {
        $summary[] = $this->t('Entity browser: @browser', ['@browser' => $browser->label()]);
      }
      else {
        drupal_set_message($this->t('Missing entity browser!'), 'error');
        return [$this->t('Missing entity browser!')];
      }
    }

    $selection_mode = $this->getSetting('selection_mode');
    $selection_mode_options = EntityBrowserElement::getSelectionModeOptions();
    if (isset($selection_mode_options[$selection_mode])) {
      $summary[] = $this->t('Selection mode: @selection_mode', ['@selection_mode' => $selection_mode_options[$selection_mode]]);
    }
    else {
      $summary[] = $this->t('Undefined selection mode.');
    }

    return $summary;
  }

  /**
   * Determines the entities used for the form element.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item to extract the entities from.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The list of entities for the form element.
   */
  protected function formElementEntities(FieldItemListInterface $items, array $element, FormStateInterface $form_state) {
    $entities = [];
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    // Find IDs from target_id element (it stores selected entities in form).
    // This was added to help solve a really edge casey bug in IEF.
    if (($target_id_entities = $this->getEntitiesByTargetId($element, $form_state)) !== FALSE) {
      return $target_id_entities;
    }

    // Determine if we're submitting and if submit came from this widget.
    $is_relevant_submit = FALSE;
    if (($trigger = $form_state->getTriggeringElement())) {
      // Can be triggered by hidden target_id element or "Remove" button.
      if (end($trigger['#parents']) === 'target_id' || (end($trigger['#parents']) === 'remove_button')) {
        $is_relevant_submit = TRUE;

        // In case there are more instances of this widget on the same page we
        // need to check if submit came from this instance.
        $field_name_key = end($trigger['#parents']) === 'target_id' ? 2 : static::$deleteDepth + 1;
        $field_name_key = count($trigger['#parents']) - $field_name_key;
        $is_relevant_submit &= ($trigger['#parents'][$field_name_key] === $this->fieldDefinition->getName()) &&
          (array_slice($trigger['#parents'], 0, count($element['#field_parents'])) == $element['#field_parents']);
      }
    };

    if ($is_relevant_submit) {
      // Submit was triggered by hidden "target_id" element when entities were
      // added via entity browser.
      if (!empty($trigger['#ajax']['event']) && $trigger['#ajax']['event'] == 'entity_browser_value_updated') {
        $parents = $trigger['#parents'];
      }
      // Submit was triggered by one of the "Remove" buttons. We need to walk
      // few levels up to read value of "target_id" element.
      elseif ($trigger['#type'] == 'submit' && strpos($trigger['#name'], $this->fieldDefinition->getName() . '_remove_') === 0) {
        $parents = array_merge(array_slice($trigger['#parents'], 0, -static::$deleteDepth), ['target_id']);
      }

      if (isset($parents) && $value = $form_state->getValue($parents)) {
        $entities = EntityBrowserElement::processEntityIds($value);
        return $entities;
      }
      return $entities;
    }
    // IDs from a previous request might be saved in the form state.
    elseif ($form_state->has([
      'entity_browser_widget',
      $this->getFormStateKey($items),
    ])
    ) {
      $stored_ids = $form_state->get([
        'entity_browser_widget',
        $this->getFormStateKey($items),
      ]);
      $indexed_entities = $entity_storage->loadMultiple($stored_ids);

      // Selection can contain same entity multiple times. Since loadMultiple()
      // returns unique list of entities, it's necessary to recreate list of
      // entities in order to preserve selection of duplicated entities.
      foreach ($stored_ids as $entity_id) {
        if (isset($indexed_entities[$entity_id])) {
          $entities[] = $indexed_entities[$entity_id];
        }
      }
      return $entities;
    }
    // We are loading for for the first time so we need to load any existing
    // values that might already exist on the entity. Also, remove any leftover
    // data from removed entity references.
    else {
      foreach ($items as $item) {
        if (isset($item->target_id)) {
          $entity = $entity_storage->load($item->target_id);
          if (!empty($entity)) {
            $entities[] = $entity;
          }
        }
      }
      return $entities;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // If an entity browser is being used in this widget, add it as a config
    // dependency.
    if ($browser_name = $this->getSetting('entity_browser')) {
      $dependencies['config'][] = 'entity_browser.browser.' . $browser_name;
    }

    return $dependencies;
  }

  /**
   * Get selected elements from target_id element on form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|false
   *   Return list of entities if they are available or false.
   */
  protected function getEntitiesByTargetId(array $element, FormStateInterface $form_state) {
    $target_id_element_path = array_merge(
      $element['#field_parents'],
      [$this->fieldDefinition->getName(), 'target_id']
    );

    if (!NestedArray::keyExists($form_state->getUserInput(), $target_id_element_path)) {
      return FALSE;
    }

    // TODO Figure out how to avoid using raw user input.
    $current_user_input = NestedArray::getValue($form_state->getUserInput(), $target_id_element_path);
    if (!is_array($current_user_input)) {
      $entities = EntityBrowserElement::processEntityIds($current_user_input);
      return $entities;
    }

    return FALSE;
  }

}
