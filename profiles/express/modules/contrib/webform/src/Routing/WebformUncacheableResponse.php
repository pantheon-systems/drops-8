<?php

namespace Drupal\webform\Routing;

use Symfony\Component\HttpFoundation\Response;

/**
 * Provides an uncacheable response.
 */
class WebformUncacheableResponse extends Response {

  /**
   * {@inheritdoc}
   */
  public function __construct($url, $status = 302, $headers = []) {
    parent::__construct($url, $status, $headers);
    $this->setPrivate();
    $this->setMaxAge(0);
    $this->setSharedMaxAge(0);
    $this->headers->addCacheControlDirective('must-revalidate', TRUE);
    $this->headers->addCacheControlDirective('no-store', TRUE);
  }

}
