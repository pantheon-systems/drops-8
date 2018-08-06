<?php

namespace Drupal\inline_entity_form\Element;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\inline_entity_form\TranslationHelper;

/**
 * Provides an inline entity form element.
 *
 * Usage example:
 * @code
 * $form['article'] = [
 *   '#type' => 'inline_entity_form',
 *   '#entity_type' => 'node',
 *   '#bundle' => 'article',
 *   // If the #default_value is NULL, a new entity will be created.
 *   '#default_value' => $loaded_article,
 * ];
 * @endcode
 * To access the entity in validation or submission callbacks, use
 * $form['article']['#entity']. Due to Drupal core limitations the entity
 * can't be accessed via $form_state->getValue('article').
 *
 * @RenderElement("inline_entity_form")
 */
class InlineEntityForm extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#ief_id' => '',
      '#entity_type' => NULL,
      '#bundle' => NULL,
      '#langcode' => NULL,
      // Instance of \Drupal\Core\Entity\EntityInterface. If NULL, a new
      // entity will be created.
      '#default_value' => NULL,
      // The form mode used to display the entity form.
      '#form_mode' => 'default',
      // Will save entity on submit if set to TRUE.
      '#save_entity' => TRUE,
      // 'add' or 'edit'. If NULL, determined by whether the entity is new.
      '#op' => NULL,
      '#process' => [
        [$class, 'processEntityForm'],
      ],
      '#element_validate' => [
        [$class, 'validateEntityForm'],
      ],
      '#ief_element_submit' => [
        [$class, 'submitEntityForm'],
      ],
      '#theme_wrappers' => ['container'],
      // Allow inline forms to use the #fieldset key.
      '#pre_render' => [
        [$class, 'addFieldsetMarkup'],
      ],
    ];
  }

  /**
   * Builds the entity form using the inline form handler.
   *
   * @param array $entity_form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the #entity_type or #bundle properties are empty, or when
   *   the #default_value property is not an entity.
   *
   * @return array
   *   The built entity form.
   */
  public static function processEntityForm($entity_form, FormStateInterface $form_state, &$complete_form) {
    if (empty($entity_form['#entity_type'])) {
      throw new \InvalidArgumentException('The inline_entity_form element requires the #entity_type property.');
    }
    if (isset($entity_form['#default_value']) && !($entity_form['#default_value'] instanceof EntityInterface)) {
      throw new \InvalidArgumentException('The inline_entity_form #default_value property must be an entity object.');
    }

    if (empty($entity_form['#ief_id'])) {
      $entity_form['#ief_id'] = \Drupal::service('uuid')->generate();
    }
    if (isset($entity_form['#default_value'])) {
      // Transfer the #default_value to #entity, as expected by inline forms.
      $entity_form['#entity'] = $entity_form['#default_value'];
    }
    else {
      // This is an add operation, create a new entity.
      $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_form['#entity_type']);
      $storage = \Drupal::entityTypeManager()->getStorage($entity_form['#entity_type']);
      $values = [];
      if ($langcode_key = $entity_type->getKey('langcode')) {
        if (!empty($entity_form['#langcode'])) {
          $values[$langcode_key] = $entity_form['#langcode'];
        }
      }
      if ($bundle_key = $entity_type->getKey('bundle')) {
        $values[$bundle_key] = $entity_form['#bundle'];
      }
      $entity_form['#entity'] = $storage->create($values);
    }
    if (!isset($entity_form['#op'])) {
      $entity_form['#op'] = $entity_form['#entity']->isNew() ? 'add' : 'edit';
    }
    // Prepare the entity form and the entity itself for translating.
    $entity_form['#entity'] = TranslationHelper::prepareEntity($entity_form['#entity'], $form_state);
    $entity_form['#translating'] = TranslationHelper::isTranslating($form_state) && $entity_form['#entity']->isTranslatable();

    $inline_form_handler = static::getInlineFormHandler($entity_form['#entity_type']);
    $entity_form = $inline_form_handler->entityForm($entity_form, $form_state);
    // The form element can't rely on inline_entity_form_form_alter() calling
    // ElementSubmit::attach() since form alters run before #process callbacks.
    ElementSubmit::attach($complete_form, $form_state);

    return $entity_form;
  }

  /**
   * Validates the entity form using the inline form handler.
   *
   * @param array $entity_form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateEntityForm(&$entity_form, FormStateInterface $form_state) {
    $inline_form_handler = static::getInlineFormHandler($entity_form['#entity_type']);
    $inline_form_handler->entityFormValidate($entity_form, $form_state);
  }

  /**
   * Handles the submission of the entity form using the inline form handler.
   *
   * @param array $entity_form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitEntityForm(&$entity_form, FormStateInterface $form_state) {
    $inline_form_handler = static::getInlineFormHandler($entity_form['#entity_type']);
    $inline_form_handler->entityFormSubmit($entity_form, $form_state);
    if ($entity_form['#save_entity']) {
      $inline_form_handler->save($entity_form['#entity']);
    }
  }

  /**
   * Gets the inline form handler for the given entity type.
   *
   * @param string $entity_type
   *   The entity type id.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the entity type has no inline form handler defined.
   *
   * @return \Drupal\inline_entity_form\InlineFormInterface
   *   The inline form handler.
   */
  public static function getInlineFormHandler($entity_type) {
    $inline_form_handler = \Drupal::entityTypeManager()->getHandler($entity_type, 'inline_form');
    if (empty($inline_form_handler)) {
      throw new \InvalidArgumentException(sprintf('The %s entity type has no inline form handler.', $entity_type));
    }

    return $inline_form_handler;
  }

  /**
   * Pre-render callback for the #fieldset form property.
   *
   * Inline forms use #tree = TRUE to keep their values in a hierarchy for
   * easier storage. Moving the form elements into fieldsets during form
   * building would break up that hierarchy, so it's not an option for entity
   * fields. Therefore, we wait until the pre_render stage, where any changes
   * we make affect presentation only and aren't reflected in $form_state.
   *
   * @param array $entity_form
   *   The entity form.
   *
   * @return array
   *   The modified entity form.
   */
  public static function addFieldsetMarkup($entity_form) {
    $sort = [];
    foreach (Element::children($entity_form) as $key) {
      $element = $entity_form[$key];
      if (isset($element['#fieldset']) && isset($entity_form[$element['#fieldset']])) {
        $entity_form[$element['#fieldset']][$key] = $element;
        // Remove the original element this duplicates.
        unset($entity_form[$key]);
        // Mark the fieldset for sorting.
        if (!in_array($key, $sort)) {
          $sort[] = $element['#fieldset'];
        }
      }
    }

    // Sort all fieldsets, so that element #weight stays respected.
    foreach ($sort as $key) {
      uasort($entity_form[$key], '\Drupal\Component\Utility\SortArray::sortByWeightProperty');
    }

    return $entity_form;
  }

}
