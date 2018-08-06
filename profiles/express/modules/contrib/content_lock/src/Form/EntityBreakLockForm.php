<?php

namespace Drupal\content_lock\Form;

use Drupal\content_lock\ContentLock\ContentLock;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a base class for break content lock forms.
 */
class EntityBreakLockForm extends FormBase {

  /**
   * Content lock service.
   *
   * @var \Drupal\content_lock\ContentLock\ContentLock
   */
  protected $lockService;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * EntityBreakLockForm constructor.
   *
   * @param \Drupal\content_lock\ContentLock\ContentLock $contentLock
   *   Content lock service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack service.
   */
  public function __construct(ContentLock $contentLock, RequestStack $requestStack) {
    $this->lockService = $contentLock;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_lock'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type_id');
    $entity_id = $form_state->getValue('entity_id');

    $this->lockService->release($entity_id, NULL, $entity_type);
    drupal_set_message($this->t('Lock broken. Anyone can now edit this content.'));

    // Redirect URL to the request destination or the canonical entity view.
    if ($destination = $this->request->query->get('destination')) {
      $url = Url::fromUserInput($destination);
      $form_state->setRedirectUrl($url);
    }
    else {
      $this->redirect("entity.$entity_type.canonical", [$entity_type => $entity_id])->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'break_lock_entity';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $entity = NULL) {
    $form['#title'] = $this->t('Break Lock for content @label', ['@label' => $entity->label()]);
    $form['entity_id'] = [
      '#type' => 'value',
      '#value' => $entity->id(),
    ];
    $form['entity_type_id'] = [
      '#type' => 'value',
      '#value' => $entity->getEntityTypeId(),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm break lock'),
    ];
    return $form;
  }

  /**
   * Custom access checker for the form route requirements.
   */
  public function access(ContentEntityInterface $entity, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('break content lock') || $this->lockService->isLockedBy($entity->id(), $account->id(), $entity->getEntityTypeId()));
  }

}
