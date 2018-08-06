<?php

/**
 * @file
 * Contains \Drupal\role_delegation\Form\RoleDelegationSettingsForm.
 */

namespace Drupal\role_delegation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\role_delegation\DelegatableRolesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure book settings for this site.
 */
class RoleDelegationSettingsForm extends FormBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\role_delegation\DelegatableRolesInterface
   */
  protected $delegatableRoles;

  /**
   * The roles page setting form.
   *
   * @param \Drupal\role_delegation\DelegatableRolesInterface $delegatable_roles
   *   The role delegation service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user viewing the form.
   */
  public function __construct(DelegatableRolesInterface $delegatable_roles, AccountInterface $current_user) {
    $this->delegatableRoles = $delegatable_roles;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('delegatable_roles'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_delegation_role_assign_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    $current_roles = $user->getRoles(TRUE);
    $current_roles = array_combine($current_roles, $current_roles);

    $form['account']['role_change'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $this->delegatableRoles->getAssignableRoles($this->currentUser),
      '#default_value' => $current_roles,
      '#description' => $this->t('Change roles assigned to user.'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $form_state->getBuildInfo()['args'][0];
    foreach($form_state->getValue('role_change') as $rid => $value) {
      $value === 0 ? $account->removeRole($rid) : $account->addRole($rid);
    }
    $account->save();
    drupal_set_message($this->t('The roles have been updated.'), 'status');
  }

}
