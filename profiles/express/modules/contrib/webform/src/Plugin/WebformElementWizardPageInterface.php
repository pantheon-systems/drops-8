<?php

namespace Drupal\webform\Plugin;

/**
 * Provides an 'wizard_page' interface used to detect wizard page elements.
 */
interface WebformElementWizardPageInterface extends WebformElementInterface {

  /**
   * Show webform wizard page.
   *
   * @param array $element
   *   A webform wizard page element and its child elements.
   */
  public function showPage(array &$element);

  /**
   * Hide webform wizard page.
   *
   * @param array $element
   *   A webform wizard page element and its child elements.
   */
  public function hidePage(array &$element);

}
