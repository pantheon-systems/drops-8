<?php

namespace Drupal\simplesamlphp_auth\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;


/**
 * Controller routines for simplesamlphp_auth routes.
 */
class SimplesamlphpAuthController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The SimpleSAML Authentication helper service.
   *
   * @var \Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager
   */
  public $simplesaml;

  /**
   * The SimpleSAML Drupal Authentication service.
   *
   * @var \Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth
   */
  public $simplesamlDrupalauth;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $requestStack;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   * @param SimplesamlphpAuthManager $simplesaml
   *   The SimpleSAML Authentication helper service.
   * @param SimplesamlphpDrupalAuth $simplesaml_drupalauth
   *   The SimpleSAML Drupal Authentication service.
   * @param UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param RequestStack $request_stack
   *   The request stack.
   * @param AccountInterface $account
   *   The current account.
   * @param PathValidatorInterface $path_validator
   *   The path validator.
   * @param LoggerInterface $logger
   *   A logger instance.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(SimplesamlphpAuthManager $simplesaml, SimplesamlphpDrupalAuth $simplesaml_drupalauth, UrlGeneratorInterface $url_generator, RequestStack $request_stack, AccountInterface $account, PathValidatorInterface $path_validator, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->simplesaml = $simplesaml;
    $this->simplesamlDrupalauth = $simplesaml_drupalauth;
    $this->urlGenerator = $url_generator;
    $this->requestStack = $request_stack;
    $this->account = $account;
    $this->pathValidator = $path_validator;
    $this->logger = $logger;
    $this->config = $config_factory->get('simplesamlphp_auth.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simplesamlphp_auth.manager'),
      $container->get('simplesamlphp_auth.drupalauth'),
      $container->get('url_generator'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('path.validator'),
      $container->get('logger.factory')->get('simplesamlphp_auth'),
      $container->get('config.factory')
    );
  }

  /**
   * Logs the user in via SimpleSAML federation.
   *
   * @return RedirectResponse
   *   A redirection to either a designated page or the user login page.
   */
  public function authenticate() {
    global $base_url;

    // Ensure the module has been turned on before continuing with the request.
    if (!$this->simplesaml->isActivated()) {
      return $this->redirect('user.login');
    }

    // Ensure phpsession isn't the session storage location.
    if ($this->simplesaml->getStorage() === 'phpsession') {
      return $this->redirect('user.login');
    }

    // See if a URL has been explicitly provided in ReturnTo. If so, use it
    // otherwise, use the HTTP_REFERER. Each must point to the site to be valid.
    $request = $this->requestStack->getCurrentRequest();

    if (($return_to = $request->request->get('ReturnTo')) || ($return_to = $request->server->get('HTTP_REFERER'))) {
      if ($this->pathValidator->isValid($return_to) && UrlHelper::externalIsLocal($return_to, $base_url)) {
        $redirect = $return_to;
      }
    }

    // The user is not logged into Drupal.
    if ($this->account->isAnonymous()) {

      if (isset($redirect)) {
        // Set the cookie so we can deliver the user to the place they started.
        // @TODO probably a more symfony way of doing this
        setrawcookie('simplesamlphp_auth_returnto', $redirect, time() + 60 * 60);
      }

      // User is logged in to the SimpleSAMLphp IdP, but not to Drupal.
      if ($this->simplesaml->isAuthenticated()) {

        if (!$this->simplesaml->allowUserByAttribute()) {
          return [
            '#markup' => $this->t('You are not allowed to login via this service.'),
          ];
        }

        // Get unique identifier from saml attributes.
        $authname = $this->simplesaml->getAuthname();

        if (!empty($authname)) {
          if ($this->config->get('debug')) {
            $this->logger->debug('Trying to login SAML-authenticated user with authname %authname', [
              '%authname' => $authname,
            ]);
          }
          // User is logged in with SAML authentication and we got the unique
          // identifier, so try to log into Drupal.
          // Check to see whether the external user exists in Drupal. If they
          // do not exist, create them.
          // Also log in the user.
          $this->simplesamlDrupalauth->externalLoginRegister($authname);
        }
      }

      if (\Drupal::config('simplesamlphp_auth.settings')->get('header_no_cache')) {
        header('Cache-Control: no-cache');
      }

      $this->simplesaml->externalAuthenticate();
    }

    // Check to see if we've set a cookie. If there is one, give it priority.
    if ($request->cookies->has('simplesamlphp_auth_returnto')) {
      $redirect = $request->cookies->get('simplesamlphp_auth_returnto');

      // Unset the cookie.
      setrawcookie('simplesamlphp_auth_returnto', '');
    }

    if (isset($redirect)) {
      // Avoid caching of redirect response object.
      \Drupal::service('page_cache_kill_switch')->trigger();
      $response = new RedirectResponse($redirect, RedirectResponse::HTTP_FOUND);
      return $response;
    }

    return $this->redirect('user.login');
  }

}
