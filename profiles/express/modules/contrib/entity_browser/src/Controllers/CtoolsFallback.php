<?php

namespace Drupal\entity_browser\Controllers;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns markup for entity browser entity add/edit page if ctools is missing.
 */
class CtoolsFallback extends ControllerBase {

  /**
   * Displays message about missing dependency on edit/add page.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response with a command for opening or closing the dialog
   *   containing the edit form.
   */
  public function displayMessage() {
    return [
      '#markup' => $this->t(
        'This form depends on <a href=":url">Chaos tool suite module</a>. Enable it and reload this page.',
        [':url' => Url::fromUri('https://drupal.org/project/ctools')->toString()]
      ),
    ];
  }

}
