<?php

namespace Drupal\redirect_404\EventSubscriber;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\redirect_404\RedirectNotFoundStorageInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An EventSubscriber that listens to redirect 404 errors.
 */
class Redirect404Subscriber implements EventSubscriberInterface {

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack (get the URL argument(s) and combined it with the path).
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The redirect storage.
   *
   * @var \Drupal\redirect_404\RedirectNotFoundStorageInterface
   */
  protected $redirectStorage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new Redirect404Subscriber.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\redirect_404\RedirectNotFoundStorageInterface $redirect_storage
   *   A redirect storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory.
   */
  public function __construct(CurrentPathStack $current_path, PathMatcherInterface $path_matcher, RequestStack $request_stack, LanguageManagerInterface $language_manager, RedirectNotFoundStorageInterface $redirect_storage, ConfigFactoryInterface $config) {
    $this->currentPath = $current_path;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->languageManager = $language_manager;
    $this->redirectStorage = $redirect_storage;
    $this->config = $config->get('redirect_404.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = 'onKernelException';
    return $events;
  }

  /**
   * Logs an exception of 404 Redirect errors.
   *
   * @param GetResponseForExceptionEvent $event
   *   Is given by the event dispatcher.
   */
  public function onKernelException(GetResponseForExceptionEvent $event) {
    // Only log page not found (404) errors.
    if ($event->getException() instanceof NotFoundHttpException) {
      $path = $this->currentPath->getPath();

      // Ignore paths specified in the redirect settings.
      if ($pages = Unicode::strtolower($this->config->get('pages'))) {
        // Do not trim a trailing slash if that is the complete path.
        $path_to_match = $path === '/' ? $path : rtrim($path, '/');

        if ($this->pathMatcher->matchPath(Unicode::strtolower($path_to_match), $pages)) {
          return;
        }
      }

      // Allow to store paths with arguments.
      if ($query_string = $this->requestStack->getCurrentRequest()->getQueryString()) {
        $query_string = '?' . $query_string;
      }
      $path .= $query_string;
      $langcode = $this->languageManager->getCurrentLanguage()->getId();

      // Write record.
      $this->redirectStorage->logRequest($path, $langcode);
    }
  }

}
