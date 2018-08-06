<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a redirect deletion confirmation form.
 */
class RedirectDeleteMultipleForm extends ConfirmFormBase {

  /**
   * The array of redirects to delete.
   *
   * @var string[][]
   */
  protected $redirects = [];

  /**
   * The private tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The redirect storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $redirectStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a RedirectDeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The String translation.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, TranslationInterface $string_translation) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->redirectStorage = $entity_type_manager->getStorage('redirect');
    $this->currentUser = $account;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->redirects), 'Are you sure you want to delete this redirect?', 'Are you sure you want to delete these redirects?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('redirect.list');
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
    $this->redirects = $this->privateTempStoreFactory->get('redirect_multiple_delete_confirm')->get($this->currentUser->id());
    if (empty($this->redirects)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['redirects'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function ($redirect) {
        return $redirect->label();
      }, $this->redirects),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('confirm') && !empty($this->redirects)) {
      $this->redirectStorage->delete($this->redirects);
      $this->privateTempStoreFactory->get('redirect_multiple_delete_confirm')->delete($this->currentUser->id());
      $count = count($this->redirects);
      $this->logger('redirect')->notice('Deleted @count redirects.', ['@count' => $count]);
      drupal_set_message($this->stringTranslation->formatPlural($count, 'Deleted 1 redirect.', 'Deleted @count redirects.'));
    }
    $form_state->setRedirect('redirect.list');
  }

}
