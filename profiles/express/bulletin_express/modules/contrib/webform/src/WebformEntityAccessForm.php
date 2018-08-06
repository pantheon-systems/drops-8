<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform to manage access.
 */
class WebformEntityAccessForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;
    $access = $webform->getAccessRules();
    $permissions = [
      'create' => $this->t('Create webform submissions'),
      'view_any' => $this->t('View all webform submissions'),
      'update_any' => $this->t('Update all webform submissions'),
      'delete_any' => $this->t('Delete all webform submissions'),
      'purge_any' => $this->t('Purge all webform submissions'),
      'view_own' => $this->t('View own webform submissions'),
      'update_own' => $this->t('Update own webform submissions'),
      'delete_own' => $this->t('Delete own webform submissions'),
    ];
    $form['access']['#tree'] = TRUE;
    foreach ($permissions as $name => $title) {
      $form['access'][$name] = [
        '#type' => ($name === 'create') ? 'fieldset' : 'details',
        '#title' => $title,
        '#open' => ($access[$name]['roles'] || $access[$name]['users']) ? TRUE : FALSE,
      ];
      $form['access'][$name]['roles'] = [
        '#type' => 'webform_roles',
        '#title' => $this->t('Roles'),
        '#include_anonymous' => (!in_array($name, ['update_any', 'delete_any', 'purge_any'])) ? TRUE : FALSE,
        '#default_value' => $access[$name]['roles'],
      ];
      $form['access'][$name]['users'] = [
        '#type' => 'webform_users',
        '#title' => $this->t('Users'),
        '#default_value' => $access[$name]['users'] ? $this->entityTypeManager->getStorage('user')->loadMultiple($access[$name]['users']) : [],
      ];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    // Don't display delete button.
    unset($element['delete']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $access = $form_state->getValue('access');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $webform->setAccessRules($access);
    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'access-form')->toString()
    ];
    $this->logger('webform')->notice('Webform access @label saved.', $context);

    drupal_set_message($this->t('Webform access %label saved.', ['%label' => $webform->label()]));
  }

}
