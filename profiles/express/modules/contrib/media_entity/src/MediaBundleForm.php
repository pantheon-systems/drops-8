<?php

namespace Drupal\media_entity;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for node type forms.
 */
class MediaBundleForm extends EntityForm {

  /**
   * The instantiated plugin instances that have configuration forms.
   *
   * @var \Drupal\Core\Plugin\PluginFormInterface[]
   */
  protected $configurableInstances = [];

  /**
   * Manager for media entity type plugins.
   *
   * @var \Drupal\media_entity\MediaTypeManager
   */
  protected $mediaTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\media_entity\MediaTypeManager $media_type_manager
   *   Media type manager.
   */
  public function __construct(MediaTypeManager $media_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->mediaTypeManager = $media_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.media_entity.type'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Ajax callback triggered by the type provider select element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function ajaxTypeProviderData(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $plugin = $this->entity->getType()->getPluginId();

    $response->addCommand(new ReplaceCommand('#edit-type-configuration-plugin-wrapper', $form['type_configuration'][$plugin]));
    $response->addCommand(new ReplaceCommand('#field-mapping-wrapper', $form['field_mapping']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $form['#entity'] = $bundle = $this->entity;
    $form_state->set('bundle', $bundle->id());

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add media bundle');
    }
    elseif ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label media bundle', ['%label' => $bundle->label()]);
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $bundle->label(),
      '#description' => $this->t('The human-readable name of this media bundle.'),
      '#required' => TRUE,
      '#size' => 30,
      '#weight' => -100,
    ];

    // @todo: '#disabled' not always FALSE.
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bundle->id(),
      '#maxlength' => 32,
      '#disabled' => !$bundle->isNew(),
      '#machine_name' => [
        'exists' => ['\Drupal\media_entity\Entity\MediaBundle', 'exists'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this media bundle.'),
      '#weight' => -90,
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $bundle->getDescription(),
      '#description' => $this->t('Describe this media bundle. The text will be displayed on the <em>Add new media</em> page.'),
      '#weight' => -80,
    ];

    $plugins = $this->mediaTypeManager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin => $definition) {
      $options[$plugin] = $definition['label'];
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type provider'),
      '#default_value' => $bundle->getType()->getPluginId(),
      '#options' => $options,
      '#description' => $this->t('Media type provider plugin that is responsible for additional logic related to this media.'),
      '#weight' => -70,
      '#ajax' => [
        'callback' => '::ajaxTypeProviderData',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating type provider configuration form.'),
        ],
      ],
    ];

    // Media type plugin configuration.
    $form['type_configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Type provider configuration'),
      '#tree' => TRUE,
      '#weight' => -60,
    ];

    /** @var \Drupal\media_entity\MediaTypeInterface $plugin */
    if ($plugin = $bundle->getType()) {
      $plugin_configuration = (empty($this->configurableInstances[$plugin->getPluginId()]['plugin_config'])) ? $bundle->type_configuration : $this->configurableInstances[$plugin->getPluginId()]['plugin_config'];
      /** @var \Drupal\media_entity\MediaTypeBase $instance */
      $instance = $this->mediaTypeManager->createInstance($plugin->getPluginId(), $plugin_configuration);
      // Store the configuration for validate and submit handlers.
      $this->configurableInstances[$plugin->getPluginId()]['plugin_config'] = $plugin_configuration;

      $form['type_configuration'][$plugin->getPluginId()] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'edit-type-configuration-plugin-wrapper',
        ],
      ];
      $form['type_configuration'][$plugin->getPluginId()] += $instance->buildConfigurationForm([], $form_state);
    }

    // Field mapping configuration.
    $form['field_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field mapping'),
      '#tree' => TRUE,
      '#attributes' => ['id' => 'field-mapping-wrapper'],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Media type plugins can provide metadata fields such as title, caption, size information, credits, ... Media entity can automatically save this metadata information to entity fields, which can be configured below. Information will only be mapped if the entity field is empty.'),
      ],
      '#weight' => -50,
    ];

    if (empty($plugin) || empty($plugin->providedFields())) {
      $form['field_mapping']['empty_message'] = [
        '#prefix' => '<em>',
        '#suffix' => '</em>',
        '#markup' => $this->t('No metadata fields available.'),
      ];
    }
    else {
      $skipped_fields = [
        'mid',
        'uuid',
        'vid',
        'bundle',
        'langcode',
        'default_langcode',
        'uid',
        'revision_timestamp',
        'revision_log',
        'revision_uid',
      ];
      $options = ['_none' => $this->t('- Skip field -')];
      foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
        if (!in_array($field_name, $skipped_fields)) {
          $options[$field_name] = $field->getLabel();
        }
      }

      foreach ($plugin->providedFields() as $field_name => $field_label) {
        $form['field_mapping'][$field_name] = [
          '#type' => 'select',
          '#title' => $field_label,
          '#options' => $options,
          '#default_value' => isset($bundle->field_map[$field_name]) ? $bundle->field_map[$field_name] : '_none',
        ];
      }
    }

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['media_entity/media_bundle_form'],
      ],
      '#weight' => 100,
    ];

    $form['workflow'] = [
      '#type' => 'details',
      '#title' => $this->t('Publishing options'),
      '#group' => 'additional_settings',
    ];

    $workflow_options = [
      'status' => $bundle->getStatus(),
      'new_revision' => $bundle->shouldCreateNewRevision(),
      'queue_thumbnail_downloads' => $bundle->getQueueThumbnailDownloads(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => [
        'status' => $this->t('Published'),
        'new_revision' => $this->t('Create new revision'),
        'queue_thumbnail_downloads' => $this->t('Queue thumbnail downloads'),
      ],
    ];

    $form['workflow']['options']['status']['#description'] = $this->t('Entities will be automatically published when they are created.');
    $form['workflow']['options']['new_revision']['#description'] = $this->t('Automatically create a new revision of media entities. Users with the Administer media permission will be able to override this option.');
    $form['workflow']['options']['queue_thumbnail_downloads']['#description'] = $this->t('Download thumbnails via a queue.');

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('media', $bundle->id());

      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'media',
          'bundle' => $bundle->id(),
        ],
        '#default_value' => $language_configuration,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Let the selected plugin validate its settings.
    $plugin = $this->entity->getType()->getPluginId();
    $plugin_configuration = !empty($this->configurableInstances[$plugin]['plugin_config']) ? $this->configurableInstances[$plugin]['plugin_config'] : [];
    $instance = $this->mediaTypeManager->createInstance($plugin, $plugin_configuration);
    $instance->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $workflow_options = ['status', 'queue_thumbnail_downloads'];
    foreach ($workflow_options as $option) {
      $this->entity->$option = (bool) $form_state->getValue(['options', $option]);
    }

    $this->entity->setNewRevision((bool) $form_state->getValue(['options', 'new_revision']));

    // Let the selected plugin save its settings.
    $plugin = $this->entity->getType()->getPluginId();
    $plugin_configuration = !empty($this->configurableInstances[$plugin]['plugin_config']) ? $this->configurableInstances[$plugin]['plugin_config'] : [];
    $instance = $this->mediaTypeManager->createInstance($plugin, $plugin_configuration);
    $instance->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save media bundle');
    $actions['delete']['#value'] = $this->t('Delete media bundle');
    $actions['delete']['#access'] = $this->entity->access('delete');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $configuration = $form_state->getValue('type_configuration');

    // Store previous plugin config.
    $plugin = $entity->getType()->getPluginId();
    $this->configurableInstances[$plugin]['plugin_config'] = empty($configuration[$plugin]) ? [] : $configuration[$plugin];

    /** @var \Drupal\media_entity\MediaBundleInterface $entity */
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    // Use type configuration for the plugin that was chosen.
    $plugin = $entity->getType()->getPluginId();
    $plugin_configuration = empty($configuration[$plugin]) ? [] : $configuration[$plugin];
    $entity->set('type_configuration', $plugin_configuration);

    // Save field mapping.
    $entity->field_map = array_filter(
      $form_state->getValue('field_mapping', []),
      function ($item) { return $item != '_none'; }
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $this->entity;
    $status = $bundle->save();

    $t_args = ['%name' => $bundle->label()];
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The media bundle %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The media bundle %name has been added.', $t_args));
      $this->logger('media')->notice('Added bundle %name.', $t_args);
    }

    // Override the "status" base field default value, for this bundle.
    $fields = $this->entityFieldManager->getFieldDefinitions('media', $bundle->id());
    $media = $this->entityTypeManager->getStorage('media')->create(array('bundle' => $bundle->id()));
    $value = (bool) $form_state->getValue(['options', 'status']);
    if ($media->status->value != $value) {
      $fields['status']->getConfig($bundle->id())->setDefaultValue($value)->save();
    }

    $form_state->setRedirectUrl($bundle->toUrl('collection'));
  }

}
