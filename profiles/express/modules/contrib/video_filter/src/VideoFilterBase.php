<?php

namespace Drupal\video_filter;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines base methods for all video filter plugin instances.
 */
class VideoFilterBase extends PluginBase implements VideoFilterInterface {

  use StringTranslationTrait;

  /**
   * Get plugin name.
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Get plugin example URL.
   */
  public function getExampleURL() {
    return $this->pluginDefinition['example_url'];
  }

  /**
   * Get plugin regexp.
   */
  public function getRegexp() {
    return $this->pluginDefinition['regexp'];
  }

  /**
   * Get video player ratio.
   */
  public function getRatio() {
    $ratio = !empty($this->pluginDefinition['ratio']) ? $this->pluginDefinition['ratio'] : '';
    if (!empty($ratio) && preg_match('/(\d+)\/(\d+)/', $ratio, $tratio)) {
      return $tratio[1] / $tratio[2];
    }
    return 1;
  }

  /**
   * Get video player control bar height.
   */
  public function getControlBarHeight() {
    return !empty($this->pluginDefinition['control_bar_height']) ? $this->pluginDefinition['control_bar_height'] : '';
  }

  /**
   * Get Video Filter coded usage instructions.
   */
  public function instructions() {
    // Return text of the instruction for the codec.
  }

  /**
   * HTML5 video (iframe).
   */
  public function iframe($video) {
    // Return HTML5 URL to the video
    // This URL will be passed int iframe.
  }

  /**
   * Flash video (flv)
   */
  public function flash($video) {
    // Usually video URL that will be played
    // with the FLV player.
  }

  /**
   * HTML code of the video player.
   */
  public function html($video) {
    // Usually video URL that will be played
    // with the FLV player.
  }

  /**
   * Embed options. (e.g. Autoplay, Width/Height).
   *
   * Uses Drupal's Form API.
   */
  public function options() {
    $form['width'] = [
      '#title' => $this->t('Width (optional)'),
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => '400',
      ],
    ];
    $form['height'] = [
      '#title' => $this->t('Height (optional)'),
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => '400',
      ],
    ];
    return $form;
  }

  /**
   * Get video preview image.
   *
   * This video will be used in CKEditor.
   */
  public function preview($video) {
    // Returns absolute URL to preview image.
    return drupal_get_path('module', 'video_filter') . '/assets/preview.png';
  }

}
