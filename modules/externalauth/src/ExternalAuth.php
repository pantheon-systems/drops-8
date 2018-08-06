<?php

namespace Drupal\externalauth;

use Drupal\Core\Entity\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\externalauth\Event\ExternalAuthLoginEvent;
use Drupal\externalauth\Event\ExternalAuthRegisterEvent;
use Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent;
use Drupal\user\UserInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;

/**
 * Class ExternalAuth.
 *
 * @package Drupal\externalauth
 */
class ExternalAuth implements ExternalAuthInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   *
   * @param EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param AuthmapInterface $authmap
   *   The authmap service.
   * @param LoggerInterface $logger
   *   A logger instance.
   * @param EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityManagerInterface $entity_manager, AuthmapInterface $authmap, LoggerInterface $logger, EventDispatcherInterface $event_dispatcher) {
    $this->entityManager = $entity_manager;
    $this->authmap = $authmap;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function load($authname, $provider) {
    if ($uid = $this->authmap->getUid($authname, $provider)) {
      return $this->entityManager->getStorage('user')->load($uid);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function login($authname, $provider) {
    $account = $this->load($authname, $provider);
    if ($account) {
      return $this->userLoginFinalize($account, $authname, $provider);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function register($authname, $provider, $account_data = array(), $authmap_data = NULL) {
    $username = $provider . '_' . $authname;
    $authmap_event = $this->eventDispatcher->dispatch(ExternalAuthEvents::AUTHMAP_ALTER, new ExternalAuthAuthmapAlterEvent($provider, $authname, $username, $authmap_data));
    $entity_storage = $this->entityManager->getStorage('user');

    $account_search = $entity_storage->loadByProperties(array('name' => $authmap_event->getUsername()));
    if ($account = reset($account_search)) {
      throw new ExternalAuthRegisterException(sprintf('User could not be registered. There is already an account with username "%s"', $authmap_event->getUsername()));
    }

    // Set up the account data to be used for the user entity.
    $account_data = array_merge(
      [
        'name' => $authmap_event->getUsername(),
        'init' => $provider . '_' . $authmap_event->getAuthname(),
        'status' => 1,
        'access' => (int) $_SERVER['REQUEST_TIME'],
      ],
      $account_data
    );
    $account = $entity_storage->create($account_data);

    $account->enforceIsNew();
    $account->save();
    $this->authmap->save($account, $provider, $authmap_event->getAuthname(), $authmap_event->getData());
    $this->eventDispatcher->dispatch(ExternalAuthEvents::REGISTER, new ExternalAuthRegisterEvent($account, $provider, $authmap_event->getAuthname(), $authmap_event->getData()));
    $this->logger->notice('External registration of user %name from provider %provider and authname %authname',
      [
        '%name' => $account->getAccountName(),
        '%provider' => $provider,
        '%authname' => $authname,
      ]
    );

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function loginRegister($authname, $provider, $account_data = array(), $authmap_data = NULL) {
    $account = $this->login($authname, $provider);
    if (!$account) {
      $account = $this->register($authname, $provider, $account_data, $authmap_data);
      return $this->userLoginFinalize($account, $authname, $provider);
    }
    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function userLoginFinalize(UserInterface $account, $authname, $provider) {
    user_login_finalize($account);
    $this->logger->notice('External login of user %name', array('%name' => $account->getAccountName()));
    $this->eventDispatcher->dispatch(ExternalAuthEvents::LOGIN, new ExternalAuthLoginEvent($account, $provider, $authname));
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function linkExistingAccount($authname, $provider, UserInterface $account) {
    // If a mapping (for the same provider) to this account already exists, we
    // silently skip saving this auth mapping.
    if (!$this->authmap->get($account->id(), $provider)) {
      $username = $provider . '_' . $authname;
      $authmap_event = $this->eventDispatcher->dispatch(ExternalAuthEvents::AUTHMAP_ALTER, new ExternalAuthAuthmapAlterEvent($provider, $authname, $username, NULL));
      $this->authmap->save($account, $provider, $authmap_event->getAuthname(), $authmap_event->getData());
    }
  }

}
