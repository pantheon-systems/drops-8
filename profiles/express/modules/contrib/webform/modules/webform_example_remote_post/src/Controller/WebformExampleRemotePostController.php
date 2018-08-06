<?php

namespace Drupal\webform_example_remote_post\Controller;

use Drupal\Component\Utility\Random;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for example remote post.
 */
class WebformExampleRemotePostController extends ControllerBase {

  /**
   * Returns a remote post response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $type
   *   Type of remote post request (completed, updated, or deleted)
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with request status and message.
   */
  public function index(Request $request, $type) {
    $random = new Random();
    $json = [
      'status' => 'success',
      'message' => (string) $this->t('Processed @type request.', ['@type' => $type]),
      'confirmation_number' => $random->name(20, TRUE),
    ];
    return new JsonResponse($json, 200);
  }

}
