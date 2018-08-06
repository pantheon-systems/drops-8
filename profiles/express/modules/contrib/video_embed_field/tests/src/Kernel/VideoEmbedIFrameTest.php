<?php

namespace Drupal\Tests\video_embed_field\Kernel;

/**
 * Test that the iframe element works.
 *
 * @group video_embed_field
 */
class VideoEmbedIFrameTest extends KernelTestBase {

  /**
   * Test cases for the embed iframe.
   *
   * @return array
   *   Video iframe test cases.
   */
  public function videoEmbedIframeTestCases() {
    return [
      'Default' => [
        [
          '#type' => 'video_embed_iframe',
        ],
        '<iframe></iframe>',
      ],
      'URL' => [
        [
          '#type' => 'video_embed_iframe',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
        ],
        '<iframe src="https://www.youtube.com/embed/fdbFVWupSsw"></iframe>',
      ],
      'URL, query' => [
        [
          '#type' => 'video_embed_iframe',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
          '#query' => ['autoplay' => '1'],
        ],
        '<iframe src="https://www.youtube.com/embed/fdbFVWupSsw?autoplay=1"></iframe>',
      ],
      'URL, query, attributes' => [
        [
          '#type' => 'video_embed_iframe',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
          '#query' => ['autoplay' => '1'],
          '#attributes' => [
            'width' => '100',
          ],
        ],
        '<iframe width="100" src="https://www.youtube.com/embed/fdbFVWupSsw?autoplay=1"></iframe>',
      ],
      'Query' => [
        [
          '#type' => 'video_embed_iframe',
          '#query' => ['autoplay' => '1'],
        ],
        '<iframe></iframe>',
      ],
      'Query, attributes' => [
        [
          '#type' => 'video_embed_iframe',
          '#query' => ['autoplay' => '1'],
          '#attributes' => [
            'width' => '100',
          ],
        ],
        '<iframe width="100"></iframe>',
      ],
      'Attributes' => [
        [
          '#type' => 'video_embed_iframe',
          '#attributes' => [
            'width' => '100',
          ],
        ],
        '<iframe width="100"></iframe>',
      ],
      'Fragment' => [
        [
          '#type' => 'video_embed_iframe',
          '#url' => 'https://example.com',
          '#fragment' => 'test fragment',
        ],
        '<iframe src="https://example.com#test fragment"></iframe>',
      ],
      'XSS Testing' => [
        [
          '#type' => 'video_embed_iframe',
          '#attributes' => [
            'xss' => '">',
          ],
          '#query' => ['xss' => '">'],
          '#url' => '">',
          '#fragment' => '">',
        ],
        '<iframe xss="&quot;&gt;" src="&quot;&gt;?xss=%22%3E#&quot;&gt;"></iframe>',
      ],
    ];
  }

  /**
   * Test the video embed iframe renders correctly.
   *
   * @dataProvider videoEmbedIframeTestCases
   */
  public function testVideoEmbedIframe($renderable, $markup) {
    $this->assertEquals($markup, trim($this->container->get('renderer')->renderRoot($renderable)));
  }

}
