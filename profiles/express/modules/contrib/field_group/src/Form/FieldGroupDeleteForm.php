<?php

namespace Drupal\field_group\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_group\FieldgroupUi;

/**
 * Provides a form for removing a fieldgroup from a bundle.
 */
class FieldGroupDeleteForm extends ConfirmFormBase {

  /**
   * The fieldgroup to delete.
   *
   * @var stdClass
   */
  protected $fieldGroup;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_group_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $field_group_name = NULL, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $mode = $this->getRequest()->attributes->get('form_mode_name');
    }
    else {
      $mode = $this->getRequest()->attributes->get('view_mode_name');
    }

    if (empty($mode)) {
      $mode = 'default';
    }

    $this->fieldGroup = field_group_load_field_group($field_group_name, $entity_type_id, $bundle, $context, $mode);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = entity_get_bundles();
    $bundle_label = $bundles[$this->fieldGroup->entity_type][$this->fieldGroup->bundle]['label'];

    field_group_group_delete($this->fieldGroup);

    drupal_set_message(t('The group %group has been deleted from the %type content type.', array('%group' => t($this->fieldGroup->label), '%type' => $bundle_label)));

    // Redirect.
    $form_state->setRedirectUrl($this->getCancelUrl());

  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the group %group?', array('%group' => t($this->fieldGroup->label)));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return FieldgroupUi::getFieldUiRoute($this->fieldGroup);
  }

}
