<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Collects available webform elements.
 */
interface WebformElementManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, FallbackPluginManagerInterface, CategorizingPluginManagerInterface, WebformPluginManagerExcludedInterface {

  /**
   * Get all available webform element plugin instances.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface[]
   *   An array of all available webform element plugin instances.
   */
  public function getInstances();

  /**
   * Build a Webform element.
   *
   * @param array $element
   *   An associative array containing an element with a #type property.
   */
  public function initializeElement(array &$element);

  /**
   * Build a Webform element.
   *
   * @param array $element
   *   An associative array containing an element with a #type property.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see hook_webform_element_alter()
   * @see hook_webform_element_ELEMENT_TYPE_alter()
   * @see \Drupal\webform\WebformSubmissionForm::prepareElements
   */
  public function buildElement(array &$element, array $form, FormStateInterface $form_state);

  /**
   * Process a form element and apply webform element specific enhancements.
   *
   * This method allows any form API element to be enhanced using webform
   * specific features include custom validation, external libraries,
   * accessibility improvements, etc…
   *
   * @param array $element
   *   An associative array containing an element with a #type property.
   *
   * @return array
   *   The processed form element with webform element specific enhancements.
   */
  public function processElement(array &$element);

  /**
   * Process form elements and apply webform element specific enhancements.
   *
   * This method allows any form API elements to be enhanced using webform
   * specific features include custom validation, external libraries,
   * accessibility improvements, etc…
   *
   * @param array $elements
   *   An associative array containing form elements.
   *
   * @return array
   *   The processed form elements with webform element specific enhancements.
   */
  public function processElements(array &$elements);

  /**
   * Invoke a method for a Webform element.
   *
   * @param string $method
   *   The method name.
   * @param array $element
   *   An associative array containing an element with a #type property.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference. If more
   *   context needs to be provided to implementations, then this should be an
   *   associative array as described above.
   *
   * @return mixed|null
   *   Return result of the invoked method. NULL will be returned if the
   *   element and/or method name does not exist.
   *
   * @see \Drupal\webform\WebformSubmissionForm::prepareElements
   */
  public function invokeMethod($method, array &$element, &$context1 = NULL, &$context2 = NULL);

  /**
   * Is an element's plugin id.
   *
   * @param array $element
   *   A element.
   *
   * @return string
   *   An element's $type has a corresponding plugin id, else
   *   fallback 'element' plugin id.
   */
  public function getElementPluginId(array $element);

  /**
   * Get a webform element plugin instance for an element.
   *
   * @param array $element
   *   An associative array containing an element with a #type property.
   * @param \Drupal\webform\WebformInterface|\Drupal\webform\WebformSubmissionInterface $entity
   *   A webform or webform submission entity.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface
   *   A webform element plugin instance
   *
   * @throws \Exception
   *   Throw exception if entity type is not a webform or webform submission.
   */
  public function getElementInstance(array $element, EntityInterface $entity = NULL);

  /**
   * Gets sorted plugin definitions.
   *
   * @param array[]|null $definitions
   *   (optional) The plugin definitions to sort. If omitted, all plugin
   *   definitions are used.
   * @param string $sort_by
   *   The property to sort plugin definitions by. Only 'label' and 'category'
   *   are supported. Defaults to label.
   *
   * @return array[]
   *   An array of plugin definitions, sorted by category and label.
   */
  public function getSortedDefinitions(array $definitions = NULL, $sort_by = 'label');

  /**
   * Get all translatable properties from all elements.
   *
   * @return array
   *   An array of translatable properties.
   */
  public function getTranslatableProperties();

  /**
   * Get all properties for all elements.
   *
   * @return array
   *   An array of all properties.
   */
  public function getAllProperties();

  /**
   * Determine if an element type is excluded.
   *
   * @param string $type
   *   The element type.
   *
   * @return bool
   *   TRUE if the element is excluded.
   */
  public function isExcluded($type);

}
