<?php

namespace Drupal\webform;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Form\WebformDialogFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for webform submission notes.
 */
class WebformSubmissionNotesForm extends ContentEntityForm {

  use WebformDialogFormTrait;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    parent::__construct($entity_manager);
    // @todo Update constructor once Webform is only supporting Drupal 8.3.x.
    $this->requestHandler = \Drupal::service('webform.request');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    list($webform_submission, $source_entity) = $this->requestHandler->getWebformSubmissionEntities();

    $form['navigation'] = [
      '#theme' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
      '#access' => $this->isDialog() ? FALSE : TRUE,
    ];
    $form['information'] = [
      '#theme' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
      '#access' => $this->isDialog() ? FALSE : TRUE,
    ];

    $form['notes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Administrative notes'),
      '#description' => $this->t('Enter notes about this submission. These notes are only visible to submission administrators.'),
      '#default_value' => $webform_submission->getNotes(),
    ];
    $form['sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Star/flag the status of this submission'),
      '#default_value' => $webform_submission->isSticky(),
      '#return_value' => TRUE,
      '#access' => $this->isDialog() ? FALSE : TRUE,
    ];
    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Submitted by'),
      '#description' => $this->t('The username of the user that submitted the webform.'),
      '#target_type' => 'user',
      '#selection_setttings' => [
        'include_anonymous' => FALSE,
      ],
      '#required' => TRUE,
      '#default_value' => $webform_submission->getOwner(),
    ];

    $form['#attached']['library'][] = 'webform/webform.admin';

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Submission @sid notes saved.', ['@sid' => '#' . $this->entity->id()]));
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->entity->toUrl('edit-notes-form');
  }

}
