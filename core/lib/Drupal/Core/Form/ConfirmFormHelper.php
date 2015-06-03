<?php

/**
 * @file
 * Contains \Drupal\Core\Form\ConfirmFormHelper.
 */

namespace Drupal\Core\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides common functionality to confirmation forms.
 */
class ConfirmFormHelper {

  /**
   * Builds the cancel link for a confirmation form.
   *
   * @param \Drupal\Core\Form\ConfirmFormInterface $form
   *   The confirmation form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The link render array for the cancel form.
   */
  public static function buildCancelLink(ConfirmFormInterface $form, Request $request) {
    // Prepare cancel link.
    $query = $request->query;
    // If a destination is specified, that serves as the cancel link.
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      // @todo Revisit this in https://www.drupal.org/node/2418219.
      $url = Url::fromUserInput('/' . $options['path'], $options);
    }
    // Check for a route-based cancel link.
    else {
      $url = $form->getCancelUrl();
    }

    return [
      '#type' => 'link',
      '#title' => $form->getCancelText(),
      '#attributes' => ['class' => ['button']],
      '#url' => $url,
    ];
  }

}
