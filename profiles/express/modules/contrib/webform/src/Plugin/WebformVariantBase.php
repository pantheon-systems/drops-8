<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform variant.
 *
 * @see \Drupal\webform\Plugin\WebformVariantInterface
 * @see \Drupal\webform\Plugin\WebformVariantManager
 * @see \Drupal\webform\Plugin\WebformVariantManagerInterface
 * @see plugin_api
 */
abstract class WebformVariantBase extends PluginBase implements WebformVariantInterface {

  use WebformEntityInjectionTrait;

  /**
   * The webform variant ID.
   *
   * @var string
   */
  protected $variant_id;

  /**
   * The element key of the webform variant.
   *
   * @var string
   */
  protected $element_key = '';

  /**
   * The webform variant label.
   *
   * @var string
   */
  protected $label;


  /**
   * The webform variant notes.
   *
   * @var string
   */
  protected $notes = '';

  /**
   * The webform variant status.
   *
   * @var bool
   */
  protected $status = 1;

  /**
   * The weight of the webform variant.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a WebformVariantBase object.
   *
   * IMPORTANT:
   * Webform variants are initialized and serialized when they are attached to a
   * webform. Make sure not include any services as a dependency injection
   * that directly connect to the database. This will prevent
   * "LogicException: The database connection is not serializable." exceptions
   * from being thrown when a form is serialized via an Ajax callback and/or
   * form build.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   *
   * @see \Drupal\webform\Entity\Webform::getVariants
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'webform_variant_' . $this->pluginId . '_summary',
      '#settings' => $this->configuration,
      '#variant' => $this,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getVariantId() {
    return $this->variant_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setVariantId($variant_id) {
    $this->variant_id = $variant_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementKey() {
    return $this->element_key;
  }

  /**
   * {@inheritdoc}
   */
  public function setElementKey($element_key) {
    $this->element_key = $element_key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotes($notes) {
    $this->notes = $notes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes() {
    return $this->notes;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    return $this->setStatus(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    return $this->setStatus(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() {
    return $this->configFactory->get('webform.settings')
      ->get('variant.excluded_variants.' . $this->pluginDefinition['id']) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(WebformInterface $webform) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'label' => $this->getLabel(),
      'notes' => $this->getNotes(),
      'variant_id' => $this->getVariantId(),
      'element_key' => $this->getElementKey(),
      'status' => $this->getStatus(),
      'weight' => $this->getWeight(),
      'settings' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'variant_id' => '',
      'element_key' => '',
      'label' => '',
      'notes' => '',
      'status' => 1,
      'weight' => '',
      'settings' => [],
    ];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();
    $this->variant_id = $configuration['variant_id'];
    $this->element_key = $configuration['element_key'];
    $this->label = $configuration['label'];
    $this->notes = $configuration['notes'];
    $this->status = $configuration['status'];
    $this->weight = $configuration['weight'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function applyVariant() {
    $webform = $this->getWebform();
    // Do not apply variant if it is not applicable to the webform.
    if (!$this->isApplicable($webform)) {
      return FALSE;
    }
    // Apply variant here.
    return TRUE;
  }

}
