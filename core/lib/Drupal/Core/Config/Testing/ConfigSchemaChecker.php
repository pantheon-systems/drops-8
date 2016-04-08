<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Testing\ConfigSchemaChecker.
 */

namespace Drupal\Core\Config\Testing;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the config save event and validates schema.
 *
 * If tests have the $strictConfigSchema property set to TRUE this event
 * listener will be added to the container and throw exceptions if configuration
 * is invalid.
 *
 * @see \Drupal\simpletest\WebTestBase::setUp()
 * @see \Drupal\KernelTests\KernelTestBase::register()
 */
class ConfigSchemaChecker implements EventSubscriberInterface {
  use SchemaCheckTrait;

  /**
   * The typed config manger.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedManager;

  /**
   * An array of config checked already. Keyed by config name and a checksum.
   *
   * @var array
   */
  protected $checked = array();

  /**
   * An array of config object names that are excluded from schema checking.
   *
   * @var string[]
   */
  protected $exclude = array();

  /**
   * Constructs the ConfigSchemaChecker object.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_manager
   *   The typed config manager.
   * @param string[] $exclude
   *   An array of config object names that are excluded from schema checking.
   */
  public function __construct(TypedConfigManagerInterface $typed_manager, array $exclude = array()) {
    $this->typedManager = $typed_manager;
    $this->exclude = $exclude;
  }

  /**
   * Checks that configuration complies with its schema on config save.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   *
   * @throws \Drupal\Core\Config\Schema\SchemaIncompleteException
   *   Exception thrown when configuration does not match its schema.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    // Only validate configuration if in the default collection. Other
    // collections may have incomplete configuration (for example language
    // overrides only). These are not valid in themselves.
    $saved_config = $event->getConfig();
    if ($saved_config->getStorage()->getCollectionName() != StorageInterface::DEFAULT_COLLECTION) {
      return;
    }

    $name = $saved_config->getName();
    $data = $saved_config->get();
    $checksum = hash('crc32b', serialize($data));
    if (!in_array($name, $this->exclude) && !isset($this->checked[$name . ':' . $checksum])) {
      $this->checked[$name . ':' . $checksum] = TRUE;
      $errors = $this->checkConfigSchema($this->typedManager, $name, $data);
      if ($errors === FALSE) {
        throw new SchemaIncompleteException("No schema for $name");
      }
      elseif (is_array($errors)) {
        $text_errors = [];
        foreach ($errors as $key => $error) {
          $text_errors[] = SafeMarkup::format('@key @error', array('@key' => $key, '@error' => $error));
        }
        throw new SchemaIncompleteException("Schema errors for $name with the following errors: " . implode(', ', $text_errors));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 255);
    return $events;
  }

}
