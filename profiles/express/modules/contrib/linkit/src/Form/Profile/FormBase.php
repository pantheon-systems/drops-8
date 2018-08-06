<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Profile\FormBase.
 */

namespace Drupal\linkit\Form\Profile;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for profile add and edit forms.
 */
abstract class FormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profile Name'),
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The human-readable name of this  profile. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => ['\Drupal\linkit\Entity\Profile', 'load']
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t('The text will be displayed on the <em>profile collection</em> page.'),
    ];

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $linkit_profile = $this->entity;

    // Prevent leading and trailing spaces in linkit profile labels.
    $linkit_profile->set('label', trim($linkit_profile->label()));

    $status = $linkit_profile->save();
    $edit_link = $this->entity->link($this->t('Edit'));
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new profile %label.', ['%label' => $linkit_profile->label()]));
        $this->logger('linkit')->notice('Created new profile %label.', ['%label' => $linkit_profile->label(), 'link' => $edit_link]);
        $form_state->setRedirect('linkit.matchers', [
          'linkit_profile' => $linkit_profile->id(),
        ]);
        break;

      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated profile %label.', ['%label' => $linkit_profile->label()]));
        $this->logger('linkit')->notice('Updated profile %label.', ['%label' => $linkit_profile->label(), 'link' => $edit_link]);
        $form_state->setRedirectUrl($linkit_profile->urlInfo('edit-form'));
        break;
    }
  }

}
