<?php

namespace Drupal\Tests\video_embed_field\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Thumbnail;

/**
 * Test the embed field formatters are functioning.
 *
 * @group video_embed_field
 */
class FieldOutputTest extends KernelTestBase {

  use StripWhitespaceTrait;

  /**
   * The test cases.
   */
  public function renderedFieldTestCases() {
    return [
      'YouTube: Thumbnail' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        [
          'type' => 'video_embed_field_thumbnail',
          'settings' => [],
        ],
        [
          '#theme' => 'image',
          '#uri' => 'public://video_thumbnails/fdbFVWupSsw.jpg',
        ],
      ],
      'YouTube: Thumbnail With Image Style' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        [
          'type' => 'video_embed_field_thumbnail',
          'settings' => [
            'image_style' => 'thumbnail',
          ],
        ],
        [
          '#theme' => 'image_style',
          '#uri' => 'public://video_thumbnails/fdbFVWupSsw.jpg',
          '#style_name' => 'thumbnail',
        ],
      ],
      'YouTube: Embed Code' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        [
          'type' => 'video_embed_field_video',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'video_embed_iframe',
          '#provider' => 'youtube',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
          '#query' => [
            'autoplay' => '1',
            'start' => '0',
            'rel' => '0',
          ],
          '#attributes' => [
            'width' => '100',
            'height' => '100',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen',
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
        ],
      ],
      'YouTube: Time-index Embed Code' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw&t=100',
        [
          'type' => 'video_embed_field_video',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'video_embed_iframe',
          '#provider' => 'youtube',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
          '#query' => [
            'autoplay' => '1',
            'start' => '100',
            'rel' => '0',
          ],
          '#attributes' => [
            'width' => '100',
            'height' => '100',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen',
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
        ],
      ],
      'YouTube: Language Specified Embed Code' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw&hl=fr',
        [
          'type' => 'video_embed_field_video',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'video_embed_iframe',
          '#provider' => 'youtube',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
          '#query' => [
            'autoplay' => '1',
            'start' => '0',
            'rel' => '0',
            'cc_lang_pref' => 'fr',
          ],
          '#attributes' => [
            'width' => '100',
            'height' => '100',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen',
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
        ],
      ],
      'Vimeo: Thumbnail' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'video_embed_field_thumbnail',
          'settings' => [],
        ],
        [
          '#theme' => 'image',
          '#uri' => 'public://video_thumbnails/80896303.jpg',
        ],
      ],
      'Vimeo: Embed Code' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'video_embed_field_video',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'video_embed_iframe',
          '#provider' => 'vimeo',
          '#url' => 'https://player.vimeo.com/video/80896303',
          '#query' => [
            'autoplay' => '1',
          ],
          '#attributes' => [
            'width' => '100',
            'height' => '100',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen',
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
        ],
      ],
      'Vimeo: Autoplaying Embed Code' => [
        'https://vimeo.com/80896303#t=150s',
        [
          'type' => 'video_embed_field_video',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'video_embed_iframe',
          '#provider' => 'vimeo',
          '#url' => 'https://player.vimeo.com/video/80896303',
          '#query' => [
            'autoplay' => '1',
          ],
          '#fragment' => 't=150s',
          '#attributes' => [
            'width' => '100',
            'height' => '100',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen',
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
        ],
      ],
      'Linked Thumbnail: Content' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'video_embed_field_thumbnail',
          'settings' => ['link_image_to' => Thumbnail::LINK_CONTENT],
        ],
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => 'public://video_thumbnails/80896303.jpg',
          ],
          '#url' => 'entity.entity_test.canonical',
        ],
      ],
      'Linked Thumbnail: Provider' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'video_embed_field_thumbnail',
          'settings' => ['link_image_to' => Thumbnail::LINK_PROVIDER],
        ],
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => 'public://video_thumbnails/80896303.jpg',
          ],
          '#url' => 'https://vimeo.com/80896303',
        ],
      ],
      'Colorbox Modal: Linked Image & Autoplay' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'video_embed_field_colorbox',
          'settings' => [
            'link_image_to' => Thumbnail::LINK_PROVIDER,
            'autoplay' => TRUE,
            'width' => 500,
            'height' => 500,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'data-video-embed-field-modal' => '<iframe width="500" height="500" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/80896303?autoplay=1"></iframe>',
            'class' => ['video-embed-field-launch-modal'],
          ],
          '#attached' => [
            'library' => [
              'video_embed_field/colorbox',
              'video_embed_field/responsive-video',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
          'children' => [
            '#type' => 'link',
            '#title' => [
              '#theme' => 'image',
              '#uri' => 'public://video_thumbnails/80896303.jpg',
            ],
            '#url' => 'https://vimeo.com/80896303',
          ],
        ],
      ],
      'Colorbox Modal: Responsive' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'video_embed_field_colorbox',
          'settings' => [
            'link_image_to' => Thumbnail::LINK_PROVIDER,
            'autoplay' => TRUE,
            'width' => 900,
            'height' => 450,
            'responsive' => TRUE,
            'modal_max_width' => 999,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'data-video-embed-field-modal' => '<div class="video-embed-field-responsive-video video-embed-field-responsive-modal" style="width:999px;"><iframe width="900" height="450" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/80896303?autoplay=1"></iframe></div>',
            'class' => [
              'video-embed-field-launch-modal',
            ],
          ],
          '#attached' => [
            'library' => [
              'video_embed_field/colorbox',
              'video_embed_field/responsive-video',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
          'children' => [
            '#type' => 'link',
            '#title' => [
              '#theme' => 'image',
              '#uri' => 'public://video_thumbnails/80896303.jpg',
            ],
            '#url' => 'https://vimeo.com/80896303',
          ],
        ],
      ],
      'Video: Responsive' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'video_embed_field_video',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => TRUE,
          ],
        ],
        [
          '#type' => 'container',
          '#attached' => [
            'library' => ['video_embed_field/responsive-video'],
          ],
          '#attributes' => [
            'class' => ['video-embed-field-responsive-video'],
          ],
          'children' => [
            '#type' => 'video_embed_iframe',
            '#provider' => 'vimeo',
            '#url' => 'https://player.vimeo.com/video/80896303',
            '#query' => [
              'autoplay' => '1',
            ],
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'YouTube Playlist' => [
        'https://www.youtube.com/watch?v=xoJH3qZwsHc&list=PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
        [
          'type' => 'video_embed_field_video',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'video_embed_iframe',
          '#provider' => 'youtube_playlist',
          '#url' => 'https://www.youtube.com/embed/videoseries',
          '#query' => [
            'list' => 'PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
          ],
          '#attributes' => [
            'width' => '100',
            'height' => '100',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen',
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
        ],
      ],
      'No provider (video formatter)' => [
        'http://example.com/not/a/video/url',
        [
          'type' => 'video_embed_field_video',
          'settings' => [],
        ],
        [
          '#theme' => 'video_embed_field_missing_provider',
        ],
      ],
      'No provider (thumbnail formatter)' => [
        'http://example.com/not/a/video/url',
        [
          'type' => 'video_embed_field_thumbnail',
          'settings' => [],
        ],
        [
          '#theme' => 'video_embed_field_missing_provider',
        ],
      ],
      'No provider (colorbox modal)' => [
        'http://example.com/not/a/video/url',
        [
          'type' => 'video_embed_field_colorbox',
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'data-video-embed-field-modal' => 'No video provider was found to handle the given URL. See <a href="https://www.drupal.org/node/2842927">the documentation</a> for more information.',
            'class' => ['video-embed-field-launch-modal'],
          ],
          '#attached' => [
            'library' => [
              'video_embed_field/colorbox',
              'video_embed_field/responsive-video',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
          'children' => [
            '#theme' => 'video_embed_field_missing_provider',
          ],
        ],
      ],
    ];
  }

  /**
   * Test the embed field.
   *
   * @dataProvider renderedFieldTestCases
   */
  public function testEmbedField($url, $settings, $expected_field_item_output) {

    $field_output = $this->getPreparedFieldOutput($url, $settings);

    // Assert the specific field output at delta 1 matches the expected test
    // data.
    $this->assertEquals($expected_field_item_output, $field_output[0]);
  }

  /**
   * Get and prepare the output of a field.
   *
   * @param string $url
   *   The video URL.
   * @param array $settings
   *   An array of formatter settings.
   *
   * @return array
   *   The rendered prepared field output.
   */
  protected function getPreparedFieldOutput($url, $settings) {
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->value = $url;
    $entity->save();

    $field_output = $this->container->get('renderer')->executeInRenderContext(new RenderContext(), function() use ($entity, $settings) {
      return $entity->{$this->fieldName}->view($settings);
    });

    // Prepare the field output to make it easier to compare our test data
    // values against.
    array_walk_recursive($field_output[0], function (&$value) {
      // Prevent circular references with comparing field output that
      // contains url objects.
      if ($value instanceof Url) {
        $value = $value->isRouted() ? $value->getRouteName() : $value->getUri();
      }
      // Trim to prevent stray whitespace for the colorbox formatters with
      // early rendering.
      $value = $this->stripWhitespace($value);
    });

    return $field_output;
  }

}
