<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Plugin implementation of the 'Webform rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "webform_entity_reference_entity_view",
 *   label = @Translation("Webform"),
 *   description = @Translation("Display the referenced webform with default submission data."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformEntityReferenceEntityFormatter extends WebformEntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'source_entity' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Set submission source entity: @source_entity', ['@source_entity' => $this->getSetting('source_entity') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_definition = \Drupal::entityTypeManager()->getDefinition(
      $this->fieldDefinition->getTargetEntityTypeId());
    $form = parent::settingsForm($form, $form_state);
    $form['source_entity'] = [
      '#title' => $this->t("Use this field's %entity_type entity as the webform submission's source entity.", ['%entity_type' => $entity_type_definition->getLabel()]),
      '#description' => $this->t("If unchecked, the current page's entity will be used as the webform submission's source entity. For example, if this webform was displayed on a node's page, the current node would be used as the webform submission's source entity.", ['%entity_type' => $entity_type_definition->getLabel()]),
      '#type' => 'checkbox',
      '#return_type' => TRUE,
      '#default_value' => $this->getSetting('source_entity'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $source_entity = $items->getEntity();
    $this->messageManager->setSourceEntity($source_entity);

    // Determine if webform is previewed within a Paragraph on .edit_form.
    $is_paragraph_edit_preview = ($source_entity->getEntityTypeId() === 'paragraph' && preg_match('/\.edit_form$/', \Drupal::routeMatch()->getRouteName())) ? TRUE : FALSE;

    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Do not display the webform if the current user can't
      // create submissions.
      if ($entity->id() && !$entity->access('submission_create')) {
        $elements[$delta] = [];
      }
      elseif ($is_paragraph_edit_preview) {
        // Webform can not be nested within node edit form because the nested
        // <form> tags will cause unexpected validation issues.
        $elements[$delta] = [
          '#type' => 'webform_message',
          '#message_message' => $this->t('%label webform can not be previewed when editing content.', ['%label' => $entity->label()]),
          '#message_type' => 'info',
        ];
      }
      else {
        $values = [];
        if ($this->getSetting('source_entity')) {
          $values += [
            'entity_type' => $source_entity->getEntityTypeId(),
            'entity_id' => $source_entity->id(),
          ];
        }
        if (!empty($items[$delta]->default_data)) {
          $values['data'] = Yaml::decode($items[$delta]->default_data);
        }
        $elements[$delta] = $entity->getSubmissionForm($values);
      }
      $this->setCacheContext($elements[$delta], $entity, $items[$delta]);
    }
    return $elements;
  }

}
