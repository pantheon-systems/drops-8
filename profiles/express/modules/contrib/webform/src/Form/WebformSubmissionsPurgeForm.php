<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Webform for webform submission purge webform.
 */
class WebformSubmissionsPurgeForm extends WebformSubmissionsDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_submissions_purge';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Purge all submissions?');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $submission_total = $this->getSubmissionTotal();
    if ($submission_total) {
      return parent::buildForm($form, $form_state);
    }
    else {
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'error',
        '#message_message' => $this->t('There are no webform submissions.'),
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to purge all submissions?') . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $form_total = $this->entityTypeManager
      ->getStorage('webform')
      ->getQuery()
      ->count()
      ->execute();

    $submission_total = $this->getSubmissionTotal();

    $t_args = [
      '@submission_total' => $submission_total,
      '@submissions' => $this->formatPlural($submission_total, 'submission', 'submissions'),
      '@form_total' => $form_total,
      '@forms' => $this->formatPlural($form_total, 'webform', 'webforms'),
    ];

    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove @submission_total @submissions in @form_total @forms', $t_args),
          ['#markup' => '<em>' . $this->t('Take a few minutes to complete') . '</em>'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmInput() {
    return [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I want to purge all submissions'),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Purge');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.webform_submission.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getFinishedMessage() {
    return $this->t('Webform submissions purged.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmissionTotal() {
    if (!isset($this->submissionTotal)) {
      $this->submissionTotal = $this->entityTypeManager
        ->getStorage('webform_submission')
        ->getQuery()
        ->count()
        ->execute();
    }
    return $this->submissionTotal;
  }

}
