<?php

namespace Drupal\metatag\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag\MetatagManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration export form.
 */
class MetatagSettingsForm extends ConfigFormBase {

  /**
   * The metatag.manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * @var \Drupal\metatag\Form\MetatagSettingsForm
     */
    $instance = parent::create($container);
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->metatagManager = $container->get('metatag.manager');
    $instance->state = $container->get('state');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['metatag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->state->get('system.maintenance_mode')) {
      $this->messenger()->addMessage($this->t('Please note that while the site is in maintenance mode none of the usual meta tags will be output.'));
    }
    $settings = $this->config('metatag.settings')->get('entity_type_groups');

    $form['entity_type_groups'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Entity type / Group Mapping'),
      '#description' => $this->t('Identify which metatag groups should be available on which entity type / bundle combination. Unselected groups will not appear on the configuration form for that entity type, reducing the size of the form and increasing performance. If no groups are selected for a type, all groups will appear.'),
      '#tree' => TRUE,
    ];

    $metatag_groups = $this->metatagManager->sortedGroups();
    $entity_types = MetatagDefaultsForm::getSupportedEntityTypes();
    foreach ($entity_types as $entity_type => $entity_label) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach ($bundles as $bundle_id => $bundle_info) {
        // Create an option list for each bundle.
        $options = [];
        foreach ($metatag_groups as $group_name => $group_info) {
          $options[$group_name] = $group_info['label'];
        }
        // Format a collapsible fieldset for each group for easier readability.
        $form['entity_type_groups'][$entity_type][$bundle_id] = [
          '#type' => 'details',
          '#title' => $entity_label . ': ' . $bundle_info['label'],
        ];
        $form['entity_type_groups'][$entity_type][$bundle_id][] = [
          '#type' => 'checkboxes',
          '#options' => $options,
          '#default_value' => isset($settings[$entity_type]) && isset($settings[$entity_type][$bundle_id]) ? $settings[$entity_type][$bundle_id] : [],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('metatag.settings');
    $value = $form_state->getValue('entity_type_groups');
    $value = static::arrayFilterRecursive($value);
    // Remove the extra layer created by collapsible fieldsets.
    foreach ($value as $entity_type => $bundle) {
      foreach ($bundle as $bundle_id => $groups) {
        $value[$entity_type][$bundle_id] = $groups[0];
      }
    }
    $settings->set('entity_type_groups', $value);
    $settings->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Recursively filter results.
   *
   * @param array $input
   *   The array to filter.
   *
   * @return array
   *   The filtered array.
   */
  public static function arrayFilterRecursive(array $input) {
    foreach ($input as &$value) {
      if (is_array($value)) {
        $value = static::arrayFilterRecursive($value);
      }
    }
    return array_filter($input);
  }

}
