<?php

namespace Drupal\webform_ui\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for webform UI.
 */
class WebformUiPathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (strpos($path, '/webform/') === FALSE  || !method_exists($request, 'getQueryString')) {
      return $path;
    }

    if (strpos($request->getQueryString(), '_wrapper_format=') === FALSE) {
      return $path;
    }

    $querystring = [];
    parse_str($request->getQueryString(), $querystring);
    if (empty($querystring['destination'])) {
      return $path;
    }

    $destination = $querystring['destination'];
    $options['query']['destination'] = $destination;
    return $path;
  }

}
