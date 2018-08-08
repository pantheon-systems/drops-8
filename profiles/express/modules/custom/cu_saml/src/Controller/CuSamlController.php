<?php

/**
 * @file
 * Controller for taking over user logout.
 */

namespace Drupal\cu_saml\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CuSamlController extends ControllerBase {

  /**
   * Destroy user information and redirect to the frontpage.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function logout() {
    // Since the SAML logout URL isn't working, we need to destroy the user's session
    // and not send them to the SAML logout.
    session_destroy();
    setcookie('SimpleSAMLAuthToken', '', time() - 3600);
    setcookie('SimpleSAMLSessionID', '', time() - 3600);
    $url = Url::fromRoute('<front>');
    $response = new RedirectResponse($url->toString());
    return $response->send();
  }
}
