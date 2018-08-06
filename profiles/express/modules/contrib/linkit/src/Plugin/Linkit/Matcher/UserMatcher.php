<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Matcher\UserMatcher.
 */

namespace Drupal\linkit\Plugin\Linkit\Matcher;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * @Matcher(
 *   id = "entity:user",
 *   target_entity = "user",
 *   label = @Translation("User"),
 *   provider = "user"
 * )
 */
class UserMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summery = parent::getSummary();

    $roles = !empty($this->configuration['roles']) ? $this->configuration['roles'] : ['None'];
    $summery[] = $this->t('Role filter: @role_filter', [
      '@role_filter' => implode(', ', $roles),
    ]);

    $summery[] = $this->t('Include blocked users: @include_blocked', [
      '@include_blocked' => $this->configuration['include_blocked'] ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summery;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'roles' => [],
      'include_blocked' => FALSE,
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'module' => ['user'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Restrict to the selected roles'),
      '#options' => array_diff_key(user_role_names(TRUE), array(RoleInterface::AUTHENTICATED_ID => RoleInterface::AUTHENTICATED_ID)),
      '#default_value' =>  $this->configuration['roles'],
      '#description' => $this->t('If none of the checkboxes is checked, allow all roles.'),
      '#element_validate' => [[get_class($this), 'elementValidateFilter']],
    );

    $form['include_blocked'] = [
      '#title' => t('Include blocked user'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['include_blocked'],
      '#description' => t('In order to see blocked users, the requesting user must also have permissions to do so.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['roles'] = $form_state->getValue('roles');
    $this->configuration['include_blocked'] = $form_state->getValue('include_blocked');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match) {
    $query = parent::buildEntityQuery($match);

    $match = $this->database->escapeLike($match);
    // The user entity don't specify a label key so we have to do it instead.
    $query->condition('name', '%' . $match . '%', 'LIKE');

    // Filter by role.
    if (!empty($this->configuration['roles'])) {
      $query->condition('roles', $this->configuration['roles'], 'IN');
    }

    if ($this->configuration['include_blocked'] !== TRUE || !$this->currentUser->hasPermission('administer users')) {
      $query->condition('status', 1);
    }

    return $query;
  }

}
