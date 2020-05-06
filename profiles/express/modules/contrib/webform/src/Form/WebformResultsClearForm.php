<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Webform for webform results clear webform.
 */
class WebformResultsClearForm extends WebformSubmissionsDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_results_clear';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $t_args = ['%label' => $this->getLabel()];
    return $this->t('Clear all %label submissions?', $t_args);
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
      $t_args = ['%label' => $this->getLabel()];
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'error',
        '#message_message' => $this->t('There are no %label submissions.', $t_args),
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    $t_args = ['%label' => $this->getLabel()];
    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to clear all %label submissions?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $submission_total = $this->getSubmissionTotal();

    $t_args = [
      '%label' => $this->getLabel(),
      '@total' => $submission_total,
      '@submissions' => $this->formatPlural($submission_total, 'submission', 'submissions'),
    ];

    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove @total %label @submissions', $t_args),
          ['#markup' => '<em>' . $this->t('Take a few minutes to complete') . '</em>'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmInput() {
    $t_args = ['%label' => $this->getLabel()];
    return [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I want to clear all %label submissions', $t_args),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->requestHandler->getUrl($this->webform, $this->sourceEntity, 'webform.results_submissions');
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    $t_args = ['%label' => $this->getLabel()];
    $this->t('Webform %label submissions cleared.', $t_args);
  }

}
