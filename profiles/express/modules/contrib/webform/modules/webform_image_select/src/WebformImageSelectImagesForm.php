<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Utility\WebformArrayHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to set webform image select images.
 */
class WebformImageSelectImagesForm extends EntityForm {

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
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images */
    $webform_images = $this->getEntity();

    // Customize title for duplicate and edit operation.
    switch ($this->operation) {
      case 'duplicate':
        $form['#title'] = $this->t("Duplicate '@label' images", ['@label' => $webform_images->label()]);
        break;

      case 'edit':
      case 'source':
        $form['#title'] = $webform_images->label();
        break;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images */
    $webform_images = $this->entity;

    /** @var \Drupal\webform_image_select\WebformImageSelectImagesStorageInterface $webform_image_select_images_storage */
    $webform_image_select_images_storage = $this->entityTypeManager->getStorage('webform_image_select_images');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#attributes' => ($webform_images->isNew()) ? ['autofocus' => 'autofocus'] : [],
      '#default_value' => $webform_images->label(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\webform_image_select\Entity\WebformImageSelectImages::load',
        'label' => '<br/>' . $this->t('Machine name'),
      ],
      '#maxlength' => 32,
      '#field_suffix' => ($webform_images->isNew()) ? ' (' . $this->t('Maximum @max characters', ['@max' => 32]) . ')' : '',
      '#required' => TRUE,
      '#disabled' => !$webform_images->isNew(),
      '#default_value' => $webform_images->id(),
    ];
    $form['category'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Category'),
      '#options' => $webform_image_select_images_storage->getCategories(),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $webform_images->get('category'),
    ];

    if ($this->getRouteMatch()->getRouteName() === 'entity.webform_image_select_images.source_form') {
      $form['images'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('Images (YAML)'),
        '#attributes' => ['style' => 'min-height: 200px'],
        '#default_value' => $this->getImages(),
      ];
    }
    else {
      $form['images'] = [
        '#type' => 'webform_image_select_images',
        '#title' => $this->t('Images'),
        '#title_display' => 'invisible',
        '#empty_options' => 10,
        '#add_more_items' => 10,
        '#default_value' => $this->getImages(),
      ];
    }

    // Display message if images are altered.
    if (!$webform_images->isNew()) {
      $hook_name = 'webform_image_select_images_' . $webform_images->id() . '_alter';
      $alter_hooks = $this->moduleHandler->getImplementations($hook_name);
      $module_info = $this->moduleExtensionList->getAllInstalledInfo();
      $module_names = [];
      foreach ($alter_hooks as $options_alter_hook) {
        $module_name = str_replace($hook_name, '', $options_alter_hook);
        $module_names[] = $module_info[$module_name]['name'];
      }
      if (count($module_names) && !$form_state->getUserInput()) {
        $t_args = [
          '%title' => $webform_images->label(),
          '%module_names' => WebformArrayHelper::toString($module_names),
          '@module' => new PluralTranslatableMarkup(count($module_names), $this->t('module'), $this->t('modules')),
        ];
        $this->messenger()->addWarning($this->t('The %title images are being altered by the %module_names @module.', $t_args));
      }
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Open delete button in a modal dialog.
    if (isset($actions['delete'])) {
      $actions['delete']['#attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, $actions['delete']['#attributes']['class']);
      WebformDialogHelper::attachLibraries($actions['delete']);
    }

    return $actions;
  }

  /**
   * Get options.
   *
   * @return array
   *   An associative array of options.
   */
  protected function getImages() {
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $images */
    $images = $this->getEntity();
    $options = $images->getImages();
    return WebformOptionsHelper::convertOptionsToString($options);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $images */
    $images = $this->getEntity();
    $images->save();

    $context = [
      '@label' => $images->label(),
      'link' => $images->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    $this->logger('webform_image_select')->notice('Images @label saved.', $context);

    $this->messenger()->addStatus($this->t('Images %label saved.', [
      '%label' => $images->label(),
    ]));

    $form_state->setRedirect('entity.webform_image_select_images.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Overriding after \Drupal\Core\Entity\EntityForm::afterBuild because
    // it calls ::buildEntity(), which calls ::copyFormValuesToEntity, which
    // attempts to populate the entity even though the 'images' have not been
    // validated and set.
    // @see \Drupal\Core\Entity\EntityForm::afterBuild
    // @eee \Drupal\webform_image_select\WebformImageSelectImagesForm::copyFormValuesToEntity
    // @see \Drupal\webform_image_select\Element\WebformImageSelect
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $entity */
    $values = $form_state->getValues();
    if (is_array($values['images'])) {
      $entity->setImages($values['images']);
      unset($values['images']);
    }

    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
  }

}
