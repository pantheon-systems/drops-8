<?php

/**
 * @file
 * Webform module query hooks.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\webform\Entity\Webform;

/**
 * Implements hook_query_TAG_alter().
 *
 * Append EAV sort to webform_submission entity query.
 *
 * @see http://stackoverflow.com/questions/12893314/sorting-eav-database
 * @see \Drupal\webform\WebformSubmissionListBuilder::getEntityIds
 */
function webform_query_webform_submission_list_builder_alter(AlterableInterface $query) {
  /** @var \Drupal\Core\Database\Query\SelectInterface $query */
  $name = $query->getMetaData('webform_submission_element_name');
  $direction = $query->getMetaData('webform_submission_element_direction');
  $property_name = $query->getMetaData('webform_submission_element_property_name');

  $query->distinct();
  $query->addJoin('INNER', 'webform_submission_data', 'webform_submission_data_list_builder_element', 'base_table.sid = webform_submission_data_list_builder_element.sid');
  $query->addField('webform_submission_data_list_builder_element', 'value');
  $query->condition('webform_submission_data_list_builder_element.name', $name);
  if ($property_name) {
    $query->condition('webform_submission_data_list_builder_element.property', $property_name);
  }
  $query->orderBy('webform_submission_data_list_builder_element.value', $direction);
}

/**
 * Implements hook_query_TAG_alter().
 */
function webform_query_entity_reference_alter(AlterableInterface $query) {
  /** @var \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection $handler */
  $handler = $query->getMetaData('entity_reference_selection_handler');

  // Get webform settings used to limit and randomize results.
  // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::getTestValues
  // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::setOptions
  // @see \Drupal\webform\Element\WebformEntityTrait::setOptions
  $configuration = $handler->getConfiguration() + ['_webform_settings' => []];
  $settings = $configuration['_webform_settings'];
  if (!empty($settings['random'])) {
    $query->orderRandom();
  }
  if (!empty($settings['limit'])) {
    $query->range(0, $settings['limit']);
  }
}

/**
 * Implements hook_query_TAG_alter().
 *
 * This hook implementation adds webform submission access checks for the
 * account stored in the 'account' meta-data (or current user if not provided),
 * for an operation stored in the 'op' meta-data (or 'view' if not provided).
 */
function webform_query_webform_submission_access_alter(AlterableInterface $query) {
  /** @var \Drupal\Core\Database\Query\SelectInterface $query */
  $op = $query->getMetaData('op') ?: 'view';
  $account = $query->getMetaData('account') ?: \Drupal::currentUser();

  $entity_type = \Drupal::entityTypeManager()->getDefinition('webform_submission');

  // Get webform submission tables which are used to build the alter query.
  $webform_submission_tables = [];
  foreach ($query->getTables() as $table) {
    if (is_string($table['table']) && $table['table'] === $entity_type->getBaseTable()) {
      $webform_submission_tables[] = [
        'alias' => $table['alias'],
        'condition' => $query->orConditionGroup(),
      ];
    }
  }

  // If there are no webform submission tables then nothing needs to be altered.
  if (empty($webform_submission_tables)) {
    return;
  }

  // If the user has administer access then exit.
  if ($account->hasPermission('administer webform submission')
    || $account->hasPermission('administer webform')) {
    return;
  }

  // Apply operation specific any and own permissions.
  if (in_array($op, ['view', 'edit', 'delete'])) {
    $permission_any = "$op any webform submission";
    $permission_own = "$op own webform submission";

    // If the user has any permission the query does not have to be altered.
    if ($account->hasPermission($permission_any)) {
      return;
    }

    // If the user has own permission, then add the account id to all
    // webform submission tables conditions.
    if ($account->hasPermission($permission_own)) {
      foreach ($webform_submission_tables as $table) {
        $table['condition']->condition($table['alias'] . '.uid', $account->id());
      }
    }
  }

  // Alter query based on update access to all webforms.
  /** @var \Drupal\webform\WebformInterface[] $webforms */
  if ($account->isAuthenticated()) {
    // Get cached list of webforms that the user can update so that we don't
    // have to continually load every webform.
    $cached = \Drupal::cache()->get('webform_submission_access__account_update__' . $account->id());
    if ($cached) {
      $webform_account_access_update = $cached->data;
    }
    else {
      $webform_account_access_update = [];
      /** @var \Drupal\webform\WebformInterface[] $webforms */
      $webforms = Webform::loadMultiple();
      foreach ($webforms as $webform) {
        if ($webform->access('update', $account)) {
          $webform_account_access_update[] = $webform->id();
        }
      }
      \Drupal::cache()->set(
        'webform_submission_access__account_update__' . $account->id(),
        $webform_account_access_update,
        Cache::PERMANENT,
        ['config:webform_list', 'user:' . $account->id()]
      );
    }
    foreach ($webform_account_access_update as $webform_id) {
      foreach ($webform_submission_tables as $table) {
        $table['condition']->condition($table['alias'] . '.webform_id', $webform_id);
      }
    }
  }
  else {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $sids = $submission_storage->getAnonymousSubmissionIds($account);
    if ($sids) {
      foreach ($webform_submission_tables as $table) {
        $table['condition']->condition($table['alias'] . '.sid', $sids, 'IN');
      }
    }
  }

  // Alter query based on access rules.
  /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
  $access_rules_manager = \Drupal::service('webform.access_rules_manager');

  // Get cached webform access rules and cache tags so that we don't have
  // to continually load every webform.
  $cached = \Drupal::cache()->get('webform_submission_access__webform_access_rules');
  if ($cached) {
    $webform_access_rules = $cached->data;
  }
  else {
    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = Webform::loadMultiple();
    $webform_access_rules = [];
    foreach ($webforms as $webform_id => $webform) {
      $webform_access_rules[$webform_id] = $access_rules_manager->getAccessRules($webform) ?: [];
    }
    \Drupal::cache()->set(
      'webform_submission_access__webform_access_rules',
      $webform_access_rules,
      Cache::PERMANENT,
      ['config:webform_list']
    );
  }

  foreach ($webform_access_rules as $webform_id => $access_rules) {
    // Check basic and any access rules and add webform id to all
    // webform submission tables conditions.
    if ($access_rules_manager->checkAccessRules($op, $account, $access_rules)
      || $access_rules_manager->checkAccessRules($op . '_any', $account, $access_rules)) {
      foreach ($webform_submission_tables as $table) {
        $table['condition']->condition($table['alias'] . '.webform_id', $webform_id);
      }
    }
    // If the user has own access rules, then add the account id to all
    // webform submission tables conditions.
    elseif ($access_rules_manager->checkAccessRules($op . '_own', $account, $access_rules)) {
      foreach ($webform_submission_tables as $table) {
        /** @var \Drupal\Core\Database\Query\SelectInterface $query */
        $condition = $query->andConditionGroup();
        $condition->condition($table['alias'] . '.uid', $account->id());
        $condition->condition($table['alias'] . '.webform_id', $webform_id);
        $table['condition']->condition($condition);
      }
    }
  }

  // Allow modules to alter and add their query access rules.
  \Drupal::moduleHandler()->alter(
    'webform_submission_query_access',
    $query,
    $webform_submission_tables
  );

  // Apply webform submission table conditions to query.
  foreach ($webform_submission_tables as $table) {
    // If a webform submission table does not have any conditions,
    // we have to block access to the table.
    if (count($table['condition']->conditions()) === 1) {
      $table['condition']->where('1 = 0');
    }

    $query->condition($table['condition']);
  }
}
