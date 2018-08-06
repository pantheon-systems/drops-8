<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Form\FormStateInterface;

/**
 * Webform access settings.
 */
class WebformEntitySettingsAccessForm extends WebformEntitySettingsBaseForm {

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
      $form['access'][$name]['permissions'] = [
        '#type' => 'webform_permissions',
        '#title' => $this->t('Permissions'),
        '#multiple' => TRUE,
        '#select2' => TRUE,
        '#default_value' => $access[$name]['permissions'],
      ];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $access = $form_state->getValue('access');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $webform->setAccessRules($access);

    parent::save($form, $form_state);
  }

}
