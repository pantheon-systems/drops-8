<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformAccessRulesManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform access settings.
 */
class WebformEntitySettingsAccessForm extends WebformEntitySettingsBaseForm {

  /**
   * Webform access rules manager.
   *
   * @var \Drupal\webform\WebformAccessRulesManagerInterface
   */
  protected $accessRulesManager;

  /**
   * WebformEntitySettingsAccessForm constructor.
   *
   * @param \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager
   *   Webform access rules manager.
   */
  public function __construct(WebformAccessRulesManagerInterface $access_rules_manager) {
    $this->accessRulesManager = $access_rules_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.access_rules_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    $form['access']['#tree'] = TRUE;

    $access = $webform->getAccessRules() + $this->accessRulesManager->getDefaultAccessRules();
    $access_rules = $this->accessRulesManager->getAccessRulesInfo();
    foreach ($access_rules as $access_rule => $info) {
      $form['access'][$access_rule] = [
        '#type' => ($access_rule === 'create') ? 'fieldset' : 'details',
        '#title' => $info['title'],
        '#open' => ($access[$access_rule]['roles'] || $access[$access_rule]['users']) ? TRUE : FALSE,
        '#description' => $info['description'],
        // Never convert description to help.
        // @see _webform_preprocess_description_help()
        '#help' => FALSE,
      ];
      $form['access'][$access_rule]['roles'] = [
        '#type' => 'webform_roles',
        '#title' => $this->t('Roles'),
        '#include_anonymous' => (!in_array($access_rule, ['update_any', 'delete_any', 'purge_any'])) ? TRUE : FALSE,
        '#default_value' => $access[$access_rule]['roles'],
      ];
      $form['access'][$access_rule]['users'] = [
        '#type' => 'webform_users',
        '#title' => $this->t('Users'),
        '#default_value' => $access[$access_rule]['users'] ? $this->entityTypeManager->getStorage('user')->loadMultiple($access[$access_rule]['users']) : [],
      ];
      $form['access'][$access_rule]['permissions'] = [
        '#type' => 'webform_permissions',
        '#title' => $this->t('Permissions'),
        '#multiple' => TRUE,
        '#select2' => TRUE,
        '#default_value' => $access[$access_rule]['permissions'],
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
