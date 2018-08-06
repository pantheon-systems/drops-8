<?php

namespace Drupal\video_embed_field;

/**
 * Interface for the class that gathers the provider plugins.
 */
interface ProviderManagerInterface {

  /**
   * Get an options list suitable for form elements for provider selection.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getProvidersOptionList();

  /**
   * Load the provider plugin definitions from a FAPI options list value.
   *
   * @param array $options
   *   An array of options from a form API submission.
   *
   * @return array
   *   An array of plugin definitions.
   */
  public function loadDefinitionsFromOptionList($options);

  /**
   * Get the provider applicable to the given user input.
   *
   * @param array $definitions
   *   A list of definitions to test against.
   * @param string $user_input
   *   The user input to test against the plugins.
   *
   * @return \Drupal\video_embed_field\ProviderPluginInterface|bool
   *   The relevant plugin or FALSE on failure.
   */
  public function filterApplicableDefinitions(array $definitions, $user_input);

  /**
   * Load a provider from user input.
   *
   * @param string $input
   *   Input provided from a field.
   *
   * @return \Drupal\video_embed_field\ProviderPluginInterface|bool
   *   The loaded plugin.
   */
  public function loadProviderFromInput($input);

  /**
   * Load a plugin definition from an input.
   *
   * @param string $input
   *   An input string.
   *
   * @return array
   *   A plugin definition.
   */
  public function loadDefinitionFromInput($input);

}
