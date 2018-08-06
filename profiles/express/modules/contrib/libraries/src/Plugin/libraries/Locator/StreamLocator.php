<?php

namespace Drupal\libraries\Plugin\libraries\Locator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\libraries\ExternalLibrary\Local\LocalLibraryInterface;
use Drupal\libraries\ExternalLibrary\Local\LocatorInterface;
use Drupal\libraries\Plugin\MissingPluginConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a locator utilizing a stream wrapper.
 *
 * It makes the following assumptions:
 * - The library files can be accessed using a specified stream.
 * - The stream wrapper is local (i.e. it is a subclass of
 *   \Drupal\Core\StreamWrapper\LocalStream).
 * - The first component of the file URIs are the library IDs (i.e. file URIs
 *   are of the form: scheme://library-id/path/to/file/filename).
 *
 * @Locator("stream")
 *
 * @see \Drupal\libraries\ExternalLibrary\Local\LocatorInterface
 */
class StreamLocator implements LocatorInterface, ContainerFactoryPluginInterface {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The scheme of the stream wrapper.
   *
   * @var string
   */
  protected $scheme;

  /**
   * Constructs a stream locator.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param string $scheme
   *   The scheme of the stream wrapper.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager, $scheme) {
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->scheme = (string) $scheme;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['scheme'])) {
      throw new MissingPluginConfigurationException($plugin_id, $plugin_definition, $configuration, 'scheme');
    }
    return new static($container->get('stream_wrapper_manager'), $configuration['scheme']);
  }

  /**
   * Locates a library.
   *
   * @param \Drupal\libraries\ExternalLibrary\Local\LocalLibraryInterface $library
   *   The library to locate.
   *
   * @see \Drupal\libraries\ExternalLibrary\Local\LocatorInterface::locate()
   */
  public function locate(LocalLibraryInterface $library) {
    /** @var \Drupal\Core\StreamWrapper\LocalStream $stream_wrapper */
    $stream_wrapper = $this->streamWrapperManager->getViaScheme($this->scheme);
    assert('$stream_wrapper instanceof \Drupal\Core\StreamWrapper\LocalStream');
    // Calling LocalStream::getDirectoryPath() explicitly avoids the realpath()
    // usage in LocalStream::getLocalPath(), which breaks if Libraries API is
    // symbolically linked into the Drupal installation.
    $path = $stream_wrapper->getDirectoryPath() . '/' . $library->getId();

    if (is_dir($path) && is_readable($path)) {
      $library->setLocalPath($path);
    }
    else {
      $library->setUninstalled();
    }
  }

}
