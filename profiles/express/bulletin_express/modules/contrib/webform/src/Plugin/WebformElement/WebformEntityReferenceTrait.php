<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Element\WebformEntityTrait;
use Drupal\webform\WebformInterface;

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
      if ($element_instance instanceof WebformEntityReferenceInterface) {
        $types[$element_name] = $element_instance->getPluginLabel();
      }
    }
    asort($types);
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function format($type, array &$element, $value, array $options = []) {
    if ($this->hasMultipleValues($element)) {
      $value = $this->getTargetEntities($element, $value, $options);
    }
    else {
      $value = $this->getTargetEntity($element, $value, $options);
    }
    return parent::format($type, $element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, $value, array $options = []) {
    $entity = $this->getTargetEntity($element, $value, $options);
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
      case 'value':
      case 'id':
      case 'label':
      case 'text':
      case 'breadcrumb':
        return $this->formatTextItem($element, $value, $options);

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
  public function formatTextItem(array $element, $value, array $options = []) {
    $entity = $this->getTargetEntity($element, $value, $options);
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
  public function getExportDefaultOptions() {
    return [
      'entity_reference_format' => 'link',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {
    parent::buildExportOptionsForm($form, $form_state, $export_options);
    if (isset($form['entity_reference'])) {
      return;
    }

    $form['entity_reference'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity reference options'),
      '#open' => TRUE,
    ];
    $form['entity_reference']['entity_reference_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Entity reference format'),
      '#options' => [
        'link' => $this->t('Entity link; with entity id, title and url in their own column.') . '<div class="description">' . $this->t("Entity links are suitable as long as there are not too many submissions (ie 1000's) pointing to just a few unique entities (ie 100's).") . '</div>',
        'id' => $this->t('Entity id; just the entity id column') . '<div class="description">' . $this->t('Entity links are suitable as long as there is mechanism for the referenced entity to be looked up external (ie REST API).') . '</div>',
      ],
      '#default_value' => $export_options['entity_reference_format'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options) {
    if (!$this->hasMultipleValues($element) && $options['entity_reference_format'] == 'link') {
      if ($options['header_format'] == 'label') {
        $header = [
          (string) $this->t('ID'),
          (string) $this->t('Title'),
          (string) $this->t('URL'),
        ];
      }
      else {
        $header = ['id', 'title', 'url'];
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
  public function buildExportRecord(array $element, $value, array $options) {
    if (!$this->hasMultipleValues($element) && $options['entity_reference_format'] == 'link') {
      $entity_type = $this->getTargetType($element);
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $entity_id = $value;

      $record = [];
      if ($entity_id && ($entity = $entity_storage->load($entity_id))) {
        $record[] = $entity->id();
        $record[] = $entity->label();
        $record[] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
      }
      else {
        $record[] = "$entity_type:$entity_id";
        $record[] = '';
        $record[] = '';
      }
      return $record;
    }
    else {
      if ($options['entity_reference_format'] == 'id') {
        $element['#format'] = 'raw';
      }
      return parent::buildExportRecord($element, $value, $options);
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
   * Get referenced entity type..
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   A entity type.
   */
  protected function getTargetType(array $element) {
    return $element['#target_type'];
  }

  /**
   * Get referenced entity.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The referenced entity.
   */
  protected function getTargetEntity(array $element, $value, array $options = []) {
    if (empty($value)) {
      return NULL;
    }
    elseif ($value instanceof EntityInterface) {
      return $value;
    }

    $entities = $this->getTargetEntities($element, [$value], $options);
    return reset($entities);
  }

  /**
   * Get referenced entities.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An associative array containing entities keyed by entity_id.
   */
  protected function getTargetEntities(array $element, $value, array $options = []) {
    if (empty($value)) {
      return [];
    }

    $target_type = $this->getTargetType($element);
    $langcode = (!empty($options['langcode'])) ? $options['langcode'] : \Drupal::languageManager()->getCurrentLanguage()->getId();
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($value);
    foreach ($entities as $entity_id => $entity) {
      if ($entity->hasTranslation($langcode)) {
        $entities[$entity_id] = $entity->getTranslation($langcode);
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Get element properties.
    $element_properties = $form_state->get('element_properties');

    // Alter element properties.
    if ($properties = $form_state->getValue('properties')) {
      $target_type = (isset($properties['target_type'])) ? $properties['target_type'] : 'node';
      $selection_handler = (isset($properties['selection_handler'])) ? $properties['selection_handler'] : 'default:' . $target_type;
      // If the default selection handler has changed  when need to update its
      // value.
      if (strpos($selection_handler, 'default:') === 0 && $selection_handler != "default:$target_type") {
        $selection_handler = "default:$target_type";
        $selection_settings = [];
      }
      else {
        $selection_settings = (isset($properties['selection_settings'])) ? $properties['selection_settings'] : [];
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

    // Reset element properties.
    $element_properties['target_type'] = $target_type;
    $element_properties['selection_handler'] = $selection_handler;
    $element_properties['selection_settings'] = $selection_settings;
    $form_state->set('element_properties', $element_properties);

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

    // ISSUE:
    // The AJAX handling for @EntityReferenceSelection plugins is just broken.
    //
    // WORKAROUND:
    // Implement custom #ajax that refresh the entire details element and
    // remove #ajax from selection settings to just get an MVP UI
    // for entity reference elements.
    //
    // @see https://www.drupal.org/project/issues/drupal?text=EntityReferenceSelection&version=8.x
    // @todo Figure out how to properly implement @EntityReferenceSelection plugins.
    $ajax_settings = [
      'callback' => [get_class($this), 'ajaxEntityReference'],
      'wrapper' => 'webform-entity-reference-selection-wrapper',
    ];
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
      '#options' => \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE),
      '#required' => TRUE,
      '#empty_option' => t('- Select a target type -'),
      '#ajax' => $ajax_settings,
      '#default_value' => $target_type,
    ];
    // Selection handler.
    $form['entity_reference']['selection_handler'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference method'),
      '#options' => $handlers_options,
      '#required' => TRUE,
      '#ajax' => $ajax_settings,
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

    $this->updateAjaxCallbackRecursive($form['entity_reference']['selection_settings'], $ajax_settings);

    // Remove the no-ajax submit button.
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

    // Disable AJAX callback that we don't need.
    unset($form['entity_reference']['selection_settings']['target_bundles']['#ajax']);
    unset($form['entity_reference']['selection_settings']['sort']['field']['#ajax']);

    // Remove user role filter, which is not working correctly.
    // @see \Drupal\user\Plugin\EntityReferenceSelection\UserSelection
    unset($form['entity_reference']['selection_settings']['filter']);

    // Add hide/show #format_items based on #tags.
    if ($this->supportsMultipleValues() && $this->hasProperty('tags')) {
      $form['display']['format_items']['#states'] = [
        'visible' => [
          [':input[name="properties[tags]"]' => ['checked' => TRUE]],
        ],
      ];
    }

    // Tags (only applies to 'entity_autocomplete' element).
    $form['element']['tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tags'),
      '#description' => $this->t('Check this option if the user should be allowed to enter multiple entity references using tags.'),
      '#return_value' => TRUE,
    ];
    if ($this->hasProperty('tags') && $this->hasProperty('multiple')) {
      $form['element']['tags']['#states']['visible'][] = [':input[name="properties[multiple]"]' => ['checked' => FALSE]];
      $form['element']['multiple']['#states']['visible'][] = [':input[name="properties[tags]"]' => ['checked' => FALSE]];
      $form['element']['multiple__header_label']['#states']['visible'][] = [':input[name="properties[tags]"]' => ['checked' => FALSE]];
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

  /**
   * Replace #ajax = TRUE with a work #ajax callback.
   *
   * @param array $element
   *   A element.
   * @param array $ajax_settings
   *   A #ajax callback.
   */
  protected function updateAjaxCallbackRecursive(array &$element, array $ajax_settings) {
    foreach (Element::children($element) as $key) {
      $element[$key]['#access'] = TRUE;
      if (isset($element[$key]['#ajax']) && $element[$key]['#ajax'] === TRUE) {
        $element[$key]['#ajax'] = $ajax_settings;
      }
      $this->updateAjaxCallbackRecursive($element[$key], $ajax_settings);
    }
  }

  /**
   * AJAX callback for entity reference details element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing entity reference details element.
   */
  public function ajaxEntityReference(array $form, FormStateInterface $form_state) {
    $element = $form['properties']['entity_reference'];
    return $element;
  }

}
