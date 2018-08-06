<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
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
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * The webform entity reference manager
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * Track if the current page is a webform admin route.
   *
   * @var bool
   */
  protected $isAdminRoute;

  /**
   * Constructs a WebformRequest object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   */
  public function __construct(RouteProviderInterface $route_provider, RequestStack $request_stack, AdminContext $admin_context, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, WebformEntityReferenceManagerInterface $webform_entity_reference_manager) {
    $this->routeProvider = $route_provider;
    $this->request = $request_stack->getCurrentRequest();
    $this->adminContext = $admin_context;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isWebformAdminRoute() {
    if (isset($this->isAdminRoute)) {
      return $this->isAdminRoute;
    }

    // Make sure the current route is an admin route.
    if (!$this->adminContext->isAdminRoute()) {
      $this->isAdminRoute = FALSE;
      return $this->isAdminRoute;
    }

    $route_name = $this->routeMatch->getRouteName();
    if (in_array($route_name, ['entity.webform.canonical', 'entity.webform_submission.edit_form'])) {
      $this->isAdminRoute = FALSE;
    }
    else {
      $this->isAdminRoute = (preg_match('/^(webform\.|^entity\.([^.]+\.)?webform)/', $route_name)) ? TRUE : FALSE;
    }
    return $this->isAdminRoute;
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

    foreach ($parameters as $value) {
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
    $source_entity = static::getCurrentSourceEntity('webform');
    if ($source_entity && ($webform = $this->webformEntityReferenceManager->getWebform($source_entity))) {
      return $webform;
    }

    $webform = $this->routeMatch->getParameter('webform');
    if (is_string($webform)) {
      $webform = $this->entityTypeManager->getStorage('webform')->load($webform);
    }
    return $webform;
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
    if (is_string($webform_submission)) {
      $webform_submission = $this->entityTypeManager->getStorage('webform_submission')->load($webform_submission);
    }
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
  public function getUrl(EntityInterface $webform_entity, EntityInterface $source_entity = NULL, $route_name, array $route_options = []) {
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

    if (static::isValidSourceEntity($webform_entity, $source_entity)) {
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

    if (static::isValidSourceEntity($webform, $source_entity)) {
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
    $webform_target = $this->webformEntityReferenceManager->getWebform($source_entity);
    if ($webform_target && $webform_target->id() == $webform->id()) {
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
      $webform_field_name = $this->webformEntityReferenceManager->getFieldName($source_entity);
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
