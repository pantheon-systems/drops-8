<?php

namespace Drupal\video_embed_field\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;

/**
 * Providers an element design for embedding iframes.
 *
 * @RenderElement("video_embed_iframe")
 */
class VideoEmbedIFrame extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'video_embed_iframe',
      '#provider' => '',
      '#url' => '',
      '#query' => [],
      '#attributes' => [],
      '#fragment' => [],
      '#pre_render' => [
        [static::class, 'preRenderInlineFrameEmbed'],
      ],
    ];
  }

  /**
   * Transform the render element structure into a renderable one.
   *
   * @param array $element
   *   An element array before being processed.
   *
   * @return array
   *   The processed and renderable element.
   */
  public static function preRenderInlineFrameEmbed($element) {
    $element['#theme'] .= !empty($element['#provider']) ? '__' . $element['#provider'] : '';

    if (is_array($element['#attributes'])) {
      $element['#attributes'] = new Attribute($element['#attributes']);
    }

    return $element;
  }

}
