<?php

namespace Drupal\inline_entity_form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides content translation helpers.
 */
class TranslationHelper {

  /**
   * Prepares the inline entity for translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The inline entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The prepared entity.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::initFormLangcodes().
   */
  public static function prepareEntity(ContentEntityInterface $entity, FormStateInterface $form_state) {
    $form_langcode = $form_state->get('langcode');
    if (empty($form_langcode) || !$entity->isTranslatable()) {
      return $entity;
    }

    $entity_langcode = $entity->language()->getId();
    if (self::isTranslating($form_state) && !$entity->hasTranslation($form_langcode)) {
      // Create a translation from the source language values.
      $source = $form_state->get(['content_translation', 'source']);
      $source_langcode = $source ? $source->getId() : $entity_langcode;
      $source_translation = $entity->getTranslation($source_langcode);
      $entity->addTranslation($form_langcode, $source_translation->toArray());
      $translation = $entity->getTranslation($form_langcode);
      $translation->set('content_translation_source', $source_langcode);
      // Make sure we do not inherit the affected status from the source values.
      if ($entity->getEntityType()->isRevisionable()) {
        $translation->setRevisionTranslationAffected(NULL);
      }
    }

    if ($entity_langcode != $form_langcode && $entity->hasTranslation($form_langcode)) {
      // Switch to the needed translation.
      $entity = $entity->getTranslation($form_langcode);
    }

    return $entity;
  }

  /**
   * Updates the entity langcode to match the form langcode.
   *
   * Called on submit to allow the user to select a different language through
   * the langcode form element, which is then transferred to form state.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if the entity langcode was updated, FALSE otherwise.
   */
  public static function updateEntityLangcode(ContentEntityInterface $entity, $form_state) {
    $changed = FALSE;
    // This method is first called during form validation, at which point
    // the 'langcode' form state flag hasn't been updated with the new value.
    $form_langcode = $form_state->getValue(['langcode', 0, 'value'], $form_state->get('langcode'));
    if (empty($form_langcode) || !$entity->isTranslatable()) {
      return $changed;
    }

    $entity_langcode = $entity->language()->getId();
    if ($entity_langcode != $form_langcode && !$entity->hasTranslation($form_langcode)) {
      $langcode_key = $entity->getEntityType()->getKey('langcode');
      $entity->set($langcode_key, $form_langcode);
      $changed = TRUE;
    }

    return $changed;
  }

  /**
   * Determines whether there's a translation in progress.
   *
   * If the root entity is being translated, then all of the inline entities
   * are candidates for translating as well.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if translating is in progress, FALSE otherwise.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::initFormLangcodes().
   */
  public static function isTranslating(FormStateInterface $form_state) {
    $form_langcode = $form_state->get('langcode');
    $default_langcode = $form_state->get('entity_default_langcode');
    if (empty($form_langcode) && empty($default_langcode)) {
      // The top-level form is not a content entity form.
      return FALSE;
    }
    else {
      return $form_langcode != $default_langcode;
    }
  }

}
