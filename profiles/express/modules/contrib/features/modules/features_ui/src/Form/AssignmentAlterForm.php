<?php

namespace Drupal\features_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the selected configuration assignment method for this site.
 */
class AssignmentAlterForm extends AssignmentFormBase {

  const METHOD_ID = 'alter';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'features_assignment_alter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bundle_name = NULL) {
    $this->currentBundle = $this->assigner->loadBundle($bundle_name);

    $settings = $this->currentBundle->getAssignmentSettings(self::METHOD_ID);
    $core_setting = $settings['core'];
    $uuid_setting = $settings['uuid'];
    $user_permissions_setting = $settings['user_permissions'];

    $form['core'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip out <em>_core</em> property.'),
      '#default_value' => $core_setting,
      '#description' => $this->t('Select this option to remove the <em>_core</em> configuration property on export. This property is added by Drupal core when configuration is installed.'),
    );

    $form['uuid'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip out <em>uuid</em> property.'),
      '#default_value' => $uuid_setting,
      '#description' => $this->t('Select this option to remove the <em>uuid</em> configuration property on export. This property is added by Drupal core when configuration is installed.'),
    );

    $form['user_permissions'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip out user permissions.'),
      '#default_value' => $user_permissions_setting,
      '#description' => $this->t('Select this option to remove permissions from user roles on export.'),
    );

    $this->setActions($form, self::METHOD_ID);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Merge in selections.
    $settings = $this->currentBundle->getAssignmentSettings(self::METHOD_ID);
    $settings = array_merge($settings, [
      'core' => $form_state->getValue('core'),
      'uuid' => $form_state->getValue('uuid'),
      'user_permissions' => $form_state->getValue('user_permissions'),
    ]);

    $this->currentBundle->setAssignmentSettings(self::METHOD_ID, $settings)->save();

    $this->setRedirect($form_state);
    drupal_set_message($this->t('Package assignment configuration saved.'));
  }

}
