<?php

namespace Drupal\webform_group\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\group\Entity\GroupRole;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform group roles element.
 *
 * @FormElement("webform_group_roles")
 */
class WebformGroupRoles extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    $info['#element_validate'] = [
      [$class, 'validateWebformGroupRoles'],
    ];
    $info['#include_internal'] = TRUE;
    $info['#include_user_roles'] = FALSE;
    $info['#include_anonymous'] = FALSE;
    $info['#include_outsider'] = TRUE;
    $info['#multiple'] = TRUE;
    return $info;
  }

  /**
   * Processes a webform roles (checkboxes) element.
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#options'] = static::getGroupRolesOptions($element);
    $element['#select2'] = TRUE;

    // Must convert this element['#type'] to a 'select' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'select';

    WebformElementHelper::process($element);
    return parent::processSelect($element, $form_state, $complete_form);
  }

  /**
   * Webform element validation handler for webform roles (checkboxes) element.
   */
  public static function validateWebformGroupRoles(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#multiple'])) {
      $value = array_values($form_state->getValue($element['#parents'], []));
      $element['#value'] = $value;
      $form_state->setValueForElement($element, $value);
    }
  }

  /**
   * Get group roles options for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   Group roles options for an element.
   */
  public static function getGroupRolesOptions(array $element) {
    $element += [
      '#include_internal' => TRUE,
      '#include_user_roles' => FALSE,
      '#include_anonymous' => FALSE,
      '#include_outsider' => TRUE,
    ];

    /** @var \Drupal\group\Entity\GroupRoleInterface[] $group_roles */
    $group_roles = GroupRole::loadMultiple();
    $group_role_names = [];

    $options = [];
    foreach ($group_roles as $group_role) {
      if (!$element['#include_internal'] && $group_role->isInternal()) {
        continue;
      }
      if (!$element['#include_user_roles'] && !$group_role->inPermissionsUI()) {
        continue;
      }
      if (!$element['#include_anonymous'] && $group_role->isAnonymous()) {
        continue;
      }
      if (!$element['#include_outsider'] && $group_role->isOutsider()) {
        continue;
      }
      $group_role_id = $group_role->id();
      $group_role_label = $group_role->label();

      $group_type = $group_role->getGroupType();
      $group_type_id = $group_type->id();
      $group_type_label = $group_type->label();

      $t_args = [
        '@group_type' => $group_type_label,
        '@group_role' => $group_role_label,
      ];

      $options[$group_type_label][$group_role_id] = t('@group_type: @group_role', $t_args);

      $group_role_name = preg_replace("/^$group_type_id-/", "", $group_role_id);
      $group_role_names[$group_role_name] = $group_role_label;
    }
    ksort($options);

    return [(string) t('Group role types') => $group_role_names] + $options;
  }

}
