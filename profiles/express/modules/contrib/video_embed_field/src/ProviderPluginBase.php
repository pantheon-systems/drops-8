<?php

namespace Drupal\video_embed_field;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base for the provider plugins.
 */
abstract class ProviderPluginBase extends PluginBase implements ProviderPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The directory where thumbnails are stored.
   *
   * @var string
   */
  protected $thumbsDirectory = 'public://video_thumbnails';

  /**
   * The ID of the video.
   *
   * @var string
   */
  protected $videoId;

  /**
   * The input that caused the embed provider to be selected.
   *
   * @var string
   */
  protected $input;

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Create a plugin with the given input.
   *
   * @param string $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *    An HTTP client.
   *
   * @throws \Exception
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!static::isApplicable($configuration['input'])) {
      throw new \Exception('Tried to create a video provider plugin with invalid input.');
    }
    $this->input = $configuration['input'];
    $this->videoId = $this->getIdFromInput($configuration['input']);
    $this->httpClient = $http_client;
  }

  /**
   * Get the ID of the video.
   *
   * @return string
   *   The video ID.
   */
  protected function getVideoId() {
    return $this->videoId;
  }

  /**
   * Get the input which caused this plugin to be selected.
   *
   * @return string
   *   The raw input from the user.
   */
  protected function getInput() {
    return $this->input;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    $id = static::getIdFromInput($input);
    return !empty($id);
  }

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {
    $output = [
      '#theme' => 'image',
      '#uri' => $this->getLocalThumbnailUri(),
    ];

    if (!empty($image_style)) {
      $output['#theme'] = 'image_style';
      $output['#style_name'] = $image_style;
    }

    if ($link_url) {
      $output = [
        '#type' => 'link',
        '#title' => $output,
        '#url' => $link_url,
      ];
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadThumbnail() {
    $local_uri = $this->getLocalThumbnailUri();
    if (!file_exists($local_uri)) {
      file_prepare_directory($this->thumbsDirectory, FILE_CREATE_DIRECTORY);
      try {
        $thumbnail = $this->httpClient->request('GET', $this->getRemoteThumbnailUrl());
        file_unmanaged_save_data((string) $thumbnail->getBody(), $local_uri);
      }
      catch (\Exception $e) {
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    return $this->thumbsDirectory . '/' . $this->getVideoId() . '.jpg';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('http_client'));
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('@provider Video (@id)', ['@provider' => $this->getPluginDefinition()['title'], '@id' => $this->getVideoId()]);
  }

}
