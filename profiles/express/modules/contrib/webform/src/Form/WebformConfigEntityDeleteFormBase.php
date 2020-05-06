<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\EntityDeleteFormTrait;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a generic base class for a webform entity deletion form.
 *
 * Copied from: \Drupal\Core\Entity\EntityConfirmFormBase.
 */
abstract class WebformConfigEntityDeleteFormBase extends EntityForm implements WebformDeleteFormInterface {

  use EntityDeleteFormTrait;
  use WebformDialogFormTrait;

  /**
   * Display confirmation checkbox.
   *
   * @var bool
   */
  protected $confirmCheckbox = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return $this->entity->getEntityTypeId() . '_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->isDialog()) {
      $t_args = [
        '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
        '@label' => $this->getEntity()->label(),
      ];
      return $this->t("Delete '@label' @entity-type?", $t_args);
    }
    else {
      $t_args = [
        '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
        '%label' => $this->getEntity()->label(),
      ];
      return $this->t('Delete %label @entity-type?', $t_args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    $t_args = [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ];

    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to delete the %label @entity-type?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmInput() {
    $t_args = [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ];

    if ($this->confirmCheckbox) {
      return [
        '#type' => 'checkbox',
        '#title' => $this->t('Yes, I want to delete the %label @entity-type', $t_args),
        '#required' => TRUE,
      ];
    }
    else {
      return [];
    }
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
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'webform_config_entity_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attributes']['class'][] = 'confirmation';
    $form['#theme'] = 'confirm_form';
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

    // Title.
    $form['#title'] = $this->getQuestion();

    // Warning.
    $form['warning'] = $this->getWarning();

    // Description.
    $form['description'] = $this->getDescription();

    // Details and confirm input.
    $details = $this->getDetails();
    $confirm_input = $this->getConfirmInput();
    if ($details) {
      $form['details'] = $details;
    }
    if (!$details && $confirm_input) {
      $form['hr'] = ['#markup' => '<p><hr/></p>'];
    }
    if ($confirm_input) {
      $form['confirm'] = $confirm_input;
    }

    // Dialog.
    return $this->buildDialogConfirmForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#submit' => [
          [$this, 'submitForm'],
        ],
      ],
      'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The save() method is not used in EntityConfirmFormBase. This overrides the
   * default implementation that saves the entity.
   *
   * Confirmation forms should override submitForm() instead for their logic.
   */
  public function save(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   *
   * The delete() method is not used in EntityConfirmFormBase. This overrides
   * the default implementation that redirects to the delete-form confirmation
   * form.
   *
   * Confirmation forms should override submitForm() instead for their logic.
   */
  public function delete(array $form, FormStateInterface $form_state) {}

}
