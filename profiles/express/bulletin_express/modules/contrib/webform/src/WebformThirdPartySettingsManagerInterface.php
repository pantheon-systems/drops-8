<?php

namespace Drupal\webform;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Defines an interface for webform third party settings manager classes.
 */
interface WebformThirdPartySettingsManagerInterface extends ThirdPartySettingsInterface {

  /**
   * Wrapper for \Drupal\Core\Extension\ModuleHandlerInterface::alter.
   *
   * Loads all webform third party settings before execute alter hooks.
   *
   * @see \Drupal\webform\WebformThirdPartySettingsManager::__construct
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL);

  /**
   * Third party settings webform constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The webform structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state);

  /**
   * Webform element #after_build callback: Checks for 'third_party_settings'.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The webform structure.
   */
  public function afterBuild(array $form, FormStateInterface $form_state);

}
