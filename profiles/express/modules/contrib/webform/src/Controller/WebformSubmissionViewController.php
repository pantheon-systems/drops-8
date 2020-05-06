<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render a single webform submission.
 */
class WebformSubmissionViewController extends EntityViewController {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Creates an WebformSubmissionViewController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   */
  public function __construct(EntityManagerInterface $entity_manager, RendererInterface $renderer, AccountInterface $current_user, WebformRequestInterface $webform_request) {
    parent::__construct($entity_manager, $renderer);
    $this->currentUser = $current_user;
    $this->requestHandler = $webform_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $webform_submission, $view_mode = 'default', $langcode = NULL) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity('webform_submission');

    // Set webform submission template.
    $build = [
      '#theme' => 'webform_submission',
      '#view_mode' => $view_mode,
      '#webform_submission' => $webform_submission,
    ];

    // Navigation.
    $build['navigation'] = [
      '#type' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
    ];

    // Information.
    $build['information'] = [
      '#type' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
    ];

    // Submission.
    $build['submission'] = parent::view($webform_submission, $view_mode, $langcode);

    // Library.
    $build['#attached']['library'][] = 'webform/webform.admin';

    // Add entities cacheable dependency.
    $this->renderer->addCacheableDependency($build, $this->currentUser);
    $this->renderer->addCacheableDependency($build, $webform);
    $this->renderer->addCacheableDependency($build, $webform_submission);
    if ($source_entity) {
      $this->renderer->addCacheableDependency($build, $source_entity);
    }

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single webform submission.
   *
   * @param \Drupal\Core\Entity\EntityInterface $webform_submission
   *   The current webform submission.
   * @param bool $duplicate
   *   Flag indicating if submission is being duplicated.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $webform_submission, $duplicate = FALSE) {
    $title = $this->entityManager->getTranslationFromContext($webform_submission)->label();
    return ($duplicate) ? $this->t('Duplicate @title', ['@title' => $title]) : $title;
  }

}
