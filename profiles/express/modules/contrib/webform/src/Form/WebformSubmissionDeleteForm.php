<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation webform for deleting a webform submission.
 */
class WebformSubmissionDeleteForm extends ContentEntityDeleteForm implements WebformDeleteFormInterface {

  use WebformDialogFormTrait;

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
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, WebformRequestInterface $request_handler) {
    parent::__construct($entity_repository);
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    list($this->webformSubmission, $this->sourceEntity) = $this->requestHandler->getWebformSubmissionEntities();
    $this->webform = $this->webformSubmission->getWebform();

    $form['warning'] = $this->getWarning();
    $form = parent::buildForm($form, $form_state);
    $form['description'] = $this->getDescription();

    return $this->buildDialogConfirmForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Issue #2582295: Confirmation cancel links are incorrect if installed in
    // a subdirectory
    // Work-around: Remove subdirectory from destination before generating
    // actions.
    $request = $this->getRequest();
    $destination = $request->query->get('destination');
    if ($destination) {
      // Remove subdirectory from destination.
      $update_destination = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $destination);
      $request->query->set('destination', $update_destination);
      $actions = parent::actions($form, $form_state);
      $request->query->set('destination', $destination);
      return $actions;
    }
    else {
      return parent::actions($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $t_args = [
      '%label' => $this->getEntity()->label(),
    ];
    return $this->t('Delete %label?', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    $t_args = [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ];

    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to delete the %label @entity-type?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove records from the database'),
          $this->t('Delete any uploaded files'),
          $this->t('Cancel all pending actions'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmInput() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('%label has been deleted.', ['%label' => $this->webformSubmission->label()]);
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
