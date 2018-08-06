<?php

namespace Drupal\entity_browser\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an Entity Browser form element.
 *
 * Properties:
 * - #entity_browser: Entity browser or ID of the Entity browser to be used.
 * - #cardinality: (optional) Maximum number of items that are expected from
 *     the entity browser. Unlimited by default.
 * - #default_value: (optional) Array of entities that Entity browser should be
 *     initialized with. It's only applicable when edit selection mode is used.
 * - #entity_browser_validators: (optional) Array of validators that are to be
 *     passed to the entity browser. Array keys are plugin IDs and array values
 *     are plugin configuration values. Cardinality validator will be set
 *     automatically.
 * - #selection_mode: (optional) Determines how selection in entity browser will
 *     be handled. Will selection be appended/prepended or it will be replaced
 *     in case of editing. Defaults to append.
 * - #widget_context: (optional) Widget configuration overrides which enable
 *     use cases where the instance of a widget needs awareness of contextual
 *     configuration like field settings.
 *
 * Return value will be an array of selected entities, which will appear under
 * 'entities' key on the root level of the element's values in the form state.
 *
 * @FormElement("entity_browser")
 */
class EntityBrowserElement extends FormElement {

  /**
   * Indicating an entity browser can return an unlimited number of values.
   *
   * Note: When entity browser is used in Fields, cardinality is directly
   * propagated from Field settings, that's why this constant should be equal to
   * FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED.
   */
  const CARDINALITY_UNLIMITED = -1;

  /**
   * Selection from entity browser will be appended to existing list.
   *
   * When this selection mode is used, then entity browser will not be
   * populated with existing selection. Preselected list will be empty.
   *
   * Note: This option is also used by "js/entity_browser.common.js".
   */
  const SELECTION_MODE_APPEND = 'selection_append';

  /**
   * Selection from entity browser will be prepended to existing list.
   *
   * When this selection mode is used, then entity browser will not be
   * populated with existing selection. Preselected list will be empty.
   *
   * Note: This option is also used by "js/entity_browser.common.js".
   */
  const SELECTION_MODE_PREPEND = 'selection_prepend';

  /**
   * Selection from entity browser will replace existing.
   *
   * When this selection mode is used, then entity browser will be populated
   * with existing selection and returned selected list will replace existing
   * selection. This option requires entity browser selection display with
   * preselection support.
   *
   * Note: This option is also used by "js/entity_browser.common.js".
   */
  const SELECTION_MODE_EDIT = 'selection_edit';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#cardinality' => static::CARDINALITY_UNLIMITED,
      '#selection_mode' => static::SELECTION_MODE_APPEND,
      '#process' => [[$class, 'processEntityBrowser']],
      '#default_value' => [],
      '#entity_browser_validators' => [],
      '#widget_context' => [],
      '#attached' => ['library' => ['entity_browser/common']],
    ];
  }

  /**
   * Get selection mode options.
   *
   * @return array
   *   Selection mode options.
   */
  public static function getSelectionModeOptions() {
    return [
      static::SELECTION_MODE_APPEND => t('Append to selection'),
      static::SELECTION_MODE_PREPEND => t('Prepend selection'),
      static::SELECTION_MODE_EDIT => t('Edit selection'),
    ];
  }

  /**
   * Check whether entity browser should be available for selection of entities.
   *
   * @param string $selection_mode
   *   Used selection mode.
   * @param int $cardinality
   *   Used cardinality.
   * @param int $preselection_size
   *   Preseletion size, if it's available.
   *
   * @return bool
   *   Returns positive if entity browser can be used.
   */
  public static function isEntityBrowserAvailable($selection_mode, $cardinality, $preselection_size) {
    if ($selection_mode == static::SELECTION_MODE_EDIT) {
      return TRUE;
    }

    $cardinality_exceeded =
      $cardinality != static::CARDINALITY_UNLIMITED
      && $preselection_size >= $cardinality;

    return !$cardinality_exceeded;
  }

  /**
   * Render API callback: Processes the entity browser element.
   */
  public static function processEntityBrowser(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    if (is_string($element['#entity_browser'])) {
      $entity_browser = EntityBrowser::load($element['#entity_browser']);
    }
    else {
      $entity_browser = $element['#entity_browser'];
    }

    // Propagate selection if edit selection mode is used.
    $entity_browser_preselected_entities = [];
    if ($element['#selection_mode'] === static::SELECTION_MODE_EDIT) {
      $entity_browser->getSelectionDisplay()->checkPreselectionSupport();

      $entity_browser_preselected_entities = $element['#default_value'];
    }

    $default_value = implode(' ', array_map(
      function (EntityInterface $item) {
        return $item->getEntityTypeId() . ':' . $item->id();
      },
      $entity_browser_preselected_entities
    ));
    $validators = array_merge(
      $element['#entity_browser_validators'],
      ['cardinality' => ['cardinality' => $element['#cardinality']]]
    );

    // Display error message if the entity browser was not found.
    if (!$entity_browser) {
      $element['entity_browser'] = [
        '#type' => 'markup',
        '#markup' => is_string($element['#entity_browser']) ? t('Entity browser @browser not found.', ['@browser' => $element['#entity_browser']]) : t('Entity browser not found.'),
      ];
    }
    // Display entity_browser
    else {
      $display = $entity_browser->getDisplay();
      $display->setUuid(sha1(implode('-', array_merge([$complete_form['#build_id']], $element['#parents']))));
      $element['entity_browser'] = [
        '#eb_parents' => array_merge($element['#parents'], ['entity_browser']),
      ];
      $element['entity_browser'] = $display->displayEntityBrowser(
        $element['entity_browser'],
        $form_state,
        $complete_form,
        [
          'validators' => $validators,
          'selected_entities' => $entity_browser_preselected_entities,
          'widget_context' => $element['#widget_context'],
        ]
      );

      $hidden_id = Html::getUniqueId($element['#id'] . '-target');
      $element['entity_ids'] = [
        '#type' => 'hidden',
        '#id' => $hidden_id,
        // We need to repeat ID here as it is otherwise skipped when rendering.
        '#attributes' => ['id' => $hidden_id, 'class' => ['eb-target']],
        '#default_value' => $default_value,
      ];

      $element['#attached']['drupalSettings']['entity_browser'] = [
        $entity_browser->getDisplay()->getUuid() => [
          'cardinality' => $element['#cardinality'],
          'selection_mode' => $element['#selection_mode'],
          'selector' => '#' . $hidden_id,
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return $element['#default_value'] ?: [];
    }

    $entities = [];
    if ($input['entity_ids']) {
      $entities = static::processEntityIds($input['entity_ids']);
    }

    return ['entities' => $entities];
  }

  /**
   * Processes entity IDs and gets array of loaded entities.
   *
   * @param array|string $ids
   *   Processes entity IDs as they are returned from the entity browser. They
   *   are in [entity_type_id]:[entity_id] form. Array of IDs or a
   *   space-delimited string is supported.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entity objects.
   */
  public static function processEntityIds($ids) {
    if (!is_array($ids)) {
      $ids = array_filter(explode(' ', $ids));
    }

    return array_map(
      function ($item) {
        list($entity_type, $entity_id) = explode(':', $item);
        return \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
      },
      $ids
    );
  }

  /**
   * Processes entity IDs and gets array of loaded entities.
   *
   * @param string $id
   *   Processes entity ID as it is returned from the entity browser. ID should
   *   be in [entity_type_id]:[entity_id] form.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Entity object.
   */
  public static function processEntityId($id) {
    $return = static::processEntityIds([$id]);
    return current($return);
  }

}
