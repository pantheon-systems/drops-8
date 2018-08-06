<?php

namespace Drupal\pathauto;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Creates a verbose messenger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $account) {
    $this->configFactory = $config_factory;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $op = NULL) {

    if (!isset($this->isVerbose)) {
      $config = $this->configFactory->get('pathauto.settings');
      $this->isVerbose = $config->get('verbose') && $this->account->hasPermission('notify of path changes');
    }

    if (!$this->isVerbose || (isset($op) && in_array($op, array('bulkupdate', 'return')))) {
      return FALSE;
    }

    if ($message) {
      drupal_set_message($message);
    }

    return TRUE;
  }

}
