<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\webform\WebformInterface;

/**
 * Defines the interface for webform variants.
 *
 * @see \Drupal\webform\Annotation\WebformVariant
 * @see \Drupal\webform\Plugin\WebformVariantBase
 * @see \Drupal\webform\Plugin\WebformVariantManager
 * @see \Drupal\webform\Plugin\WebformVariantManagerInterface
 * @see plugin_api
 */
interface WebformVariantInterface extends PluginInspectionInterface, ConfigurableInterface, ContainerFactoryPluginInterface, PluginFormInterface, WebformEntityInjectionInterface {

  /**
   * Returns a render array summarizing the configuration of the webform variant.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns the webform variant label.
   *
   * @return string
   *   The webform variant label.
   */
  public function label();

  /**
   * Returns the webform variant description.
   *
   * @return string
   *   The webform variant description.
   */
  public function description();

  /**
   * Returns the unique ID representing the webform variant.
   *
   * @return string
   *   The webform variant ID.
   */
  public function getVariantId();

  /**
   * Sets the id for this webform variant.
   *
   * @param int $variant_id
   *   The variant_id for this webform variant.
   *
   * @return $this
   */
  public function setVariantId($variant_id);

  /**
   * Returns the element key of the webform variant.
   *
   * @return string
   *   The webform element key.
   */
  public function getElementKey();

  /**
   * Sets the element key of this webform variant.
   *
   * @param int $element_key
   *   The element key for this webform variant.
   *
   * @return $this
   */
  public function setElementKey($element_key);

  /**
   * Returns the label of the webform variant.
   *
   * @return string
   *   The label of the webform variant, or an empty string.
   */
  public function getLabel();

  /**
   * Sets the label for this webform variant.
   *
   * @param string $label
   *   The label for this webform variant.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns notes of the webform variant.
   *
   * @return string
   *   Notes for the webform variant, or an empty string.
   */
  public function getNotes();

  /**
   * Set notes for this webform variant.
   *
   * @param string $notes
   *   Notes for this webform variant.
   *
   * @return $this
   */
  public function setNotes($notes);

  /**
   * Returns the weight of the webform variant.
   *
   * @return int|string
   *   Either the integer weight of the webform variant, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this webform variant.
   *
   * @param int $weight
   *   The weight for this webform variant.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Returns the status of the webform variant.
   *
   * @return bool
   *   The status of the webform variant.
   */
  public function getStatus();

  /**
   * Sets the status for this webform variant.
   *
   * @param bool $status
   *   The status for this webform variant.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Enables the webform variant.
   *
   * @return $this
   */
  public function enable();

  /**
   * Disables the webform variant.
   *
   * @return $this
   */
  public function disable();

  /**
   * Checks if the variant is excluded via webform.settings.
   *
   * @return bool
   *   TRUE if the variant is excluded.
   */
  public function isExcluded();

  /**
   * Returns the webform variant enabled indicator.
   *
   * @return bool
   *   TRUE if the webform variant is enabled.
   */
  public function isEnabled();

  /**
   * Returns the webform variant disabled indicator.
   *
   * @return bool
   *   TRUE if the webform variant is disabled.
   */
  public function isDisabled();

  /**
   * Determine if this variant is applicable to the webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return bool
   *   TRUE if this variant is applicable to the webform.
   */
  public function isApplicable(WebformInterface $webform);

  /**
   * Apply variant to the webform.
   *
   * @return bool
   *   TRUE if this variant was applied to the webform.
   */
  public function applyVariant();

}
