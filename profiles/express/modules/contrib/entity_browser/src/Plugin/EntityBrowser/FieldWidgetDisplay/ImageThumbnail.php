<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\FieldWidgetDisplay;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_browser\FieldWidgetDisplayBase;

/**
 * Displays image thumbnail.
 *
 * @EntityBrowserFieldWidgetDisplay(
 *   id = "thumbnail",
 *   label = @Translation("Image thumbnail"),
 *   description = @Translation("Displays image files as thumbnails")
 * )
 */
class ImageThumbnail extends FieldWidgetDisplayBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity) {
    return [
      '#theme' => 'image_style',
      '#style_name' => $this->configuration['image_style'],
      '#title' => $entity->label(),
      '#alt' => $entity->label(),
      '#uri' => $entity->getFileUri(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->entityTypeManager->getStorage('image_style')->loadMultiple() as $id => $image_style) {
      $options[$id] = $image_style->label();
    }

    return [
      'image_style' => [
        '#type' => 'select',
        '#title' => $this->t('Image style'),
        '#description' => $this->t('Select image style to be used to display thumbnails.'),
        '#default_value' => $this->configuration['image_style'],
        '#options' => $options,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->isSubclassOf(FileInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image_style' => 'thumbnail',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($image_style = $this->entityTypeManager->getStorage('image_style')->load($this->configuration['image_style'])) {
      $dependencies[$image_style->getConfigDependencyKey()][] = $image_style->getConfigDependencyName();
    }
    return $dependencies;
  }

}
