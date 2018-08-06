<?php
/**
 * @file
 * Hooks for simpleSAMLphp Authentication module.
 */

/**
 * Hook to alter the roles assigned to a SAML-authenticated user.
 *
 * Whenever a user's roles are evaluated this hook will be called, allowing
 * custom logic to be used to alter or even completely replace the roles
 * evaluated.
 *
 * @param array &$roles
 *   The roles that have been selected for the current user
 *   by the role evaluation process.
 * @param array $attributes
 *   The SimpleSAMLphp attributes for this user.
 */
function hook_simplesamlphp_auth_user_roles_alter(&$roles, $attributes) {
  if (isset($attributes['roles'])) {
    // The roles provided by the IdP.
    $sso_roles = $attributes['roles'];

    // Match role names in the saml attributes to local role names.
    $user_roles = array_intersect(user_roles(), $sso_roles);

    foreach (array_keys($user_roles) as $rid) {
      $roles[$rid] = $rid;
    }
  }
}

/**
 * Hook to specify if a SAML-authenticated user is allowed to login.
 *
 * Allows other modules to decide whether user with the given set of
 * attributes is allowed to log in via SSO or not.
 *
 * Each implementation should take care of displaying errors, there is no
 * message implementation at hook invocation. Implementations should return
 * a boolean indicating the success of the access check. Access will be denied
 * if any implementations return FALSE.
 *
 * @param array $attributes
 *   The SimpleSAMLphp attributes for this user.
 *
 * @return bool
 *   TRUE if SAML user is allowed to log in, FALSE if not.
 */
function hook_simplesamlphp_auth_allow_login($attributes) {
  if (in_array('student', $attributes['roles'])) {
    return FALSE;
  }
  else {
    return TRUE;
  }
}

/**
 * Hook to alter the assigned authname of a pre-existing Drupal user.
 *
 * Allows other modules to change the authname that is being stored when
 * a pre-existing Drupal user account gets SAML-enabled.
 * This is done by clicking the checkbox "Enable this user to leverage SAML
 * authentication" upon user registration or the user edit form (given enough
 * permissions).
 *
 * For example, this allows you to pre-register Drupal accounts and store the
 * entered email address (rather than the default username) as the authname.
 * The SAML user with that email address as authname will then be able to login
 * as that Drupal user.
 *
 * @param string $authname
 *   The current authname that will be assigned this user (default: username).
 * @param \Drupal\user\UserInterface $account
 *   The pre-existing Drupal user to be SAML-enabled.
 */
function hook_simplesamphp_auth_account_authname_alter(&$authname, \Drupal\user\UserInterface $account) {
  $authname = $account->mail;
}


/**
 * Hook to map pre-existing Drupal user based on SAML attributes.
 *
 * Allows other modules to decide if there is an existing Drupal user that
 * should be linked with the SAML-authenticated user authname, based on the
 * supplied SAML atttributes.
 *
 * E.g. When a SAML-authenticated user logs in, try to find an existing Drupal
 * user which has the same email address as specified in the SAML attributes.
 * In that case the existing Drupal user and SAML-authenticated user will be
 * linked, and that Drupal user will be loaded and logged in upon successful
 * SAML authentication.
 *
 * @param array $attributes
 *   The SimpleSAMLphp attributes for this user.
 *
 * @return \Drupal\user\UserInterface | bool
 *   The pre-existing Drupal user to be SAML-enabled, or FALSE if none found.
 */
function hook_simplesamlphp_auth_existing_user($attributes) {
  $saml_mail = $attributes['mail'];
  $existing_users = \Drupal::service('entity.manager')->getStorage('user')->loadByProperties(['mail' => $saml_mail]);
  if ($existing_users) {
    $existing_user = is_array($existing_users) ? reset($existing_users) : FALSE;
    if ($existing_user) {
      return $existing_user;
    }
  }
  return FALSE;
}

/**
 * Hook to alter a Drupal user account after SAML authentication.
 *
 * Allows other modules to change fields or properties on the Drupal account
 * after a user logged in through SimpleSAMLphp. This can be used to add
 * map additional SAML attributes to Drupal user profile fields.
 *
 * @param \Drupal\user\UserInterface $account
 *   The Drupal account that can be altered.
 * @param array $attributes
 *   The SimpleSAMLphp attributes for this user.
 *
 * @return \Drupal\user\UserInterface|bool
 *   The altered Drupal account or FALSE if nothing was changed.
 */
function hook_simplesamlphp_auth_user_attributes(\Drupal\user\UserInterface $account, $attributes) {
  $saml_first_name = $attributes['first_name'];
  if ($saml_first_name) {
    $account->set('field_first_name', $saml_first_name);
    return $account;
  }
  return FALSE;
}
