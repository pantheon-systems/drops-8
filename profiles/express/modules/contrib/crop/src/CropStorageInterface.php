<?php

namespace Drupal\crop;

use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Provides an interface defining an crop storage controller.
 */
interface CropStorageInterface extends SqlEntityStorageInterface, DynamicallyFieldableEntityStorageSchemaInterface, EntityBundleListenerInterface {

}
