<?php

namespace Drupal\webform_node;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Prevents webform_node module from being uninstalled whilst any webform nodes exist.
 */
class WebformNodeUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a WebformNodeUninstallValidator.
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
    if ($module == 'webform_node') {
      // The webform node type is provided by the Webform node module. Prevent
      // uninstall if there are any nodes of that type.
      if ($this->hasWebformNodes()) {
        $reasons[] = $this->t('To uninstall Webform node, delete all content that has the Webform content type.');
      }
    }
    return $reasons;
  }

  /**
   * Determines if there is any webform nodes or not.
   *
   * @return bool
   *   TRUE if there are webform nodes, FALSE otherwise.
   */
  protected function hasWebformNodes() {
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'webform')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($nodes);
  }

}
