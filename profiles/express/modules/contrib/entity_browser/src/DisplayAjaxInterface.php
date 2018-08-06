<?php

namespace Drupal\entity_browser;

/**
 * Defines the interface for entity browser displays.
 */
interface DisplayAjaxInterface {

  /**
   * Adds ajax capabilities to the entity browser form.
   *
   * This will be used in Plugins like Modal that require the Entity Browser
   * form to be sumbitted with ajax.  All other plugins that don't require it
   * can leave it blank.
   *
   * @param array $form
   *   Form array containing the Entity Browser elements.
   */
  public function addAjax(array &$form);

}
