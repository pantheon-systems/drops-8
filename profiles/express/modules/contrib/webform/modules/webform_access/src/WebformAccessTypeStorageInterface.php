<?php

namespace Drupal\webform_access;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for Webform Access Group storage.
 */
interface WebformAccessTypeStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

}
