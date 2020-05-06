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
use Drupal\webform\Plugin\WebformSourceEntityManagerInterface;
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
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * Webform source entity plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformSourceEntityManagerInterface
   */
  protected $webformSourceEntityManager;

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
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   * @param \Drupal\webform\Plugin\WebformSourceEntityManagerInterface $webform_source_entity_manager
   *   The webform source entity plugin manager.
   */
  public function __construct(RouteProviderInterface $route_provider, RequestStack $request_stack, AdminContext $admin_context, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, WebformEntityReferenceManagerInterface $webform_entity_reference_manager, WebformSourceEntityManagerInterface $webform_source_entity_manager) {
    $this->routeProvider = $route_provider;
    $this->request = $request_stack->getCurrentRequest();
    $this->adminContext = $admin_context;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;
    $this->webformSourceEntityManager = $webform_source_entity_manager;
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
    // TODO: Can we refactor this method away altogether and let all its callers
    // work directly with webform source entity manager?
    return $this->webformSourceEntityManager->getSourceEntity(is_null($ignored_types) ? [] : $ignored_types);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebform() {
    $webform = $this->routeMatch->getParameter('webform');
    if (is_string($webform)) {
      $webform = $this->entityTypeManager->getStorage('webform')->load($webform);
    }
    if ($webform) {
      return $webform;
    }

    $source_entity = static::getCurrentSourceEntity('webform');
    if ($source_entity && ($source_entity_webform = $this->webformEntityReferenceManager->getWebform($source_entity))) {
      return $source_entity_webform;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebformSubmission() {
    $webform_submission = $this->routeMatch->getParameter('webform_submission');
    if (is_string($webform_submission)) {
      $webform_submission = $this->entityTypeManager->getStorage('webform_submission')->load($webform_submission);
    }
    return $webform_submission;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebformUrl($route_name, array $route_options = []) {
    $webform_entity = $this->getCurrentWebform();
    $source_entity = $this->getCurrentSourceEntity();
    return $this->getUrl($webform_entity, $source_entity, $route_name, $route_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebformSubmissionUrl($route_name, array $route_options = []) {
    $webform_entity = $this->getCurrentWebformSubmission();
    $source_entity = $this->getCurrentSourceEntity();
    return $this->getUrl($webform_entity, $source_entity, $route_name, $route_options);
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
