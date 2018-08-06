<?php

namespace Drupal\entity_browser;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Base implementation for display plugins.
 */
abstract class DisplayBase extends PluginBase implements DisplayInterface, ContainerFactoryPluginInterface {

  use PluginConfigurationFormTrait;

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * Selected entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = [];

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * UUID generator interface.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Instance UUID string.
   *
   * @var string
   */
  protected $uuid = NULL;

  /**
   * The selection storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $selectionStorage;

  /**
   * Constructs display plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   UUID generator interface.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $selection_storage
   *   The selection storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, UuidInterface $uuid_generator, KeyValueStoreExpirableInterface $selection_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->eventDispatcher = $event_dispatcher;
    $this->uuidGenerator = $uuid_generator;
    $this->selectionStorage = $selection_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('uuid'),
      $container->get('entity_browser.selection_storage')
    );
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
  public function getConfiguration() {
    return array_diff_key(
      $this->configuration,
      ['entity_browser_id' => 0]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
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
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    if (empty($this->uuid)) {
      $this->uuid = $this->uuidGenerator->generate();
    }
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function displayEntityBrowser(array $element, FormStateInterface $form_state, array &$complete_form, array $persistent_data = []) {
    // Store persistent data so that after being rendered widgets can still
    // have access to contextual information.
    $this->selectionStorage->setWithExpire(
      $this->getUuid(),
      $persistent_data,
      Settings::get('entity_browser_expire', 21600)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function selectionCompleted(array $entities) {
    $this->entities = $entities;
    $this->eventDispatcher->addListener(KernelEvents::RESPONSE, [$this, 'propagateSelection']);
  }

}
