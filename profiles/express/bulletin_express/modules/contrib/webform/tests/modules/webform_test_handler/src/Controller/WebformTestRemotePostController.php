<?php

namespace Drupal\webform_test_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for remote post tests.
 */
class WebformTestRemotePostController extends ControllerBase {

  /**
   * Returns a webform confirmation page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $type
   *   Type of remote post request (insert, update, or delete)
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with request status and message.
   */
  public function index(Request $request, $type) {
    $post_data = $request->request->all();
    if (strpos(print_r($post_data, TRUE), 'FAIL') !== FALSE) {
      $json = [
        'status' => 'fail',
        'message' => (string) $this->t('Failed to process @type request.', ['@type' => $type]),
      ];
      return new JsonResponse($json, 500);
    }
    else {
      $json = [
        'status' => 'success',
        'message' => (string) $this->t('Processed @type request.', ['@type' => $type]),
      ];
      return new JsonResponse($json, 200);
    }
  }

}
