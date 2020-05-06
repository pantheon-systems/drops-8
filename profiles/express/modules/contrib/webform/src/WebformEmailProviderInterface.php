<?php

namespace Drupal\webform;

/**
 * Defines the interface for webform email provider.
 */
interface WebformEmailProviderInterface {

  /**
   * Get list of known contrib module that support HTML email.
   *
   * @return array
   *   An array containing known contrib module that support HTML email.
   */
  public function getModules();

  /**
   * Check if the Webform module should provide support for sending HTML emails.
   */
  public function check();

  /**
   * Check if webform email handler is installed.
   */
  public function installed();

  /**
   * Install webform's PHP mail handler which supports sending HTML emails.
   */
  public function install();

  /**
   * Uninstall webform's PHP mail handler which supports sending HTML emails.
   */
  public function uninstall();

  /**
   * Get the HTML email provider module machine name.
   *
   * @return bool|string
   *   The HTML email provider module machine name.
   */
  public function getModule();

  /**
   * Get the HTML email provider human readable module name.
   *
   * @return bool|string
   *   The HTML email provider module name.
   */
  public function getModuleName();

  /**
   * Determine if mail module is installed and enabled.
   *
   * @param string $module
   *   Mail module name.
   *
   * @return bool
   *   TRUE if mail module is installed and enabled.
   */
  public function moduleEnabled($module);

  /**
   * Get the mail back-end plugin id.
   *
   * @return string
   *   The email handler plugin id.
   */
  public function getMailPluginId();

  /**
   * Get the mail back-end plugin definition.
   *
   * @return array
   *   A plugin definition array.
   */
  public function getMailPluginDefinition();

}
