<?php

namespace Drupal\redirect\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\redirect\Exception\RedirectLoopException;
use Drupal\redirect\RedirectChecker;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Redirect subscriber for controller requests.
 */
class RedirectRequestSubscriber implements EventSubscriberInterface {

  /** @var  \Drupal\redirect\RedirectRepository */
  protected $redirectRepository;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\redirect\RedirectChecker
   */
  protected $checker;

  /**
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * A path processor manager for resolving the system path.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\redirect\RedirectRepository $redirect_repository
   *   The redirect entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.
   * @param \Drupal\Core\Path\AliasManager $alias_manager
   *   The alias manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\redirect\RedirectChecker $checker
   *   The redirect checker service.
   * @param \Symfony\Component\Routing\RequestContext
   *   Request context.
   */
  public function __construct(RedirectRepository $redirect_repository, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config, AliasManager $alias_manager, ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager, RedirectChecker $checker, RequestContext $context, InboundPathProcessorInterface $path_processor) {
    $this->redirectRepository = $redirect_repository;
    $this->languageManager = $language_manager;
    $this->config = $config->get('redirect.settings');
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->checker = $checker;
    $this->context = $context;
    $this->pathProcessor = $path_processor;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(GetResponseEvent $event) {
    // Get a clone of the request. During inbound processing the request
    // can be altered. Allowing this here can lead to unexpected behavior.
    // For example the path_processor.files inbound processor provided by
    // the system module alters both the path and the request; only the
    // changes to the request will be propagated, while the change to the
    // path will be lost.
    $request = clone $event->getRequest();

    if (!$this->checker->canRedirect($request)) {
      return;
    }

    // Get URL info and process it to be used for hash generation.
    parse_str($request->getQueryString(), $request_query);

    // Do the inbound processing so that for example language prefixes are
    // removed.
    $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    $path = ltrim($path, '/');

    $this->context->fromRequest($request);

    try {
      $redirect = $this->redirectRepository->findMatchingRedirect($path, $request_query, $this->languageManager->getCurrentLanguage()->getId());
    }
    catch (RedirectLoopException $e) {
      \Drupal::logger('redirect')->warning($e->getMessage());
      $response = new Response();
      $response->setStatusCode(503);
      $response->setContent('Service unavailable');
      $event->setResponse($response);
      return;
    }

    if (!empty($redirect)) {

      // Handle internal path.
      $url = $redirect->getRedirectUrl();
      if ($this->config->get('passthrough_querystring')) {
        $url->setOption('query', (array) $url->getOption('query') + $request_query);
      }
      $headers = [
        'X-Redirect-ID' => $redirect->id(),
      ];
      $response = new TrustedRedirectResponse($url->setAbsolute()->toString(), $redirect->getStatusCode(), $headers);
      $response->addCacheableDependency($redirect);
      $event->setResponse($response);
    }
  }

  /**
   * Prior to set the response it check if we can redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   * @param \Drupal\Core\Url $url
   *   The Url where we want to redirect.
   */
  protected function setResponse(GetResponseEvent $event, Url $url) {
    $request = $event->getRequest();
    $this->context->fromRequest($request);

    parse_str($request->getQueryString(), $query);
    $url->setOption('query', $query);
    $url->setAbsolute(TRUE);

    // We can only check access for routed URLs.
    if (!$url->isRouted() || $this->checker->canRedirect($request, $url->getRouteName())) {
      // Add the 'rendered' cache tag, so that we can invalidate all responses
      // when settings are changed.
      $response = new TrustedRedirectResponse($url->toString(), 301);
      $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([])->addCacheTags(['rendered']));
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestCheckRedirect', 33);
    return $events;
  }

}
