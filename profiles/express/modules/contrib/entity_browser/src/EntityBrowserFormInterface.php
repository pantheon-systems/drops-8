<?php

namespace Drupal\entity_browser;

use Drupal\Core\Form\FormInterface;

/**
 * Provides an interface defining an entity browser form.
 */
interface EntityBrowserFormInterface extends FormInterface {

  /**
   * Sets entity browser entity.
   *
   * @param \Drupal\entity_browser\EntityBrowserInterface
   *   Entity browser entity.
   */
  public function setEntityBrowser(EntityBrowserInterface $entity_browser);

}
