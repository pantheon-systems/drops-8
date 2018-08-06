<?php

namespace Drupal\video_embed_wysiwyg\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Video;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * The filter to turn tokens inserted into the WYSIWYG into videos.
 *
 * @Filter(
 *   title = @Translation("Video Embed WYSIWYG"),
 *   id = "video_embed_wysiwyg",
 *   description = @Translation("Enables the use of video_embed_wysiwyg."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class VideoEmbedWysiwyg extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The video provider manager.
   *
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * VideoEmbedWysiwyg constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\video_embed_field\ProviderManagerInterface $provider_manager
   *   The video provider manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProviderManagerInterface $provider_manager, RendererInterface $renderer, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('video_embed_field.provider_manager'), $container->get('renderer'), $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    $response = new FilterProcessResult($text);

    foreach ($this->getValidMatches($text) as $source_text => $embed_data) {
      if (!$provider = $this->providerManager->loadProviderFromInput($embed_data['video_url'])) {
        continue;
      }

      $autoplay = $this->currentUser->hasPermission('never autoplay videos') ? FALSE : $embed_data['settings']['autoplay'];
      $embed_code = $provider->renderEmbedCode($embed_data['settings']['width'], $embed_data['settings']['height'], $autoplay);

      // Add the container to make the video responsive if it's been
      // configured as such. This usually is attached to field output in the
      // case of a formatter, but a custom container must be used where one is
      // not present.
      if ($embed_data['settings']['responsive']) {
        $embed_code = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['video-embed-field-responsive-video'],
          ],
          'children' => $embed_code,
        ];
      }

      // Replace the JSON settings with a video.
      $text = str_replace($source_text, $this->renderer->render($embed_code), $text);

      // Add the required responsive video library only when at least one match
      // is present.
      $response->setAttachments(['library' => ['video_embed_field/responsive-video']]);
      $response->setCacheContexts(['user.permissions']);
    }

    $response->setProcessedText($text);
    return $response;
  }

  /**
   * Get all valid matches in the WYSIWYG.
   *
   * @param string $text
   *   The text to check for WYSIWYG matches.
   *
   * @return array
   *   An array of data from the text keyed by the text content.
   */
  protected function getValidMatches($text) {
    // Use a look ahead to match the capture groups in any order.
    if (!preg_match_all('/(<p>)?(?<json>{(?=.*preview_thumbnail\b)(?=.*settings\b)(?=.*video_url\b)(?=.*settings_summary)(.*)})(<\/p>)?/', $text, $matches)) {
      return [];
    }
    $valid_matches = [];
    foreach ($matches['json'] as $delta => $match) {
      // Ensure the JSON string is valid.
      $embed_data = json_decode($match, TRUE);
      if (!$embed_data || !is_array($embed_data)) {
        continue;
      }
      if ($this->isValidSettings($embed_data['settings'])) {
        $valid_matches[$matches[0][$delta]] = $embed_data;
      }
    }
    return $valid_matches;
  }

  /**
   * Check if the given settings are valid.
   *
   * @param array $settings
   *   Settings to validate.
   *
   * @return bool
   *   If the required settings are present.
   */
  protected function isValidSettings($settings) {
    foreach (Video::defaultSettings() as $setting => $default) {
      if (!isset($settings[$setting])) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
