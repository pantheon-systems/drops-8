<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles webform requests.
 */
class WebformRequest implements WebformRequestInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The current route match.
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
   * Constructs a WebformRequest object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   */
  public function __construct(RouteProviderInterface $route_provider, RequestStack $request_stack, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository) {
    $this->routeProvider = $route_provider;
    $this->request = $request_stack->getCurrentRequest();
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSourceEntity($ignored_types = NULL) {
    // See if source entity is being set via query string parameters.
    if ($source_entity = $this->getCurrentSourceEntityFromQuery()) {
      return $source_entity;
    }

    // Get the most specific source entity available in the current route's
    // parameters.
    $parameters = $this->routeMatch->getParameters()->all();
    $parameters = array_reverse($parameters);

    if ($ignored_types) {
      if (is_array($ignored_types)) {
        $parameters = array_diff_key($parameters, array_flip($ignored_types));
      }
      else {
        unset($parameters[$ignored_types]);
      }
    }

    foreach ($parameters as $name => $value) {
      if ($value instanceof EntityInterface) {
        return $value;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebform() {
    $source_entity = self::getCurrentSourceEntity('webform');
    $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($source_entity);
    if ($source_entity && $webform_field_name && $source_entity->hasField($webform_field_name)) {
      return $source_entity->$webform_field_name->entity;
    }
    else {
      return $this->routeMatch->getParameter('webform');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformEntities() {
    $webform = $this->getCurrentWebform();
    $source_entity = $this->getCurrentSourceEntity('webform');
    return [$webform, $source_entity];
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformSubmissionEntities() {
    $webform_submission = $this->routeMatch->getParameter('webform_submission');
    $source_entity = $this->getCurrentSourceEntity('webform_submission');
    return [$webform_submission, $source_entity];
  }

  /****************************************************************************/
  // Routing helpers
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isAjax() {
    return $this->request->get(AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $webform_entity, EntityInterface $source_entity = NULL, $route_name, $route_options = []) {
    $route_name = $this->getRouteName($webform_entity, $source_entity, $route_name);
    $route_parameters = $this->getRouteParameters($webform_entity, $source_entity);
    return Url::fromRoute($route_name, $route_parameters, $route_options);

  }
  /**
   * {@inheritdoc}
   */
  public function getRouteName(EntityInterface $webform_entity, EntityInterface $source_entity = NULL, $route_name) {
    if (!$this->hasSourceEntityWebformRoutes($source_entity)) {
      $source_entity = NULL;
    }

    return $this->getBaseRouteName($webform_entity, $source_entity) . '.' . $route_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    if (!$this->hasSourceEntityWebformRoutes($source_entity)) {
      $source_entity = NULL;
    }

    if (self::isValidSourceEntity($webform_entity, $source_entity)) {
      if ($webform_entity instanceof WebformSubmissionInterface) {
        return [
          'webform_submission' => $webform_entity->id(),
          $source_entity->getEntityTypeId() => $source_entity->id(),
        ];
      }
      else {
        return [$source_entity->getEntityTypeId() => $source_entity->id()];
      }
    }
    elseif ($webform_entity instanceof WebformSubmissionInterface) {
      return [
        'webform_submission' => $webform_entity->id(),
        'webform' => $webform_entity->getWebform()->id(),
      ];
    }
    else {
      return [$webform_entity->getEntityTypeId() => $webform_entity->id()];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteName(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    if ($webform_entity instanceof WebformSubmissionInterface) {
      $webform = $webform_entity->getWebform();
    }
    elseif ($webform_entity instanceof WebformInterface) {
      $webform = $webform_entity;
    }
    else {
      throw new \InvalidArgumentException('Webform entity');
    }

    if (self::isValidSourceEntity($webform, $source_entity)) {
      return 'entity.' . $source_entity->getEntityTypeId();
    }
    else {
      return 'entity';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSourceEntityWebformRoutes(EntityInterface $source_entity = NULL) {
    if ($source_entity && $this->routeExists('entity.' . $source_entity->getEntityTypeId() . '.webform_submission.canonical')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isValidSourceEntity(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    // Validate that source entity exists and can be linked to.
    if (!$source_entity || !$source_entity->hasLinkTemplate('canonical')) {
      return FALSE;
    }

    // Get the webform.
    if ($webform_entity instanceof WebformSubmissionInterface) {
      $webform = $webform_entity->getWebform();
    }
    elseif ($webform_entity instanceof WebformInterface) {
      $webform = $webform_entity;
    }
    else {
      throw new \InvalidArgumentException('Webform entity');
    }

    // Validate that source entity's field target id is the correct webform.
    $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($source_entity);
    if ($webform_field_name
      && $source_entity->hasField($webform_field_name)
      && $source_entity->$webform_field_name->target_id == $webform->id()
    ) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get webform submission source entity from query string.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   A source entity.
   */
  protected function getCurrentSourceEntityFromQuery() {
    // Get and check for webform.
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

    // Check source entity access.
    if (!$source_entity->access('view')) {
      return NULL;
    }

    // Check that the webform is referenced by the source entity.
    if (!$webform->getSetting('form_prepopulate_source_entity')) {
      // Get source entity's webform field.
      $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($source_entity);
      if (!$webform_field_name) {
        return NULL;
      }

      // Check that source entity's reference webform is the current YAML
      // webform.
      if ($source_entity->$webform_field_name->target_id != $webform->id()) {
        return NULL;
      }
    }

    return $source_entity;
  }

  /**
   * Check if route exists.
   *
   * @param string $name
   *   Route name.
   *
   * @return bool
   *   TRUE if the route exists.
   *
   * @see http://drupal.stackexchange.com/questions/222591/how-do-i-verify-a-route-exists
   */
  protected function routeExists($name) {
    try {
      $this->routeProvider->getRouteByName($name);
      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

}
