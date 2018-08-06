<?php

namespace Drupal\crop\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Form controller for crop type forms.
 */
class CropTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    $form['#title'] = $this->operation == 'add' ? $this->t('Add crop type')
        :
        $this->t('Edit %label crop type', array('%label' => $type->label()));

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label,
      '#description' => $this->t('The human-readable name of this crop type. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#machine_name' => [
        'exists' => ['\Drupal\crop\Entity\CropType', 'load'],
        'source' => ['label'],
      ],
      // A crop type's machine name cannot be changed.
      '#disabled' => !$type->isNew(),
      '#description' => $this->t('A unique machine-readable name for this crop type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->description,
      '#description' => $this->t('Describe this crop type.'),
    ];

    $form['aspect_ratio'] = [
      '#title' => $this->t('Aspect Ratio'),
      '#type' => 'textfield',
      '#default_value' => $type->aspect_ratio,
      '#attributes' => ['placeholder' => 'W:H'],
      '#description' => $this->t('Set an aspect ratio <b>eg: 16:9</b> or leave this empty for arbitrary aspect ratio'),
    ];

    $form['soft_limit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Soft limit'),
      '#description' => $this->t('Define crop size soft limit. Warning will be displayed if crop smaller than that is selected.'),
    ];

    $form['soft_limit']['soft_limit_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $type->soft_limit_width,
      '#description' => $this->t('Limit for width.'),
      '#size' => 60,
      '#field_suffix' => 'px',
      '#min' => 0,
    ];
    $form['soft_limit']['soft_limit_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $type->soft_limit_height,
      '#description' => $this->t('Limit for height.'),
      '#size' => 60,
      '#field_suffix' => 'px',
      '#min' => 0,
    ];

    $form['hard_limit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hard limit'),
      '#description' => $this->t('Define crop size hard limit. User is not allowed to make a smaller selection than defined here.'),
    ];

    $form['hard_limit']['hard_limit_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $type->hard_limit_width,
      '#description' => $this->t('Limit for width'),
      '#size' => 60,
      '#field_suffix' => 'px',
      '#min' => 0,
    ];
    $form['hard_limit']['hard_limit_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $type->hard_limit_height,
      '#description' => $this->t('Limit for height.'),
      '#size' => 60,
      '#field_suffix' => 'px',
      '#min' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save crop type');
    $actions['delete']['#value'] = $this->t('Delete crop type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\crop\Entity\CropType $entity */
    $entity = $this->buildEntity($form, $form_state);
    $violations = $entity->validate();

    $this->flagViolations($violations, $form, $form_state);
  }

  /**
   * Flags violations for the current form.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The violations to flag.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function flagViolations(ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display.
    foreach ($violations->getIterator() as $violation) {
      if ($violation->getPropertyPath() == 'aspect_ratio') {
        $form_state->setErrorByName('aspect_ratio', $violation->getMessage());
      }
      if ($violation->getPropertyPath() == 'id') {
        $form_state->setErrorByName('id', $violation->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $type->id = trim($type->id());
    $type->label = trim($type->label);
    $type->aspect_ratio = trim($type->aspect_ratio);

    $status = $type->save();

    $t_args = array('%name' => $type->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The crop type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The crop type %name has been added.', $t_args));
      $context = array_merge($t_args, array('link' => Link::createFromRoute($this->t('View'), 'crop.overview_types')->toString()));
      $this->logger('crop')->notice('Added crop type %name.', $context);
    }

    $form_state->setRedirect('crop.overview_types');
  }

}
