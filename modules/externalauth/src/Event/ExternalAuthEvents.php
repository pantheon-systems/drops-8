<?php

namespace Drupal\externalauth\Event;

/**
 * Defines events for the externalauth module.
 *
 * @see \Drupal\externalauth\Event\ExternalAuthRegisterEvent
 * @see \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent
 */
final class ExternalAuthEvents {

  /**
   * Name of the event fired after a Drupal user was logged in.
   *
   * This event allows modules to react on the fact that a user logged in
   * to Drupal, following the authentication with an external service.
   * The event listener method receives a
   * \Drupal\externalauth\Event\ExternalAuthLoginEvent instance.
   *
   * @Event
   *
   * @see \Drupal\externalauth\Event\ExternalAuthLoginEvent
   *
   * @var string
   */
  const LOGIN = 'externalauth.login';

  /**
   * Name of the event fired after a Drupal user was registered.
   *
   * This event allows modules to react on the fact that a new "external" user
   * was registered, following the authentication with an external service.
   * The event listener method receives a
   * \Drupal\externalauth\Event\ExternalAuthRegisterEvent instance.
   *
   * @Event
   *
   * @see \Drupal\externalauth\Event\ExternalAuthRegisterEvent
   *
   * @var string
   */
  const REGISTER = 'externalauth.register';

  /**
   * Name of the event fired to alter the authmap data before it is stored.
   *
   * This event allows modules to alter the data from the external
   * authentication service that will be stored in the Drupal database, to map
   * Drupal users to external identities.
   *
   * The event listener method receives a
   * \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent instance.
   *
   * @Event
   *
   * @see \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent
   *
   * @var string
   */
  const AUTHMAP_ALTER = 'externalauth.authmap_alter';

}
