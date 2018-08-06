<?php

namespace Drupal\video_embed_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Video;

/**
 * The media_entity plugin for video_embed_field.
 *
 * @CKEditorPlugin(
 *   id = "video_embed",
 *   label = @Translation("Video Embed WYSIWYG")
 * )
 */
class VideoEmbedWysiwyg extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'video_embed_wysiwyg') . '/plugin/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'video_embed' => [
        'label' => $this->t('Video Embed'),
        'image' => drupal_get_path('module', 'video_embed_wysiwyg') . '/plugin/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $editor_settings = $editor->getSettings();
    $plugin_settings = NestedArray::getValue($editor_settings, [
      'plugins',
      'video_embed',
      'defaults',
      'children',
    ]);
    $settings = $plugin_settings ?: [];

    $form['defaults'] = [
      '#title' => $this->t('Default Settings'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      'children' => Video::mockInstance($settings)->settingsForm([], new FormState()),
    ];
    return $form;
  }

}
