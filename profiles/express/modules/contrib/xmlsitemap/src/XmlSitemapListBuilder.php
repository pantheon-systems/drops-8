<?php

namespace Drupal\xmlsitemap;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Provides a listing of XmlSitemap.
 */
class XmlSitemapListBuilder extends ConfigEntityListBuilder {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a XmlSitemapListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $storage);
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
        $entity_type, $container->get('entity.manager')->getStorage($entity_type->id()), $container->get('module_handler'), $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    xmlsitemap_check_status();
    return parent::render();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('XmlSitemap');
    if ($this->moduleHandler->moduleExists('language')) {
      $header['language'] = $this->t('Language');
    }
    $header['id'] = $this->t('Sitemap ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    if ($this->moduleHandler->moduleExists('language')) {
      if (isset($entity->context['language'])) {
        $language = $this->languageManager->getLanguage($entity->context['language']);
        $row['language'] = $language->getName();
      }
      else {
        $row['language'] = $this->t('Undefined');
      }
    }
    $row['id'] = $entity->id();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (isset($operations['translate'])) {
      unset($operations['translate']);
    }
    return $operations;
  }

}
