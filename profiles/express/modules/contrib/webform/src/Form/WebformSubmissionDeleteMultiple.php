<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a webform submission deletion confirmation form.
 */
class WebformSubmissionDeleteMultiple extends ConfirmFormBase {

  /**
   * The array of webform_submissions to delete.
   *
   * @var string[][]
   */
  protected $webformSubmissionInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a WebformSubmissionDeleteMultiple object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('webform_submission');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_submission_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->webformSubmissionInfo), 'Are you sure you want to delete this submission?', 'Are you sure you want to delete these submissions?');
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
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface[] $webform_submissions */
    $webform_submissions = $this->tempStoreFactory->get('webform_submission_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($webform_submissions)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['webform_submissions'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function ($webform_submission) {
        return $webform_submission->label();
      }, $webform_submissions),
    ];
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface[] $webform_submissions */
    $webform_submissions = $this->tempStoreFactory->get('webform_submission_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if ($form_state->getValue('confirm') && !empty($webform_submissions)) {
      $this->storage->delete($webform_submissions);
      $this->logger('content')->notice('Deleted @count submission.', ['@count' => count($webform_submissions)]);
      $this->tempStoreFactory->get('webform_submission_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.webform_submission.collection');
  }

}
