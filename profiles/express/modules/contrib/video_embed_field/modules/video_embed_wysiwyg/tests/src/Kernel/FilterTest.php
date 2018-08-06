<?php

namespace Drupal\Tests\video_embed_wysiwyg\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\video_embed_field\Kernel\KernelTestBase;

/**
 * A test for the filter.
 *
 * @group video_embed_wysiwyg
 */
class FilterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'field',
    'text',
    'entity_test',
    'field_test',
    'video_embed_field',
    'video_embed_wysiwyg',
    'image',
    'filter',
  ];

  /**
   * The filter to use for the test.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $filter;

  /**
   * Test cases for the video filter test.
   *
   * @return array
   *   An array of test cases and FALSE when no change is expected.
   */
  public function videoFilterTestCases() {
    return [
      'Standard embed code' => [
        '<p>Content.</p><p>{"preview_thumbnail":"http://example.com/thumbnail.jpg","video_url":"https://www.youtube.com/watch?v=uNRtZDAS0xI","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Video (854x480, autoplaying)."]}</p><p>More content.</p>',
        '<p>Content.</p><div class="video-embed-field-responsive-video"><iframe width="854" height="480" frameborder="0" allowfullscreen="allowfullscreen" src="https://www.youtube.com/embed/uNRtZDAS0xI?autoplay=1&amp;start=0&amp;rel=0"></iframe></div><p>More content.</p>',
      ],
      'Embedded vimeo video' => [
        '<p>Content.</p><p>{"preview_thumbnail":"http://example.com/thumbnail.jpg","video_url":"https://vimeo.com/18352872","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Video (854x480, autoplaying)."]}</p><p>More content.</p>',
        '<p>Content.</p><div class="video-embed-field-responsive-video"><iframe width="854" height="480" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/18352872?autoplay=1"></iframe></div><p>More content.</p>',
      ],
      'JSON keys in reverse order' => [
        '<p>Content.</p><p>{"settings_summary":["Embedded Video (854x480, autoplaying)."],"settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"video_url":"https://vimeo.com/18352872","preview_thumbnail":"http://example.com/thumbnail.jpg"}</p><p>More content.</p>',
        '<p>Content.</p><div class="video-embed-field-responsive-video"><iframe width="854" height="480" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/18352872?autoplay=1"></iframe></div><p>More content.</p>',
      ],
      'Relative thumbnail URL' => [
        '<p>Content.</p><p>{"settings_summary":["Embedded Video (854x480, autoplaying)."],"settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"video_url":"https://vimeo.com/18352872","preview_thumbnail":"/thumbnail.jpg"}</p><p>More content.</p>',
        '<p>Content.</p><div class="video-embed-field-responsive-video"><iframe width="854" height="480" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/18352872?autoplay=1"></iframe></div><p>More content.</p>',
      ],
      'No wrapping paragraphs tags' => [
        '{"settings_summary":["Embedded Video (854x480, autoplaying)."],"settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"video_url":"https://vimeo.com/18352872","preview_thumbnail":"/thumbnail.jpg"}',
        '<div class="video-embed-field-responsive-video"><iframe width="854" height="480" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/18352872?autoplay=1"></iframe></div>',
      ],
      'Invalid URL' => [
        '<p>Content.</p><p>{"preview_thumbnail":"http://example.com/thumbnail.jpg","video_url":"https://example.com/InvalidUrl","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Video (854x480, autoplaying)."]}</p><p>More content.</p>',
        FALSE,
      ],
      'Malformed JSON' => [
        '<p>Content.</p><p>{"preview_thumbnail":::"http://example.com/thumbnail.jpg","video_url":"https://www.youtube.com/watch?v=uNRtZDAS0xI","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Video (854x480, autoplaying)."]}</p><p>More content.</p>',
        FALSE,
      ],
      'Partial keys' => [
        '<p>Content.</p><p>{"preview_thumbnail":"http://example.com/thumbnail.jpg","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Video (854x480, autoplaying)."]}</p><p>More content.</p>',
        FALSE,
      ],
      'XSS Width/Height' => [
        '<p>Content.</p><p>{"preview_thumbnail":"http://example.com/thumbnail.jpg","video_url":"https://www.youtube.com/watch?v=uNRtZDAS0xI","settings":{"responsive":1,"width":"\">test","height":"\">test","autoplay":1},"settings_summary":["Embedded Video (854x480, autoplaying)."]}</p><p>More content.</p>',
        '<p>Content.</p><div class="video-embed-field-responsive-video"><iframe width="&quot;&gt;test" height="&quot;&gt;test" frameborder="0" allowfullscreen="allowfullscreen" src="https://www.youtube.com/embed/uNRtZDAS0xI?autoplay=1&amp;start=0&amp;rel=0"></iframe></div><p>More content.</p>',
      ],
      'Empty settings' => [
        '<p>Content.</p><p>{"preview_thumbnail":"http://example.com/thumbnail.jpg","video_url":"https://www.youtube.com/watch?v=uNRtZDAS0xI","settings":{},"settings_summary":["Embedded Video (854x480, autoplaying)."]}</p><p>More content.</p>',
        FALSE,
      ],
    ];
  }

  /**
   * Test the video ouput.
   *
   * @dataProvider videoFilterTestCases
   */
  public function testVideoFilter($content, $expected) {
    if ($expected === FALSE) {
      $expected = $content;
    }
    $filtered_markup = $this->stripWhitespace(check_markup($content, 'test_format'));
    $this->assertEquals($expected, $filtered_markup);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->filter = FilterFormat::create([
      'format' => 'test_format',
      'name' => $this->randomMachineName(),
    ]);
    $this->filter->setFilterConfig('video_embed_wysiwyg', ['status' => 1]);
    $this->filter->save();
  }

  /**
   * Remove HTML whitespace from a string.
   *
   * @param string $string
   *   The input string.
   *
   * @return string
   *   The whitespace cleaned string.
   */
  protected function stripWhitespace($string) {
    $no_whitespace = preg_replace('/\s{2,}/', '', $string);
    $no_whitespace = str_replace("\n", '', $no_whitespace);
    return $no_whitespace;
  }

}
