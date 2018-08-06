<?php

namespace Drupal\libraries\ExternalLibrary;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\libraries\Extension\ExtensionHandlerInterface;
use Drupal\libraries\ExternalLibrary\Exception\LibraryTypeNotFoundException;
use Drupal\libraries\ExternalLibrary\Type\LibraryCreationListenerInterface;
use Drupal\libraries\ExternalLibrary\Type\LibraryLoadingListenerInterface;
use Drupal\libraries\ExternalLibrary\Definition\DefinitionDiscoveryInterface;

/**
 * Provides a manager for external libraries.
 *
 * @todo Dispatch events at various points in the library lifecycle.
 * @todo Automatically load PHP file libraries that are required by modules or
 *   themes.
 */
class LibraryManager implements LibraryManagerInterface {

  /**
   * The library definition discovery.
   *
   * @var \Drupal\libraries\ExternalLibrary\Definition\DefinitionDiscoveryInterface
   */
  protected $definitionDiscovery;

  /**
   * The library type factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $libraryTypeFactory;

  /**
   * The extension handler.
   *
   * @var \Drupal\libraries\Extension\ExtensionHandlerInterface
   */
  protected $extensionHandler;

  /**
   * Constructs an external library manager.
   *
   * @param \Drupal\libraries\ExternalLibrary\Definition\DefinitionDiscoveryInterface $definition_disovery
   *   The library registry.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $library_type_factory
   *   The library type factory.
   * @param \Drupal\libraries\Extension\ExtensionHandlerInterface $extension_handler
   *   The extension handler.
   */
  public function __construct(
    DefinitionDiscoveryInterface $definition_disovery,
    FactoryInterface $library_type_factory,
    ExtensionHandlerInterface $extension_handler
  ) {
    $this->definitionDiscovery = $definition_disovery;
    $this->libraryTypeFactory = $library_type_factory;
    $this->extensionHandler = $extension_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary($id) {
    $definition = $this->definitionDiscovery->getDefinition($id);
    return $this->getLibraryFromDefinition($id, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredLibraryIds() {
    $library_ids = [];
    foreach ($this->extensionHandler->getExtensions() as $extension) {
      foreach ($extension->getLibraryDependencies() as $library_id) {
        $library_ids[] = $library_id;
      }
    }
    return array_unique($library_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $definition = $this->definitionDiscovery->getDefinition($id);
    $library_type = $this->getLibraryType($id, $definition);
    // @todo Throw an exception instead of silently failing.
    if ($library_type instanceof LibraryLoadingListenerInterface) {
      $library_type->onLibraryLoad($this->getLibraryFromDefinition($id, $definition));
    }
  }

  /**
   * @param $id
   * @param $definition
   * @return mixed
   */
  protected function getLibraryFromDefinition($id, $definition) {
    $library_type = $this->getLibraryType($id, $definition);

    // @todo Make this alter-able.
    $class = $library_type->getLibraryClass();

    // @todo Make sure that the library class implements the correct interface.
    $library = $class::create($id, $definition, $library_type);

    if ($library_type instanceof LibraryCreationListenerInterface) {
      $library_type->onLibraryCreate($library);
      return $library;
    }
    return $library;
  }

  /**
   * @param string $id
   * @param array $definition
   *
   * @return \Drupal\libraries\ExternalLibrary\Type\LibraryTypeInterface
   */
  protected function getLibraryType($id, $definition) {
    // @todo Validate that the type is a string.
    if (!isset($definition['type'])) {
      throw new LibraryTypeNotFoundException($id);
    }
    return $this->libraryTypeFactory->createInstance($definition['type']);
  }

}
