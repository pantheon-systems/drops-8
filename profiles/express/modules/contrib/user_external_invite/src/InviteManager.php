<?php

namespace Drupal\user_external_invite;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

class InviteManager {

  private $dynamic;

  public function __construct(KeyValueFactoryInterface $keyValueFactory, $dynamic) {

    $this->keyValueFactory = $keyValueFactory;
    $this->dynamic = $dynamic;
  }

  public function sendInvite($message) {
    $key = $this->keyValueFactory->get('ding');

    if ($this->getDynamic()) {
      $key->set('joop', $message);
      return;
    }

    $key->set('joop', 'default');
  }

  /**
   * @return mixed
   */
  public function getDynamic() {
    return $this->dynamic;
  }
}
