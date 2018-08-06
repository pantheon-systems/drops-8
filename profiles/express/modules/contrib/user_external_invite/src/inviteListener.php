<?php

namespace Drupal\user_external_invite;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class inviteListener implements EventSubscriberInterface {

  public function onKernelRequest($event) {
    //var_dump($event);die();
    //kint($event);
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * ['eventName' => 'methodName']
   *  * ['eventName' => ['methodName', $priority]]
   *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents() {

    return [KernelEvents::REQUEST => 'onKernelRequest'];
  }


}
