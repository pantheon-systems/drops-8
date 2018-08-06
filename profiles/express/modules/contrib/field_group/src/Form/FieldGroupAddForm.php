<?php

namespace Drupal\field_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_group\FieldgroupUi;

/**
 * Provides a form for adding a fieldgroup to a bundle.
 */
class FieldGroupAddForm extends FormBase {

  /**
   * The prefix for groups.
   *
   * @var string
   */
  const GROUP_PREFIX = 'group_';

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The context for the group.
   *
   * @var string
   */
  protected $context;

  /**
   * The mode for the group.
   *
   * @var string
   */
  protected $mode;

  /**
   * Current step of the form.
   *
   * @var string
   */
  protected $currentStep;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_group_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $this->mode = \Drupal::request()->get('form_mode_name');
    }
    else {
      $this->mode = \Drupal::request()->get('view_mode_name');
    }

    if (empty($this->mode)) {
      $this->mode = 'default';
    }

    if (!$form_state->get('context')) {
      $form_state->set('context', $context);
    }
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }
    if (!$form_state->get('step')) {
      $form_state->set('step', 'formatter');
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->context = $form_state->get('context');
    $this->currentStep = $form_state->get('step');

    if ($this->currentStep == 'formatter') {
      $this->buildFormatterSelectionForm($form, $form_state);
    }
    else {
      $this->buildConfigurationForm($form, $form_state);
    }

    return $form;

  }

  /**
   * Build the formatter selection step.
   */
  function buildFormatterSelectionForm(array &$form, FormStateInterface $form_state) {

    // Gather group formatters.
    $formatter_options = \field_group_field_formatter_options($form_state->get('context'));
    $form['add'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('form--inline', 'clearfix')),
    );

    $form['add']['group_formatter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add a new group'),
      '#options' => $formatter_options,
      '#empty_option' => $this->t('- Select a group type -'),
      '#required' => TRUE,
    );

    // Field label and field_name.
    $form['new_group_wrapper'] = array(
      '#type' => 'container',
      '#states' => array(
        '!visible' => array(
          ':input[name="group_formatter"]' => array('value' => ''),
        ),
      ),
    );
    $form['new_group_wrapper']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
      '#required' => TRUE,
    );

    $form['new_group_wrapper']['group_name'] = array(
      '#type' => 'machine_name',
      '#size' => 15,
      // This field should stay LTR even for RTL languages.
      '#field_prefix' => '<span dir="ltr">' . self::GROUP_PREFIX,
      '#field_suffix' => '</span>&lrm;',
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen(self::GROUP_PREFIX),
      '#machine_name' => array(
        'source' => array('new_group_wrapper', 'label'),
        'exists' => array($this, 'groupNameExists'),
      ),
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#validate' => array(
        array($this, 'validateFormatterSelection')
      ),
    );

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
  }

  /**
   * Build the formatter configuration form.
   */
  function buildConfigurationForm(array &$form, FormStateInterface $form_state) {

    $group = new \stdClass();
    $group->context = $this->context;
    $group->entity_type = $this->entityTypeId;
    $group->bundle = $this->bundle;
    $group->mode = $this->mode;

    $manager = \Drupal::service('plugin.manager.field_group.formatters');
    $plugin = $manager->getInstance(array(
      'format_type' => $form_state->getValue('group_formatter'),
      'configuration' => [
        'label' => $form_state->getValue('label'),
      ],
      'group' => $group,
    ));

    $form['format_settings'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );

    $form['format_settings'] += $plugin->settingsForm();

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Create group'),
      '#button_type' => 'primary',
    );

  }

  /**
   * Validate the formatter selection step.
   */
  public function validateFormatterSelection(array &$form, FormStateInterface $form_state) {

    $group_name = self::GROUP_PREFIX . $form_state->getValue('group_name');

    // Add the prefix.
    $form_state->setValueForElement($form['new_group_wrapper']['group_name'], $group_name);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->get('step') == 'formatter') {
      $form_state->set('step', 'configuration');
      $form_state->set('group_label', $form_state->getValue('label'));
      $form_state->set('group_name', $form_state->getValue('group_name'));
      $form_state->set('group_formatter', $form_state->getValue('group_formatter'));
      $form_state->setRebuild();
    }
    else {

      $new_group = (object) array(
        'group_name' => $form_state->get('group_name'),
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'mode' => $this->mode,
        'context' => $this->context,
        'children' =>[],
        'parent_name' => '',
        'weight' => 20,
        'format_type' => $form_state->get('group_formatter'),
      );

      $new_group->format_settings = $form_state->getValue('format_settings');
      $new_group->label = $new_group->format_settings['label'];
      unset($new_group->format_settings['label']);
      $new_group->format_settings += _field_group_get_default_formatter_settings($form_state->get('group_formatter'), $this->context);

      field_group_group_save($new_group);

      // Store new group information for any additional submit handlers.
      $groups_added = $form_state->get('groups_added');
      $groups_added['_add_new_group'] = $new_group->group_name;
      drupal_set_message(t('New group %label successfully created.', array('%label' => $new_group->label)));

      $form_state->setRedirectUrl(FieldgroupUi::getFieldUiRoute($new_group));
      \Drupal::cache()->invalidate('field_groups');

    }

  }

  /**
   * Checks if a group machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'group_name' element.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the group machine name is taken.
   */
  public function groupNameExists($value, $element, FormStateInterface $form_state) {

    // Add the prefix.
    $group_name = self::GROUP_PREFIX . $value;
    $entity_type = $form_state->get('entity_type_id');
    $bundle = $form_state->get('bundle');
    $context = $form_state->get('context');
    $mode = $form_state->get('mode');

    return field_group_exists($group_name, $entity_type, $bundle, $context, $mode);
  }

}
