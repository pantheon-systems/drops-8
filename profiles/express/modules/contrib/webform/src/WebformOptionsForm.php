<?php

namespace Drupal\webform;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to set options.
 */
class WebformOptionsForm extends EntityForm {

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleExtensionList = $container->get('extension.list.module');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation == 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }

    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->getEntity();

    // Customize title for duplicate and edit operation.
    switch ($this->operation) {
      case 'duplicate':
        $form['#title'] = $this->t("Duplicate '@label' options", ['@label' => $webform_options->label()]);
        break;

      case 'edit':
      case 'source':
        $form['#title'] = $webform_options->label();
        break;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->entity;

    /** @var \Drupal\webform\WebformOptionsStorageInterface $webform_options_storage */
    $webform_options_storage = $this->entityTypeManager->getStorage('webform_options');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#attributes' => ($webform_options->isNew()) ? ['autofocus' => 'autofocus'] : [],
      '#default_value' => $webform_options->label(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\webform\Entity\WebformOptions::load',
        'label' => '<br/>' . $this->t('Machine name'),
      ],
      '#maxlength' => 32,
      '#field_suffix' => ($webform_options->isNew()) ? ' (' . $this->t('Maximum @max characters', ['@max' => 32]) . ')' : '',
      '#required' => TRUE,
      '#disabled' => !$webform_options->isNew(),
      '#default_value' => $webform_options->id(),
    ];
    $form['category'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Category'),
      '#options' => $webform_options_storage->getCategories(),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $webform_options->get('category'),
    ];
    $form['likert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use as likert'),
      '#description' => $this->t("If checked, options will be available as answers to Likert elements. The 'Likert:' prefix will be removed from the option's label when listed as answers for a Likert elment."),
      '#default_value' => $webform_options->get('likert'),
      '#return_value' => TRUE,
    ];

    // Call the isolated edit webform that can be overridden by the
    // webform_ui.module.
    $module_names = $this->alterModuleNames();
    if (count($module_names) && !$form_state->getUserInput()) {
      $t_args = [
        '%title' => $webform_options->label(),
        '%module_names' => WebformArrayHelper::toString($module_names),
        '@module' => new PluralTranslatableMarkup(count($module_names), $this->t('module'), $this->t('modules')),
      ];
      if (empty($webform_options->get('options'))) {
        $this->messenger()->addWarning($this->t('The %title options are being set by the %module_names @module. Altering any of the below options will override these dynamically populated options.', $t_args));
      }
      else {
        $this->messenger()->addWarning($this->t('The %title options have been customized. Resetting the below options will allow the %module_names @module to dynamically populate these options.', $t_args));
      }
    }

    $form = $this->editForm($form, $form_state);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->entity;

    // Add reset button if options are altered.
    $module_names = $this->alterModuleNames();
    if (count($module_names) && !empty($webform_options->get('options'))) {
      $actions['#submit']['#weight'] = -100;
      $actions['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::submitForm', '::reset'],
      ];
    }

    // Open delete button in a modal dialog.
    if (isset($actions['delete'])) {
      $actions['delete']['#attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, $actions['delete']['#attributes']['class']);
      WebformDialogHelper::attachLibraries($actions['delete']);
    }

    return $actions;
  }

  /**
   * Get webform options alter module names.
   *
   * @return array
   *   An array of module names that implement
   *   hook_webform_options_WEBFORM_OPTIONS_ID_alter().
   */
  protected function alterModuleNames() {
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->entity;

    if ($webform_options->isNew()) {
      return [];
    }

    $hook_name = 'webform_options_' . $webform_options->id() . '_alter';
    $alter_hooks = $this->moduleHandler->getImplementations($hook_name);
    $module_info = $this->moduleExtensionList->getAllInstalledInfo();
    $module_names = [];
    foreach ($alter_hooks as $options_alter_hook) {
      $module_name = str_replace($hook_name, '', $options_alter_hook);
      $module_names[] = $module_info[$module_name]['name'];
    }
    return $module_names;
  }

  /**
   * Edit webform options source code form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  protected function editForm(array $form, FormStateInterface $form_state) {
    $form['options'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Options (YAML)'),
      '#description' => $this->t('Key-value pairs MUST be specified as "safe_key: \'Some readable option\'". Use of only alphanumeric characters and underscores is recommended in keys. One option per line. Option groups can be created by using just the group name followed by indented group options.') . ' ' .
        $this->t("Descriptions, which are only applicable to radios and checkboxes, can be delimited using ' -- '."),
      '#attributes' => ['style' => 'min-height: 200px'],
      '#default_value' => Yaml::encode($this->getOptions()),
    ];
    return $form;
  }

  /**
   * Get options.
   *
   * @return array
   *   An associative array of options.
   */
  protected function getOptions() {
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->getEntity();

    $options = $webform_options->getOptions();
    if (empty($options)) {
      $element = ['#options' => $webform_options->id()];
      $options = WebformOptions::getElementOptions($element);
    }

    return WebformOptionsHelper::convertOptionsToString($options);
  }

  /**
   * Reset options.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->getEntity();
    $webform_options->set('options', '');
    $webform_options->save();

    $context = [
      '@label' => $webform_options->label(),
      'link' => $webform_options->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    $this->logger('webform')->notice('Options @label have been reset.', $context);

    $this->messenger()->addStatus($this->t('Options %label have been reset.', ['%label' => $webform_options->label()]));

    $form_state->setRedirect('entity.webform_options.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->getEntity();
    $webform_options->save();

    $context = [
      '@label' => $webform_options->label(),
      'link' => $webform_options->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    $this->logger('webform')->notice('Options @label saved.', $context);

    $this->messenger()->addStatus($this->t('Options %label saved.', ['%label' => $webform_options->label()]));

    $form_state->setRedirect('entity.webform_options.collection');
  }

}
