<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides a render element for message.
 *
 * @FormElement("webform_message")
 */
class WebformMessage extends RenderElement {

  /**
   * Storage none.
   */
  const STORAGE_NONE = '';

  /**
   * Storage local.
   */
  const STORAGE_LOCAL = 'local';

  /**
   * Storage session.
   */
  const STORAGE_SESSION = 'session';

  /**
   * Storage user (data).
   */
  const STORAGE_USER = 'user';

  /**
   * Storage state (API).
   */
  const STORAGE_STATE = 'state';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#message_type' => 'status',
      '#message_message' => '',
      '#message_close' => FALSE,
      '#message_close_effect' => 'slide',
      '#message_id' => '',
      '#message_storage' => '',
      '#status_headings' => [],
      '#pre_render' => [
        [$class, 'preRenderWebformMessage'],
      ],
      '#theme_wrappers' => ['webform_message'],
    ];
  }

  /**
   * Create status message for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with status message.
   */
  public static function preRenderWebformMessage(array $element) {
    $message_type = $element['#message_type'];
    $message_close = $element['#message_close'];
    $message_close_effect = $element['#message_close_effect'];
    $message_id = $element['#message_id'];
    $message_storage = $element['#message_storage'];
    $message_message = $element['#message_message'];

    $element['#attributes']['class'][] = 'webform-message';
    $element['#attributes']['class'][] = 'js-webform-message';

    // Ignore 'user' and 'state' storage is current user is anonymous.
    if (\Drupal::currentUser()->isAnonymous() && in_array($message_storage, [self::STORAGE_USER, self::STORAGE_STATE])
    ) {
      $message_storage = '';
    }

    // Build the messages render array.
    $messages = [];

    // Add close button as the first message.
    if ($message_close) {
      $element['#attributes']['data-message-close-effect'] = $message_close_effect;
      $element['#attributes']['class'][] = 'webform-message--close';
      $element['#attributes']['class'][] = 'js-webform-message--close';

      $close_attributes = [
        'aria-label' => t('close'),
        'class' => ['js-webform-message__link', 'webform-message__link'],
      ];
      if (in_array($message_storage, ['user', 'state'])) {
        $close_url = Url::fromRoute('webform.element.message.close', [
          'storage' => $message_storage,
          'id' => $message_id,
        ]);
      }
      else {
        $close_url = Url::fromRoute('<none>', [], ['fragment' => 'close']);
      }

      $messages[] = [
        '#type' => 'link',
        '#title' => '×',
        '#url' => $close_url,
        '#attributes' => $close_attributes,
      ];

      // Add close attributes and check is message is closed.
      if ($message_storage && $message_id) {
        $element['#attributes']['data-message-id'] = $message_id;
        $element['#attributes']['data-message-storage'] = $message_storage;
        $element['#attributes']['class'][] = 'js-webform-message--close-storage';
        if (self::isClosed($message_storage, $message_id)) {
          $element['#closed'] = TRUE;
        }
      }
    }

    // Add messages to container children.
    $messages[] = (!is_array($message_message)) ? ['#markup' => $message_message] : $message_message;
    foreach (Element::children($element) as $key) {
      $messages[] = $element[$key];
      unset($element[$key]);
    }

    // Add status messages as the message.
    $element['#message'] = [
      '#theme' => 'status_messages',
      '#message_list' => [$message_type => [$messages]],
      '#status_headings' => $element['#status_headings'] + [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];

    $element['#attached']['library'][] = 'webform/webform.element.message';
    return $element;
  }

  /****************************************************************************/
  // Manage closed functions.
  /****************************************************************************/

  /**
   * Is message closed via User Data or State API.
   *
   * @param string $storage
   *   The storage mechanism to check if a message is closed.
   * @param string $id
   *   The ID of the message.
   *
   * @return bool
   *   TRUE if the message is closed.
   */
  public static function isClosed($storage, $id) {
    $account = \Drupal::currentUser();
    $namespace = 'webform.element.message';
    switch ($storage) {
      case self::STORAGE_STATE:
        /** @var \Drupal\Core\State\StateInterface $state */
        $state = \Drupal::service('state');
        $values = $state->get($namespace, []);
        return (isset($values[$id])) ? TRUE : FALSE;

      case self::STORAGE_USER:
        /** @var \Drupal\user\UserDataInterface $user_data */
        $user_data = \Drupal::service('user.data');
        $values = $user_data->get('webform', $account->id(), $namespace) ?: [];
        return (isset($values[$id])) ? TRUE : FALSE;

    }
    return FALSE;
  }

  /**
   * Set message closed via User Data or State API.
   *
   * @param string $storage
   *   The storage mechanism save message closed.
   * @param string $id
   *   The ID of the message.
   *
   * @see \Drupal\webform\Controller\WebformElementController::close
   */
  public static function setClosed($storage, $id) {
    $account = \Drupal::currentUser();
    $namespace = 'webform.element.message';
    switch ($storage) {
      case self::STORAGE_STATE:
        /** @var \Drupal\Core\State\StateInterface $state */
        $state = \Drupal::service('state');
        $values = $state->get($namespace, []);
        $values[$id] = TRUE;
        $state->set($namespace, $values);
        break;

      case self::STORAGE_USER:
        /** @var \Drupal\user\UserDataInterface $user_data */
        $user_data = \Drupal::service('user.data');
        $values = $user_data->get('webform', $account->id(), $namespace) ?: [];
        $values[$id] = TRUE;
        $user_data->set('webform', $account->id(), $namespace, $values);
    }
  }

  /**
   * Reset message closed via User Data or State API.
   *
   * @param string $storage
   *   The storage mechanism save message closed.
   * @param string $id
   *   The ID of the message.
   *
   * @see \Drupal\webform\Controller\WebformElementController::close
   */
  public static function resetClosed($storage, $id) {
    $account = \Drupal::currentUser();
    $namespace = 'webform.element.message';
    switch ($storage) {
      case self::STORAGE_STATE:
        /** @var \Drupal\Core\State\StateInterface $state */
        $state = \Drupal::service('state');
        $values = $state->get($namespace, []);
        unset($values[$id]);
        $state->set($namespace, $values);
        break;

      case self::STORAGE_USER:
        /** @var \Drupal\user\UserDataInterface $user_data */
        $user_data = \Drupal::service('user.data');
        $values = $user_data->get('webform', $account->id(), $namespace) ?: [];
        unset($values[$id]);
        $user_data->set('webform', $account->id(), $namespace, $values);
    }
  }

}
