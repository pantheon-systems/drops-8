<?php

namespace Drupal\webform\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response subscriber to redirect to user login when access is denied to private webform file uploads.
 *
 * @see webform_file_download()
 * @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::accessFileDownload
 */
class WebformSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new WebformSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(AccountInterface $account, ConfigFactoryInterface $config_factory) {
    $this->account = $account;
    $this->configFactory = $config_factory;
  }

  /**
   * Redirect to user login when access is denied to private webform file uploads.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespondRedirectPrivateFileAccess(FilterResponseEvent $event) {
    $path = $event->getRequest()->getPathInfo();
    // Make sure the user is trying to access a private webform file upload.
    if (strpos($path, '/system/files/webform/') !== 0) {
      return;
    }

    // Make private webform file upload is not a temporary file.
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::postSave
    if (strpos($path, '/_sid_/') !== FALSE) {
      return;
    }

    // Only redirect anonymous users.
    if ($this->account->isAuthenticated()) {
      return;
    }

    // Check that private file redirection is enabled.
    if ($this->configFactory->get('webform.settings')->get('file.file_private_redirect') === FALSE) {
      return;
    }

    if ($event->getResponse()->getStatusCode() === Response::HTTP_FORBIDDEN) {
      // Display message on user login.
      drupal_set_message($this->t('Please login to access the uploaded file.'));

      // Redirect to user login with destination set to the private file.
      $redirect_url = Url::fromRoute(
        'user.login',
        [],
        ['absolute' => TRUE, 'query' => ['destination' => ltrim($path, '/')]]
      );
      $event->setResponse(new RedirectResponse($redirect_url->toString()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['onRespondRedirectPrivateFileAccess'];
    return $events;
  }

}
