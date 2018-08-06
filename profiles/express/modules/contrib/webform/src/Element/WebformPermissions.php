<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform roles (select) element.
 *
 * @FormElement("webform_permissions")
 */
class WebformPermissions extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    $info['#element_validate'] = [
      [$class, 'validateWebformPermissions'],
    ];
    return $info;
  }

  /**
   * Processes a webform roles (select) element.
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\user\PermissionHandlerInterface $permission_handler */
    $permission_handler = \Drupal::service('user.permissions');
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');

    // Get list of permissions as options.
    $options = [];
    $permissions = $permission_handler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $display_name = $module_handler->getName($provider);
      $options[$display_name][$perm] = strip_tags($perm_item['title']);
    }
    $element['#options'] = $options;

    WebformElementHelper::enhanceSelect($element, TRUE);

    // Must convert this element['#type'] to a 'select' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'select';

    return parent::processSelect($element, $form_state, $complete_form);
  }

  /**
   * Webform element validation handler for webform roles (select) element.
   */
  public static function validateWebformPermissions(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#multiple'])) {
      $value = $form_state->getValue($element['#parents'], []);
      $form_state->setValueForElement($element, array_values($value));
    }
  }

}
