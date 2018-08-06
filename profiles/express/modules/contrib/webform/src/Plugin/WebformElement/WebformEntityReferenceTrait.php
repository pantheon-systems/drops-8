<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Element\WebformEntityTrait;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an 'entity_reference' trait.
 */
trait WebformEntityReferenceTrait {

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    $types = [];
    $plugin_id = $this->getPluginId();
    $elements = $this->elementManager->getInstances();
    foreach ($elements as $element_name => $element_instance) {
      // Skip self.
      if ($plugin_id == $element_instance->getPluginId()) {
        continue;
      }
      if ($element_instance instanceof WebformElementEntityReferenceInterface) {
        $types[$element_name] = $element_instance->getPluginLabel();
      }
    }
    asort($types);
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $entity = $this->getTargetEntity($element, $webform_submission, $options);
    if (!$entity) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
      case 'value':
      case 'id':
      case 'label':
      case 'text':
      case 'breadcrumb':
        return $this->formatTextItem($element, $webform_submission, $options);

      case 'link':
        return [
          '#type' => 'link',
          '#title' => $entity->label(),
          '#url' => $entity->toUrl()->setAbsolute(TRUE),
        ];

      default:
        return \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $format);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $entity = $this->getTargetEntity($element, $webform_submission, $options);
    if (!$entity) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'id':
        return $entity->id();

      case 'breadcrumb':
        if ($entity->getEntityTypeId() == 'taxonomy_term') {
          /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
          $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
          $parents = $taxonomy_storage->loadAllParents($entity->id());
          $breadcrumb = [];
          foreach ($parents as $parent) {
            $breadcrumb[] = $parent->label();
          }
          $element += ['#delimiter' => ' › '];
          return implode($element['#delimiter'], array_reverse($breadcrumb));
        }
        return $entity->label();

      case 'label':
        return $entity->label();

      case 'raw':
        $entity_id = $entity->id();
        $entity_type = $entity->getEntityTypeId();
        return "$entity_type:$entity_id";

      case 'text':
      default:
        return sprintf('%s (%s)', $entity->label(), $entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $this->setOptions($element);
    $target_type = $this->getTargetType($element);
    // Exclude 'anonymous' user.
    if ($target_type == 'user') {
      unset($element['#options'][0]);
    }
    return array_keys($element['#options']);
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiline(array $element) {
    if ($this->hasMultipleValues($element)) {
      return TRUE;
    }
    else {
      return parent::isMultiline($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'link';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats() + [
      'link' => $this->t('Link'),
      'id' => $this->t('Entity ID'),
      'label' => $this->t('Label'),
      'text' => $this->t('Label (ID)'),
      'teaser' => $this->t('Teaser'),
      'default' => $this->t('Default'),
    ];
    if ($this->hasProperty('breadcrumb')) {
      $formats['breadcrumb'] = $this->t('Breadcrumb');
    }
    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    $element = parent::preview();
    $element += [
      '#target_type' => 'user',
      '#selection_handler' => 'default:user',
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
    ];
    if ($this instanceof OptionsBase) {
      $element['#options'] = [
        '1' => 'Administrator',
        '0' => 'Anonymous',
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [
      'entity_reference_items' => ['id', 'title', 'url'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {
    if (isset($form['entity_reference'])) {
      return;
    }

    $form['entity_reference'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity reference options'),
      '#open' => TRUE,
    ];
    $form['entity_reference']['entity_reference_items'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity reference format'),
      '#options' => [
        'id' => $this->t("ID, an entity's unique identified"),
        'title' => $this->t("Title, an entity's title/label"),
        'url' => $this->t("URL, an entity's URL"),
      ],
      '#required' => TRUE,
      '#default_value' => $export_options['entity_reference_items'],
      '#element_validate' => [[get_class($this), 'validateEntityReferenceFormat']],
    ];
  }

  /**
   * Form API callback. Remove unchecked options from value array.
   */
  public static function validateEntityReferenceFormat(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $values = $element['#value'];
    // Filter unchecked/unselected options whose value is 0.
    $values = array_filter($values, function ($value) {
      return $value !== 0;
    });
    $values = array_values($values);
    $form_state->setValueForElement($element, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options) {
    if (!$this->hasMultipleValues($element)) {
      $default_options = $this->getExportDefaultOptions();
      $header = isset($options['entity_reference_items']) ? $options['entity_reference_items'] : $default_options['entity_reference_items'];
      if ($options['header_format'] == 'label') {
        foreach ($header as $index => $column) {
          switch ($column) {
            case 'id':
              $header[$index] = $this->t('ID');
              break;

            case 'title':
              $header[$index] = $this->t('Title');
              break;

            case 'url':
              $header[$index] = $this->t('URL');
              break;
          }
        }
      }
      return $this->prefixExportHeader($header, $element, $options);
    }
    else {
      return parent::buildExportHeader($element, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $value = $this->getValue($element, $webform_submission);
    $default_options = $this->getExportDefaultOptions();
    $entity_reference_items = isset($export_options['entity_reference_items']) ? $export_options['entity_reference_items'] : $default_options['entity_reference_items'];

    if (!$this->hasMultipleValues($element)) {
      $entity_type = $this->getTargetType($element);
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $entity_id = $value;

      $record = [];
      if ($entity_id && ($entity = $entity_storage->load($entity_id))) {
        foreach ($entity_reference_items as $column) {
          switch ($column) {
            case 'id':
              $record[] = $entity->id();
              break;

            case 'title':
              $record[] = $entity->label();
              break;

            case 'url':
              $record[] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
              break;
          }
        }
      }
      else {
        foreach ($entity_reference_items as $column) {
          switch ($column) {
            case 'id':
              $record[] = $entity_id;
              break;

            case 'title':
              $record[] = '';
              break;

            case 'url':
              $record[] = '';
              break;
          }
        }
      }
      return $record;
    }
    else {
      if ($entity_reference_items == ['id']) {
        $element['#format'] = 'raw';
      }
      return parent::buildExportRecord($element, $webform_submission, $export_options);
    }
  }

  /**
   * Get element options.
   *
   * @param array $element
   *   An element.
   */
  protected function setOptions(array &$element) {
    WebformEntityTrait::setOptions($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType(array $element) {
    return $element['#target_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    if (empty($value)) {
      return NULL;
    }
    $entities = $this->getTargetEntities($element, $webform_submission, $options);
    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    if (empty($value)) {
      return [];
    }

    if (!is_array($value)) {
      $value = [$value];
    }

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    $target_type = $this->getTargetType($element);
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($value);
    foreach ($entities as $entity_id => $entity) {
      // Set the entity in the correct language for display.
      $entities[$entity_id] = $entity_repository->getTranslationFromContext($entity);
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /**************************************************************************/
    // IMPORTANT: Most of the below code and #ajax tweaks compensate for the
    // fact that the EntityReferenceSelection plugin specifically targets
    // entity references managed via the Field API.
    // @see \Drupal\webform\Plugin\WebformElementBase::setConfigurationFormDefaultValueRecursive
    // @see \Drupal\webform\Plugin\WebformElementBase::buildConfigurationForm
    /**************************************************************************/

    // Get element properties.
    $element_properties = $form_state->get('element_properties');

    // Alter element properties.
    if ($form_state->isRebuilding()) {
      // Get entity reference value from user input because
      // $form_state->getValue() does not always contain every input's value.
      $user_input = $form_state->getUserInput();
      $target_type = (!empty($user_input['properties']['target_type'])) ? $user_input['properties']['target_type'] : 'node';
      $selection_handler = (!empty($user_input['properties']['selection_handler'])) ? $user_input['properties']['selection_handler'] : 'default:' . $target_type;
      $selection_settings = (!empty($user_input['properties']['selection_settings'])) ? $user_input['properties']['selection_settings'] : [];
      // If the default selection handler has changed when need to update its
      // value.
      if (strpos($selection_handler, 'default:') === 0 && $selection_handler != "default:$target_type") {
        $selection_handler = "default:$target_type";
        $selection_settings = [];
        NestedArray::setValue($form_state->getUserInput(), ['properties', 'selection_handler'], $selection_handler);
        NestedArray::setValue($form_state->getUserInput(), ['properties', 'selection_settings'], $selection_settings);
      }
    }
    else {
      // Set default #target_type and #selection_handler.
      if (empty($element_properties['target_type'])) {
        $element_properties['target_type'] = 'node';
      }
      if (empty($element_properties['selection_handler'])) {
        $element_properties['selection_handler'] = 'default:' . $element_properties['target_type'];
      }
      $target_type = $element_properties['target_type'];
      $selection_handler = $element_properties['selection_handler'];
      $selection_settings = $element_properties['selection_settings'];
    }

    // Set 'User' entity reference selection filter type role's #default_value
    // to an array and not NULL, which throws
    // "Warning: Invalid argument supplied for foreach()
    // in Drupal\Core\Render\Element\Checkboxes::valueCallback()"
    // @see \Drupal\user\Plugin\EntityReferenceSelection\UserSelection::buildConfigurationForm
    if ($target_type == 'user'
      && isset($selection_settings['filter']['type'])
      && $selection_settings['filter']['type'] == 'role'
      && empty($selection_settings['filter']['role'])) {
      $selection_settings['filter']['role'] = [];
    }

    // Reset element properties.
    $element_properties['target_type'] = $target_type;
    $element_properties['selection_handler'] = $selection_handler;
    $element_properties['selection_settings'] = $selection_settings;
    $form_state->set('element_properties', $element_properties);

    /**************************************************************************/

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $entity_reference_selection_manager */
    $entity_reference_selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');

    // @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
    $selection_plugins = $entity_reference_selection_manager->getSelectionGroups($target_type);
    $handlers_options = [];
    foreach (array_keys($selection_plugins) as $selection_group_id) {
      if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
        $handlers_options[$selection_group_id] = Html::escape($selection_plugins[$selection_group_id][$selection_group_id]['label']);
      }
      elseif (array_key_exists($selection_group_id . ':' . $target_type, $selection_plugins[$selection_group_id])) {
        $selection_group_plugin = $selection_group_id . ':' . $target_type;
        $handlers_options[$selection_group_plugin] = Html::escape($selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label']);
      }
    }

    // Entity Reference fields are no longer supported to reference Paragraphs.
    // @see paragraphs_form_field_storage_config_edit_form_alter()
    $target_type_options = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
    unset($target_type_options[(string) $this->t('Content')]['paragraph']);

    $form['entity_reference'] = [
      '#type' => 'fieldset',
      '#title' => t('Entity reference settings'),
      '#prefix' => '<div id="webform-entity-reference-selection-wrapper">',
      '#suffix' => '</div>',
      '#weight' => -40,
    ];
    // Target type.
    $form['entity_reference']['target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of item to reference'),
      '#options' => $target_type_options,
      '#required' => TRUE,
      '#empty_option' => t('- Select a target type -'),
      '#attributes' => ['data-webform-trigger-submit' => '.js-webform-entity-reference-submit'],
      '#default_value' => $target_type,
    ];
    // Selection handler.
    $form['entity_reference']['selection_handler'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference method'),
      '#options' => $handlers_options,
      '#required' => TRUE,
      '#attributes' => ['data-webform-trigger-submit' => '.js-webform-entity-reference-submit'],
      '#default_value' => $selection_handler,
    ];
    // Selection settings.
    // Note: The below options are used to populate the #default_value for
    // selection settings.
    $entity_reference_selection_handler = $entity_reference_selection_manager->getInstance([
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    ]);
    $form['entity_reference']['selection_settings'] = $entity_reference_selection_handler->buildConfigurationForm([], $form_state);
    $form['entity_reference']['selection_settings']['#tree'] = TRUE;

    // Replace #ajax = TRUE with [data-webform-trigger-submit] attribute.
    $this->updateAjaxCallbackRecursive($form['entity_reference']['selection_settings']);

    // Remove the no-ajax submit button because we are not using the
    // EntityReferenceSelection with in Field API.
    unset(
      $form['entity_reference']['selection_settings']['target_bundles_update']
    );

    // Remove auto create, except for entity_autocomplete.
    if ($this->getPluginId() != 'entity_autocomplete' || $target_type != 'taxonomy_term') {
      unset(
        $form['entity_reference']['selection_settings']['auto_create'],
        $form['entity_reference']['selection_settings']['auto_create_bundle']
      );
    }

    // Add hide/show #format_items based on #tags.
    if ($this->supportsMultipleValues() && $this->hasProperty('tags')) {
      $form['display']['format_items']['#states'] = [
        'visible' => [
          [':input[name="properties[tags]"]' => ['checked' => TRUE]],
        ],
      ];
    }

    // Add Update button.
    // @see \Drupal\webform_test_element\Plugin\WebformElement\WebformTestElementProperties
    $form['entity_reference']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      // Set access to make sure the button is visible.
      '#access' => TRUE,
      // Validate the form.
      '#validate' => [[get_called_class(), 'validateEntityReferenceCallback']],
      // Submit the form.
      '#submit' => [[get_called_class(), 'submitEntityReferenceCallback']],
      // Refresh the entity reference details container.
      '#ajax' => [
        'callback' => [get_called_class(), 'entityReferenceAjaxCallback'],
        'wrapper' => 'webform-entity-reference-selection-wrapper',
        'progress' => ['type' => 'fullscreen'],
      ],
      // Hide button, add submit button trigger class, and disable validation.
      '#attributes' => [
        'class' => [
          'js-hide',
          'js-webform-entity-reference-submit',
          'js-webform-novalidate',
        ],
      ],
    ];

    // Attached webform.form library for .js-webform-novalidate behavior.
    $form['#attached']['library'][] = 'webform/webform.form';

    // Tags (only applies to 'entity_autocomplete' element).
    $form['element']['tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tags'),
      '#description' => $this->t('Check this option if the user should be allowed to enter multiple entity references using tags.'),
      '#return_value' => TRUE,
    ];
    if ($this->hasProperty('tags') && $this->hasProperty('multiple')) {
      $form['element']['multiple']['#states'] = [
        'disabled' => [
          ':input[name="properties[tags]"]' => ['checked' => TRUE],
        ],
      ];
      $form['element']['multiple__header_container']['#states']['visible'][] = [':input[name="properties[tags]"]' => ['checked' => FALSE]];
      $form['element']['tags']['#states'] = [
        'visible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['!value' => -1],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['value' => 1],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    if (isset($values['selection_settings']['target_bundles']) && empty($values['selection_settings']['target_bundles'])) {
      unset($values['selection_settings']['target_bundles']);
    }
    if (isset($values['selection_settings']['sort']['field']) && $values['selection_settings']['sort']['field'] == '_none') {
      unset($values['selection_settings']['sort']);
    }
    // Convert include_anonymous into boolean.
    if (isset($values['selection_settings']['include_anonymous'])) {
      $values['selection_settings']['include_anonymous'] = (bool) $values['selection_settings']['include_anonymous'];
    }

    $form_state->setValues($values);
  }

  /****************************************************************************/
  // Form/Ajax helpers and callbacks.
  /****************************************************************************/

  /**
   * Replace #ajax = TRUE with [data-webform-trigger-submit] attribute.
   *
   * @param array $element
   *   An element.
   */
  protected function updateAjaxCallbackRecursive(array &$element) {
    $element['#access'] = TRUE;
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['#ajax']) && $element[$key]['#ajax'] === TRUE) {
        $element[$key]['#attributes']['data-webform-trigger-submit'] = '.js-webform-entity-reference-submit';
      }
      unset($element[$key]['#ajax'], $element[$key]['#limit_validation_errors']);
      $this->updateAjaxCallbackRecursive($element[$key]);
    }
  }

  /**
   * Entity reference validate callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateEntityReferenceCallback(array $form, FormStateInterface $form_state) {
    $form_state->clearErrors();
  }

  /**
   * Entity reference submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitEntityReferenceCallback(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Entity reference Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The properties element.
   */
  public static function entityReferenceAjaxCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

}
