<?php

namespace Drupal\pathauto;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface as CoreMessengerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a verbose messenger.
 */
class VerboseMessenger implements MessengerInterface {

  /**
   * The verbose flag.
   *
   * @var bool
   */
  protected $isVerbose;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Creates a verbose messenger.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $account, CoreMessengerInterface $messenger) {
    $this->configFactory = $config_factory;
    $this->account = $account;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $op = NULL) {

    if (!isset($this->isVerbose)) {
      $config = $this->configFactory->get('pathauto.settings');
      $this->isVerbose = $config->get('verbose') && $this->account->hasPermission('notify of path changes');
    }

    if (!$this->isVerbose || (isset($op) && in_array($op, ['bulkupdate', 'return']))) {
      return FALSE;
    }

    if ($message) {
      $this->messenger->addMessage($message);
    }

    return TRUE;
  }

}
