<?php

namespace Drupal\webform\Plugin\WebformSourceEntity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\webform\Plugin\WebformSourceEntityInterface;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Detect source entity by examining query string.
 *
 * @WebformSourceEntity(
 *   id = "query_string",
 *   label = @Translation("Query string"),
 *   weight = 0
 * )
 */
class QueryStringWebformSourceEntity extends PluginBase implements WebformSourceEntityInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * QueryStringWebformSourceEntity constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The "entity_type.manager" service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The "current_route_match" service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The "request_stack" service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The "language_manager" service.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The "webform.entity_reference_manager" service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, RequestStack $request_stack, LanguageManagerInterface $language_manager, WebformEntityReferenceManagerInterface $webform_entity_reference_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->request = $request_stack->getCurrentRequest();
    $this->languageManager = $language_manager;
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;
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
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('webform.entity_reference_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity(array $ignored_types) {
    // Note: We deliberately discard $ignored_types because through query string
    // any arbitrary entity can be injected as a source.
    $webform = $this->routeMatch->getParameter('webform');
    if (!$webform) {
      return NULL;
    }

    // Get and check source entity type.
    $source_entity_type = $this->request->query->get('source_entity_type');
    if (!$source_entity_type || !$this->entityTypeManager->hasDefinition($source_entity_type)) {
      return NULL;
    }

    // Get and check source entity id.
    $source_entity_id = $this->request->query->get('source_entity_id');
    if (!$source_entity_id) {
      return NULL;
    }

    // Get and check source entity.
    $source_entity = $this->entityTypeManager->getStorage($source_entity_type)->load($source_entity_id);
    if (!$source_entity) {
      return NULL;
    }

    // Get translated source entity.
    if ($source_entity instanceof TranslatableInterface && $source_entity->hasTranslation($this->languageManager->getCurrentLanguage()->getId())) {
      $source_entity = $source_entity->getTranslation($this->languageManager->getCurrentLanguage()->getId());
    }

    // Check source entity access.
    if (!$source_entity->access('view')) {
      return NULL;
    }

    // Check that the webform is referenced by the source entity.
    if (!$webform->getSetting('form_prepopulate_source_entity')) {
      // Get source entity's webform field.
      $webform_field_names = $this->webformEntityReferenceManager->getFieldNames($source_entity);
      foreach ($webform_field_names as $webform_field_name) {
        // Check that source entity's reference webform is the
        // current webform.
        foreach ($source_entity->$webform_field_name as $item) {
          if ($item->target_id === $webform->id()) {
            return $source_entity;
          }
        }
      }

      return NULL;
    }

    return $source_entity;
  }

}
