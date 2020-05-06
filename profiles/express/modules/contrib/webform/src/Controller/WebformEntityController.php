<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Element\Webform as WebformElement;
use Drupal\webform\Routing\WebformUncacheableResponse;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheableResponse;

/**
 * Provides route responses for Webform entity.
 */
class WebformEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * Constructs a WebformEntityController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(RendererInterface $renderer, WebformRequestInterface $request_handler, WebformTokenManagerInterface $token_manager) {
    $this->renderer = $renderer;
    $this->requestHandler = $request_handler;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('renderer'),
      $container->get('webform.request'),
      $container->get('webform.token_manager')
    );
    $instance->webformEntityReferenceManager = $container->get('webform.entity_reference_manager');
    return $instance;
  }

  /**
   * Returns a webform to add a new submission to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform this submission will be added to.
   *
   * @return array
   *   The webform submission webform.
   */
  public function addForm(Request $request, WebformInterface $webform) {
    return $webform->getSubmissionForm();
  }

  /**
   * Returns a webform's CSS.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return \Symfony\Component\HttpFoundation\Response|\Drupal\Core\Cache\CacheableResponse
   *   The response object.
   */
  public function css(Request $request, WebformInterface $webform) {
    $assets = $webform->getAssets();
    if ($webform->access('update')) {
      return new WebformUncacheableResponse($assets['css'], 200, ['Content-Type' => 'text/css']);
    }
    else {
      return new CacheableResponse($assets['css'], 200, ['Content-Type' => 'text/css']);
    }
  }

  /**
   * Returns a webform's JavaScript.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return \Symfony\Component\HttpFoundation\Response|\Drupal\Core\Cache\CacheableResponse
   *   The response object.
   */
  public function javascript(Request $request, WebformInterface $webform) {
    $assets = $webform->getAssets();
    if ($webform->access('update')) {
      return new WebformUncacheableResponse($assets['javascript'], 200, ['Content-Type' => 'text/javascript']);
    }
    else {
      return new CacheableResponse($assets['javascript'], 200, ['Content-Type' => 'text/javascript']);
    }
  }

  /**
   * Returns a webform confirmation page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   A webform submission.
   *
   * @return array
   *   A render array representing a webform confirmation page
   */
  public function confirmation(Request $request, WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    if (!$webform) {
      list($webform, $source_entity) = $this->requestHandler->getWebformEntities();
    }
    else {
      $source_entity = $this->requestHandler->getCurrentSourceEntity('webform');
    }

    if ($token = $request->get('token')) {
      /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
      $webform_submission_storage = $this->entityTypeManager()->getStorage('webform_submission');
      if ($entities = $webform_submission_storage->loadByProperties(['token' => $token])) {
        $webform_submission = reset($entities);
      }
    }

    // Alter webform settings before setting the entity.
    if ($webform_submission) {
      $webform_submission->getWebform()->invokeHandlers('overrideSettings', $webform_submission);
    }

    // Apply variants.
    if ($webform->hasVariants()) {
      if ($webform_submission) {
        $webform->applyVariants($webform_submission);
      }
      else {
        $variants = $this->getVariants($request, $webform, $source_entity);
        $webform->applyVariants(NULL, $variants);
      }
    }

    // Get title.
    $title = $webform->getSetting('confirmation_title') ?: (($source_entity) ? $source_entity->label() : $webform->label());

    // Replace tokens in title.
    $title = $this->tokenManager->replace($title, $webform_submission ?: $webform);

    $build = [
      '#title' => $title,
      '#theme' => 'webform_confirmation',
      '#webform' => $webform,
      '#source_entity' => $source_entity,
      '#webform_submission' => $webform_submission,
    ];

    // Add entities cacheable dependency.
    $this->renderer->addCacheableDependency($build, $webform);
    if ($webform_submission) {
      $this->renderer->addCacheableDependency($build, $webform_submission);
    }
    if ($source_entity) {
      $this->renderer->addCacheableDependency($build, $source_entity);
    }

    return $build;
  }

  /**
   * Get variants from the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The current webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   The current source entity.
   *
   * @return array
   *   An associative array of variants keyed by element key
   *   and variant instance id.
   *
   * @see \Drupal\webform\Entity\Webform::getSubmissionForm
   */
  protected function getVariants(Request $request, WebformInterface $webform, EntityInterface $source_entity = NULL) {
    // Get variants from '_webform_variant query string parameter.
    $webform_variant = $request->query->get('_webform_variant');
    if ($webform_variant && ($webform->access('update') || $webform->access('test'))) {
      return $webform_variant;
    }

    // Get default data.
    $field_name = $this->webformEntityReferenceManager->getFieldName($source_entity);
    if (!$field_name) {
      return [];
    }

    $default_data = $source_entity->$field_name->default_data;
    $default_data = ($default_data) ? Yaml::decode($default_data) : [];

    // Get query string data.
    $query = $request->query->all();

    // Get variants from #prepopulate query string parameters.
    $variants = [];
    $element_keys = $webform->getElementsVariant();
    foreach ($element_keys as $element_key) {
      $element = $webform->getElement($element_key);
      if (!empty($query[$element_key]) && !empty($element['#prepopulate'])) {
        $variants[$element_key] = $query[$element_key];
      }
      elseif (!empty($default_data[$element_key])) {
        $variants[$element_key] = $default_data[$element_key];
      }
    }

    return $variants;
  }

  /**
   * Returns a webform filter webform autocomplete matches.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param bool $templates
   *   If TRUE, limit autocomplete matches to webform templates.
   * @param bool $archived
   *   If TRUE, limit autocomplete matches to archived webforms and templates.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function autocomplete(Request $request, $templates = FALSE, $archived = FALSE) {
    $q = $request->query->get('q');

    $webform_storage = $this->entityTypeManager()->getStorage('webform');

    $query = $webform_storage->getQuery()
      ->range(0, 10)
      ->sort('title');
    // Query title and id.
    $or = $query->orConditionGroup()
      ->condition('id', $q, 'CONTAINS')
      ->condition('title', $q, 'CONTAINS');
    $query->condition($or);

    // Limit query to templates.
    if ($templates) {
      $query->condition('template', TRUE);
    }
    elseif ($this->moduleHandler()->moduleExists('webform_templates')) {
      // Filter out templates if the webform_template.module is enabled.
      $query->condition('template', FALSE);
    }

    // Limit query to archived.
    $query->condition('archive', $archived);

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      return new JsonResponse([]);
    }
    $webforms = $webform_storage->loadMultiple($entity_ids);

    $matches = [];
    foreach ($webforms as $webform) {
      if ($webform->access('view')) {
        $value = new FormattableMarkup('@label (@id)', ['@label' => $webform->label(), '@id' => $webform->id()]);
        $matches[] = ['value' => $value, 'label' => $value];
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Returns a webform's access denied page.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return array
   *   A renderable array containing an access denied page.
   */
  public function accessDenied(WebformInterface $webform) {
    return WebformElement::buildAccessDenied($webform);
  }

  /**
   * Returns a webform's access denied title.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The webform submissions's access denied title.
   */
  public function accessDeniedTitle(WebformInterface $webform) {
    return $webform->getSetting('form_access_denied_title') ?: $this->t('Access denied');
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   *
   * @return string
   *   The webform label as a render array.
   */
  public function title(WebformInterface $webform = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    if (!$webform) {
      list($webform, $source_entity) = $this->requestHandler->getWebformEntities();
    }
    else {
      $source_entity = $this->requestHandler->getCurrentSourceEntity('webform');
    }

    // If source entity does not exist or does not have a label always use
    // the webform's label.
    if (!$source_entity || !method_exists($source_entity, 'label')) {
      return $webform->label();
    }

    // Handler duplicate titles.
    if ($source_entity->label() === $webform->label()) {
      return $webform->label();
    }

    // Get the webform's title.
    switch ($webform->getSetting('form_title')) {
      case WebformInterface::TITLE_SOURCE_ENTITY:
        return $source_entity->label();

      case WebformInterface::TITLE_WEBFORM:
        return $webform->label();

      case WebformInterface::TITLE_WEBFORM_SOURCE_ENTITY:
        $t_args = [
          '@source_entity' => $source_entity->label(),
          '@webform' => $webform->label(),
        ];
        return $this->t('@webform: @source_entity', $t_args);

      case WebformInterface::TITLE_SOURCE_ENTITY_WEBFORM:
      default:
        $t_args = [
          '@source_entity' => $source_entity->label(),
          '@webform' => $webform->label(),
        ];
        return $this->t('@source_entity: @webform', $t_args);

    }
  }

}
