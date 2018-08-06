<?php

namespace Drupal\inline_entity_form\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inline_entity_form\TranslationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Inline entity form widget base class.
 */
abstract class InlineEntityFormBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The inline entity form id.
   *
   * @var string
   */
  protected $iefId;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The inline entity from handler.
   *
   * @var \Drupal\inline_entity_form\InlineFormInterface
   */
  protected $inlineFormHandler;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs an InlineEntityFormBase object.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->createInlineFormHandler();
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
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Creates an instance of the inline form handler for the current entity type.
   */
  protected function createInlineFormHandler() {
    if (!isset($this->inlineFormHandler)) {
      $target_type = $this->getFieldSetting('target_type');
      $this->inlineFormHandler = $this->entityTypeManager->getHandler($target_type, 'inline_form');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $keys = array_diff(parent::__sleep(), ['inlineFormHandler']);
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();
    $this->createInlineFormHandler();
  }

  /**
   * Sets inline entity form ID.
   *
   * @param string $ief_id
   *   The inline entity form ID.
   */
  protected function setIefId($ief_id) {
    $this->iefId = $ief_id;
  }

  /**
   * Gets inline entity form ID.
   *
   * @return string
   *   Inline entity form ID.
   */
  protected function getIefId() {
    return $this->iefId;
  }

  /**
   * Gets the target bundles for the current field.
   *
   * @return string[]
   *   A list of bundles.
   */
  protected function getTargetBundles() {
    $settings = $this->getFieldSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      $target_bundles = array_values($settings['handler_settings']['target_bundles']);
      // Filter out target bundles which no longer exist.
      $existing_bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($settings['target_type']));
      $target_bundles = array_intersect($target_bundles, $existing_bundles);
    }
    else {
      // If no target bundles have been specified then all are available.
      $target_bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($settings['target_type']));
    }

    return $target_bundles;
  }

  /**
   * Gets the bundles for which the current user has create access.
   *
   * @return string[]
   *   The list of bundles.
   */
  protected function getCreateBundles() {
    $create_bundles = [];
    foreach ($this->getTargetBundles() as $bundle) {
      if ($this->getAccessHandler()->createAccess($bundle)) {
        $create_bundles[] = $bundle;
      }
    }

    return $create_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'form_mode' => 'default',
      'override_labels' => FALSE,
      'label_singular' => '',
      'label_plural' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->getFieldSetting('target_type');
    $states_prefix = 'fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings]';
    $element = [];
    $element['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form mode'),
      '#default_value' => $this->getSetting('form_mode'),
      '#options' => $this->entityDisplayRepository->getFormModeOptions($entity_type_id),
      '#required' => TRUE,
    ];
    $element['override_labels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override labels'),
      '#default_value' => $this->getSetting('override_labels'),
    ];
    $element['label_singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Singular label'),
      '#default_value' => $this->getSetting('label_singular'),
      '#states' => [
        'visible' => [
          ':input[name="' . $states_prefix . '[override_labels]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['label_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural label'),
      '#default_value' => $this->getSetting('label_plural'),
      '#states' => [
        'visible' => [
          ':input[name="' . $states_prefix . '[override_labels]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($entity_form_mode = $this->getEntityFormMode()) {
      $form_mode_label = $entity_form_mode->label();
    }
    else {
      $form_mode_label = $this->t('Default');
    }
    $summary[] = t('Form mode: @mode', ['@mode' => $form_mode_label]);
    if ($this->getSetting('override_labels')) {
      $summary[] = $this->t(
        'Overriden labels are used: %singular and %plural',
        ['%singular' => $this->getSetting('label_singular'), '%plural' => $this->getSetting('label_plural')]
      );
    }
    else {
      $summary[] = $this->t('Default labels are used.');
    }

    return $summary;
  }

  /**
   * Gets the entity type managed by this handler.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type.
   */
  protected function getEntityTypeLabels() {
    // The admin has specified the exact labels that should be used.
    if ($this->getSetting('override_labels')) {
      return [
        'singular' => $this->getSetting('label_singular'),
        'plural' => $this->getSetting('label_plural'),
      ];
    }
    else {
      $this->createInlineFormHandler();
      return $this->inlineFormHandler->getEntityTypeLabels();
    }
  }

  /**
   * Checks whether we can build entity form at all.
   *
   * - Is IEF handler loaded?
   * - Are we on a "real" entity form and not on default value widget?
   *
   * @param FormStateInterface $form_state
   *   Form state.
   *
   * @return bool
   *   TRUE if we are able to proceed with form build and FALSE if not.
   */
  protected function canBuildForm(FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      return FALSE;
    }

    if (!$this->inlineFormHandler) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Prepares the form state for the current widget.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param bool $translating
   *   Whether there's a translation in progress.
   */
  protected function prepareFormState(FormStateInterface $form_state, FieldItemListInterface $items, $translating = FALSE) {
    $widget_state = $form_state->get(['inline_entity_form', $this->iefId]);
    if (empty($widget_state)) {
      $widget_state = [
        'instance' => $this->fieldDefinition,
        'form' => NULL,
        'delete' => [],
        'entities' => [],
      ];
      // Store the $items entities in the widget state, for further manipulation.
      foreach ($items as $delta => $item) {
        $entity = $item->entity;
        // The $entity can be NULL if the reference is broken.
        if ($entity) {
          // Display the entity in the correct translation.
          if ($translating) {
            $entity = TranslationHelper::prepareEntity($entity, $form_state);
          }
          $widget_state['entities'][$delta] = [
            'entity' => $entity,
            'weight' => $delta,
            'form' => NULL,
            'needs_save' => $entity->isNew(),
          ];
        }
      }
      $form_state->set(['inline_entity_form', $this->iefId], $widget_state);
    }
  }

  /**
   * Gets inline entity form element.
   *
   * @param string $operation
   *   The operation (i.e. 'add' or 'edit').
   * @param string $bundle
   *   Entity bundle.
   * @param string $langcode
   *   Entity langcode.
   * @param array $parents
   *   Array of parent element names.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Optional entity object.
   *
   * @return array
   *   IEF form element structure.
   */
  protected function getInlineEntityForm($operation, $bundle, $langcode, $delta, array $parents, EntityInterface $entity = NULL) {
    $element = [
      '#type' => 'inline_entity_form',
      '#entity_type' => $this->getFieldSetting('target_type'),
      '#bundle' => $bundle,
      '#langcode' => $langcode,
      '#default_value' => $entity,
      '#op' => $operation,
      '#form_mode' => $this->getSetting('form_mode'),
      '#save_entity' => FALSE,
      '#ief_row_delta' => $delta,
      // Used by Field API and controller methods to find the relevant
      // values in $form_state.
      '#parents' => $parents,
      // Labels could be overridden in field widget settings. We won't have
      // access to those in static callbacks (#process, ...) so let's add
      // them here.
      '#ief_labels' => $this->getEntityTypeLabels(),
      // Identifies the IEF widget to which the form belongs.
      '#ief_id' => $this->getIefId(),
    ];

    return $element;
  }

  /**
   * Determines whether there's a translation in progress.
   *
   * Ensures that at least one target bundle has translations enabled.
   * Otherwise the widget will skip translation even if it's happening
   * on the parent form itself.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if translating is in progress, FALSE otherwise.
   *
   * @see \Drupal\inline_entity_form\TranslationHelper::initFormLangcodes().
   */
  protected function isTranslating(FormStateInterface $form_state) {
    if (TranslationHelper::isTranslating($form_state)) {
      $translation_manager = \Drupal::service('content_translation.manager');
      $target_type = $this->getFieldSetting('target_type');
      foreach ($this->getTargetBundles() as $bundle) {
        if ($translation_manager->isEnabled($target_type, $bundle)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * After-build callback for removing the translatability clue from the widget.
   *
   * IEF expects the entity reference field to not be translatable, to avoid
   * different translations having different references.
   * However, that causes ContentTranslationHandler::addTranslatabilityClue()
   * to add an "(all languages)" suffix to the widget title. That suffix is
   * incorrect, since IEF does ensure that specific entity translations are
   * being edited.
   */
  public static function removeTranslatabilityClue(array $element, FormStateInterface $form_state) {
    $element['#title'] = $element['#field_title'];
    return $element;
  }

  /**
   * Adds submit callbacks to the inline entity form.
   *
   * @param array $element
   *   Form array structure.
   */
  public static function addIefSubmitCallbacks($element) {
    $element['#ief_element_submit'][] = [get_called_class(), 'submitSaveEntity'];
    return $element;
  }

  /**
   * Marks created/edited entity with "needs save" flag.
   *
   * Note that at this point the entity is not yet saved, since the user might
   * still decide to cancel the parent form.
   *
   * @param $entity_form
   *  The form of the entity being managed inline.
   * @param $form_state
   *   The form state of the parent form.
   */
  public static function submitSaveEntity($entity_form, FormStateInterface $form_state) {
    $ief_id = $entity_form['#ief_id'];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $entity_form['#entity'];

    if ($entity_form['#op'] == 'add') {
      // Determine the correct weight of the new element.
      $weight = 0;
      $entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']);
      if (!empty($entities)) {
        $weight = max(array_keys($entities)) + 1;
      }
      // Add the entity to form state, mark it for saving, and close the form.
      $entities[] = [
        'entity' => $entity,
        'weight' => $weight,
        'form' => NULL,
        'needs_save' => TRUE,
      ];
      $form_state->set(['inline_entity_form', $ief_id, 'entities'], $entities);
    }
    else {
      $delta = $entity_form['#ief_row_delta'];
      $entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']);
      $entities[$delta]['entity'] = $entity;
      $entities[$delta]['needs_save'] = TRUE;
      $form_state->set(['inline_entity_form', $ief_id, 'entities'], $entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($entity_form_mode = $this->getEntityFormMode()) {
      $dependencies['config'][] = $entity_form_mode->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * Gets the entity form mode instance for this widget.
   *
   * @return \Drupal\Core\Entity\EntityFormModeInterface|null
   *   The form mode instance, or NULL if the default one is used.
   */
  protected function getEntityFormMode() {
    $form_mode = $this->getSetting('form_mode');
    if ($form_mode != 'default') {
      $entity_type_id = $this->getFieldSetting('target_type');
      return $this->entityTypeManager->getStorage('entity_form_mode')->load($entity_type_id . '.' . $form_mode);
    }
    return NULL;
  }

  /**
   * Gets the access handler for the target entity type.
   *
   * @return \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   *   The access handler.
   */
  protected function getAccessHandler() {
    $entity_type_id = $this->getFieldSetting('target_type');
    return $this->entityTypeManager->getAccessControlHandler($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    if ($this->canBuildForm($form_state)) {
      return parent::form($items, $form, $form_state, $get_delta);
    }
    return [];
  }

  /**
   * Determines if the current user can add any new entities.
   *
   * @return bool
   */
  protected function canAddNew() {
    $create_bundles = $this->getCreateBundles();
    return !empty($create_bundles);
  }

}
