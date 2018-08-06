<?php

namespace Drupal\crop\Plugin\Crop\EntityProvider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\crop\EntityProviderBase;

/**
 * File crop integration.
 *
 * @CropEntityProvider(
 *   entity_type = "file",
 *   label = @Translation("File"),
 *   description = @Translation("Provides crop integration for core files.")
 * )
 */
class File extends EntityProviderBase {

  /**
   * {@inheritdoc}
   */
  public function uri(EntityInterface $entity) {
    /** @var \Drupal\file\FileInterface $entity */
    return $entity->getFileUri();
  }

}
