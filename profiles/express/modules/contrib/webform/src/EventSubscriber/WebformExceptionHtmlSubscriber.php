<?php

namespace Drupal\webform\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Event subscriber to redirect to login form when webform settings instruct to.
 */
class WebformExceptionHtmlSubscriber extends DefaultExceptionHtmlSubscriber {

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new WebformExceptionHtmlSubscriber.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $access_unaware_router
   *   A router implementation which does not check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination, UrlMatcherInterface $access_unaware_router, AccountInterface $account, ConfigFactoryInterface $config_factory, RendererInterface $renderer, MessengerInterface $messenger, WebformTokenManagerInterface $token_manager) {
    parent::__construct($http_kernel, $logger, $redirect_destination, $access_unaware_router);

    $this->account = $account;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // Execute before CustomPageExceptionHtmlSubscriber which is -50.
    // @see \Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber::getPriority
    return -49;
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $this->on403RedirectEntityAccess($event);
    $this->on403RedirectPrivateFileAccess($event);
  }

  /**
   * Redirect to user login when access is denied to private webform file.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   *
   * @see webform_file_download()
   * @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::accessFileDownload
   */
  public function on403RedirectPrivateFileAccess(GetResponseForExceptionEvent $event) {
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

    // Check that private file redirection is enabled.
    if (!$this->configFactory->get('webform.settings')->get('file.file_private_redirect')) {
      return;
    }

    $message = $this->configFactory->get('webform.settings')->get('file.file_private_redirect_message');
    $this->redirectToLogin($event, $message);
  }

  /**
   * Redirect to user login when access is denied for webform or submission.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403RedirectEntityAccess(GetResponseForExceptionEvent $event) {
    $url = Url::fromUserInput($event->getRequest()->getPathInfo());
    if (!$url) {
      return;
    }

    $route_parameters = $url->isRouted() ? $url->getRouteParameters() : [];
    if (empty($route_parameters['webform']) && empty($route_parameters['webform_submission'])) {
      return;
    }

    $config = $this->configFactory->get('webform.settings');

    // If webform submission, handle login redirect.
    if (!empty($route_parameters['webform_submission'])) {
      $webform_submission = WebformSubmission::load($route_parameters['webform_submission']);
      $webform = $webform_submission->getWebform();

      $submission_access_denied_message = $webform->getSetting('submission_access_denied_message')
        ?: $config->get('settings.default_submission_access_denied_message');

      switch ($webform->getSetting('submission_access_denied')) {
        case WebformInterface::ACCESS_DENIED_LOGIN:
          $this->redirectToLogin($event, $submission_access_denied_message, $webform_submission);
          break;

        case WebformInterface::ACCESS_DENIED_PAGE:
          // Must manually build access denied path so that base path is not
          // included.
          $this->makeSubrequest($event, '/admin/structure/webform/manage/' . $webform->id() . '/submission/' . $webform_submission->id() . '/access-denied', Response::HTTP_FORBIDDEN);
          break;

        case WebformInterface::ACCESS_DENIED_DEFAULT:
        default:
          // Make the default 403 request so that we can add cacheable dependencies.
          $this->makeSubrequest($event, $this->getSystemSite403Path(), Response::HTTP_FORBIDDEN);
          break;
      }

      // Add cacheable dependencies.
      $response = $event->getResponse();
      if ($response instanceof CacheableResponseInterface) {
        $response->addCacheableDependency($webform);
        $response->addCacheableDependency($webform_submission);
        $response->addCacheableDependency($config);
      }
      return;
    }

    // If webform, handle access denied redirect or page.
    if (!empty($route_parameters['webform'])) {
      $webform = Webform::load($route_parameters['webform']);

      $webform_access_denied_message = $webform->getSetting('form_access_denied_message')
        ?: $config->get('settings.default_form_access_denied_message');

      switch ($webform->getSetting('form_access_denied')) {
        case WebformInterface::ACCESS_DENIED_LOGIN:
          $this->redirectToLogin($event, $webform_access_denied_message, $webform);
          break;

        case WebformInterface::ACCESS_DENIED_PAGE:
          // Must manually build access denied path so that base path is not
          // included.
          $this->makeSubrequest($event, '/webform/' . $webform->id() . '/access-denied', Response::HTTP_FORBIDDEN);
          break;

        case WebformInterface::ACCESS_DENIED_MESSAGE:
          // Display message.
          $this->setMessage($webform_access_denied_message, $webform);
          // Make the default 403 request so that we can add cacheable dependencies.
          $this->makeSubrequest($event, $this->getSystemSite403Path(), Response::HTTP_FORBIDDEN);
          break;

        case WebformInterface::ACCESS_DENIED_DEFAULT:
        default:
          // Make the default 403 request so that we can add cacheable dependencies.
          $this->makeSubrequest($event, $this->getSystemSite403Path(), Response::HTTP_FORBIDDEN);
          break;
      }
      // Add cacheable dependencies.
      $response = $event->getResponse();
      if ($response instanceof CacheableResponseInterface) {
        $response->addCacheableDependency($webform);
        $response->addCacheableDependency($config);
      }
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * {@inheritdoc}
   */
  public function onException(GetResponseForExceptionEvent $event) {
    // Only handle 403 exception.
    // @see \Drupal\webform\EventSubscriber\WebformExceptionHtmlSubscriber::on403
    $exception = $event->getException();
    if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403) {
      parent::onException($event);
    }
  }

  /**
   * Get 403 path from system.site config.
   *
   * @return string
   *   The custom 403 path or Drupal's default 403 path.
   */
  protected function getSystemSite403Path() {
    return $this->configFactory->get('system.site')->get('page.403') ?: '/system/403';
  }

  /**
   * Redirect to user login with destination and display custom message.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   * @param null|string $message
   *   (Optional) Message to be display on user login.
   * @param null|\Drupal\Core\Entity\EntityInterface $entity
   *   (Optional) Entity to be used when replacing tokens.
   */
  protected function redirectToLogin(GetResponseForExceptionEvent $event, $message = NULL, EntityInterface $entity = NULL) {
    // Display message.
    if ($message) {
      $this->setMessage($message, $entity);
    }

    // Only redirect anonymous users.
    if ($this->account->isAuthenticated()) {
      return;
    }

    $redirect_url = Url::fromRoute(
      'user.login',
      [],
      ['absolute' => TRUE, 'query' => $this->redirectDestination->getAsArray()]
    );
    $event->setResponse(new RedirectResponse($redirect_url->toString()));
  }

  /**
   * Display custom message.
   *
   * @param null|string $message
   *   (Optional) Message to be display on user login.
   * @param null|\Drupal\Core\Entity\EntityInterface $entity
   *   (Optional) Entity to be used when replacing tokens.
   */
  protected function setMessage($message, EntityInterface $entity = NULL) {
    $message = $this->tokenManager->replace($message, $entity);
    $build = WebformHtmlEditor::checkMarkup($message);
    $this->messenger->addStatus($this->renderer->renderPlain($build));
  }

}
