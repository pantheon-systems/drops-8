<?php

namespace Drupal\video_filter\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "VideoFilter" plugin.
 *
 * @CKEditorPlugin(
 *   id = "video_filter",
 *   label = @Translation("Video Filter")
 * )
 */
class VideoFilter extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'video_filter') . '/assets/video_filter.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'video_filter' => [
        'label' => t('Video Filter'),
        'image' => drupal_get_path('module', 'video_filter') . '/assets/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'video_filter_dialog_title' => t('Video Filter'),
    ];
  }

}
