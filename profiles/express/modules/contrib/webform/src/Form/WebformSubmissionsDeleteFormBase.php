<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base webform for deleting webform submission.
 */
abstract class WebformSubmissionsDeleteFormBase extends ConfirmFormBase {

  /**
   * Default number of submission to be deleted during batch processing.
   *
   * @var int
   */
  protected $batchLimit = 1000;

  /**
   * The webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformResultsDeleteFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler) {
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clear');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    list($this->webform, $this->sourceEntity) = $this->requestHandler->getWebformEntities();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getCancelUrl());
    if ($this->submissionStorage->getTotal($this->webform, $this->sourceEntity) < $this->getBatchLimit()) {
      $this->submissionStorage->deleteAll($this->webform, $this->sourceEntity);
      drupal_set_message($this->getFinishedMessage());
    }
    else {
      $this->batchSet($this->webform, $this->sourceEntity);
    }
  }

  /**
   * Message to displayed after submissions are deleted.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Message to be displayed after delete has finished.
   */
  public function getFinishedMessage() {
    return $this->t('Webform submissions cleared.');
  }

  /**
   * Batch API; Initialize batch operations.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The webform's source entity.
   */
  public function batchSet(WebformInterface $webform = NULL, EntityInterface $entity = NULL) {
    $parameters = [
      $webform,
      $entity,
      $this->submissionStorage->getMaxSubmissionId($webform, $entity),
    ];
    $batch = [
      'title' => $this->t('Clear submissions'),
      'init_message' => $this->t('Clearing submission data'),
      'error_message' => $this->t('The submissions could not be cleared because an error occurred.'),
      'operations' => [
        [[$this, 'batchProcess'], $parameters],
      ],
      'finished' => [$this, 'batchFinish'],
    ];

    batch_set($batch);
  }

  /**
   * Get the number of submissions to be deleted with each batch.
   *
   * @return int
   *   Number of submissions to be deleted with each batch.
   */
  public function getBatchLimit() {
    return $this->config('webform.settings')->get('batch.default_batch_delete_size') ?: $this->batchLimit;
  }

  /**
   * Batch API callback; Delete submissions.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The webform's source entity.
   * @param int $max_sid
   *   The max submission ID to be delete.
   * @param mixed|array $context
   *   The batch current context.
   */
  public function batchProcess(WebformInterface $webform = NULL, EntityInterface $entity = NULL, $max_sid, &$context) {
    // ISSUE:
    // $this->submissionStorage is not being setup via
    // WebformSubmissionsDeleteFormBase::__construct.
    //
    // WORKAROUND:
    // Reset it for each batch process.
    $this->submissionStorage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $this->submissionStorage->getTotal($webform, $entity);
      $context['results']['webform'] = $webform;
      $context['results']['entity'] = $entity;
    }

    // Track progress.
    $context['sandbox']['progress'] += $this->submissionStorage->deleteAll($webform, $entity, $this->getBatchLimit(), $max_sid);

    $context['message'] = $this->t('Deleting @count of @total submissions...', ['@count' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    // Track finished.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch API callback; Completed deletion.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of function calls (not used in this function).
   */
  public function batchFinish($success = FALSE, array $results, array $operations) {
    if (!$success) {
      drupal_set_message($this->t('Finished with an error.'));
    }
    else {
      drupal_set_message($this->getFinishedMessage());
    }
  }

}
