<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation webform for deleting a webform submission.
 */
class WebformSubmissionDeleteForm extends ContentEntityDeleteForm {

  /**
   * The webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;


  /**
   * The webform submission entity.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * The webform source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformSubmissionDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(EntityManagerInterface $entity_manager, WebformRequestInterface $request_handler) {
    parent::__construct($entity_manager);
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    list($this->webformSubmission, $this->sourceEntity) = $this->requestHandler->getWebformSubmissionEntities();
    $this->webform = $this->webformSubmission->getWebform();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete @title?', ['@title' => $this->webformSubmission->label()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('@title has been deleted.', ['@title' => $this->webformSubmission->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $base_route_name = (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.user.submission.delete') !== FALSE) ? 'webform.user.submissions' : 'webform.results_submissions';
    return $this->requestHandler->getUrl($this->webform, $this->sourceEntity, $base_route_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    // Deletion logging is handled via WebformSubmissionStorage.
    // @see \Drupal\webform\WebformSubmissionStorage::delete
  }

}
