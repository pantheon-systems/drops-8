 <?php

/**
 * @file
 * Documentation of webform views module.
 */

/**
 * Alter webform element views handler.
 *
 * You may use this hook to specify special handlers on per-element and/or
 * per-webform basis while not affecting the rest of webform element handlers.
 * On the other hand, if you want to specify a new "default" views handler class
 * for a webform element, you are advised to it via
 * hook_webform_element_info_alter() instead. See
 * webform_views_webform_element_info_alter() for sample code.
 *
 * @param string $views_handler_class
 *   Name of the current webform element views handler class
 * @param array $element
 *   Webform element whose views handler is being altered
 * @param \Drupal\webform\WebformInterface $webform
 *   Webform where $element belongs to
 */
function hook_webform_views_element_views_handler(&$views_handler_class, $element, \Drupal\webform\WebformInterface $webform) {
  if ($webform->id() == 'my_special_webform' && $element['#webform_key'] == 'my_special_webform_element') {
    $views_handler_class = '\Drupal\custom\WebformElementViews\MySpecialHandler';
  }
}
