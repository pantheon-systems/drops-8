<?php

namespace Drupal\entity\BundlePlugin;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Prevents uninstalling modules with bundle plugins in case of found data.
 */
class BundlePluginUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];

    foreach (entity_get_bundle_plugin_entity_types() as $entity_type) {
      /** @var \Drupal\entity\BundlePlugin\BundlePluginHandler $bundle_handler */
      $bundle_handler = $this->entityTypeManager->getHandler($entity_type->id(), 'bundle_plugin');
      $bundles = $bundle_handler->getBundleInfo();

      // We find all bundles which have to be removed due to the uninstallation.
      $bundles_filtered_by_module = array_filter($bundles, function ($bundle_info) use ($module) {
        return $module === $bundle_info['provider'];
      });

      if (!empty($bundles_filtered_by_module)) {
        $bundle_keys_with_content = array_filter(array_keys($bundles_filtered_by_module), function ($bundle) use ($entity_type) {
          $result = $this->entityTypeManager->getStorage($entity_type->id())->getQuery()
            ->condition($entity_type->getKey('bundle'), $bundle)
            ->range(0, 1)
            ->execute();
          return !empty($result);
        });

        $bundles_with_content = array_intersect_key($bundles_filtered_by_module, array_flip($bundle_keys_with_content));
  
        foreach ($bundles_with_content as $bundle) {
          $reasons[] = $this->t('There is data for the bundle @bundle on the entity type @entity_type. Please remove all content before uninstalling the module.', [
            '@bundle' => $bundle['label'],
            '@entity_type' => $entity_type->getLabel(),
          ]);
        }
      }
    }

    return $reasons;
  }

}
