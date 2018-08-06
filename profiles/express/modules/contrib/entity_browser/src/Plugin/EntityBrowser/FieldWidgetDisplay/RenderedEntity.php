<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\FieldWidgetDisplay;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_browser\FieldWidgetDisplayBase;

/**
 * Displays the fully rendered entity.
 *
 * @EntityBrowserFieldWidgetDisplay(
 *   id = "rendered_entity",
 *   label = @Translation("Rendered entity"),
 *   description = @Translation("Displays fully rendered entity.")
 * )
 */
class RenderedEntity extends FieldWidgetDisplayBase implements ContainerFactoryPluginInterface {

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

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
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   Entity display repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity) {
    return $this->entityTypeManager->getViewBuilder($this->configuration['entity_type'])->view($entity, $this->configuration['view_mode']);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->entityDisplayRepository->getViewModeOptions($this->configuration['entity_type']) as $id => $view_mode_label) {
      $options[$id] = $view_mode_label;
    }

    return [
      'view_mode' => [
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#description' => $this->t('Select view mode to be used when rendering entities.'),
        '#default_value' => $this->configuration['view_mode'],
        '#options' => $options,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => 'default',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($view_mode = $this->entityTypeManager->getStorage('entity_view_mode')->load($this->configuration['entity_type'] . '.' . $this->configuration['view_mode'])) {
      $dependencies[$view_mode->getConfigDependencyKey()][] = $view_mode->getConfigDependencyName();
    }
    return $dependencies;
  }

}
