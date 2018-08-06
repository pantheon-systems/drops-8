<?php

namespace Drupal\Tests\video_embed_media\Kernel;

use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\Entity\MediaBundle;
use Drupal\Tests\video_embed_field\Kernel\KernelTestBase;
use Drupal\video_embed_media\Plugin\MediaEntity\Type\VideoEmbedField;

/**
 * Test the provided fields.
 *
 * @group video_embed_media
 */
class ProvidedFieldsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'video_embed_media',
    'media_entity',
    'file',
    'views',
  ];

  /**
   * The media video plugin manager.
   *
   * @var \Drupal\media_entity\MediaTypeManager
   */
  protected $mediaVideoPlugin;

  /**
   * Test cases for ::testProvidedFields().
   */
  public function providedFieldsTestCases() {
    return [
      'Video ID (YouTube)' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'id',
        'gnERPdAiuSo',
      ],
      'Video ID (Vimeo)' => [
        'https://vimeo.com/channels/staffpicks/153786080',
        'id',
        '153786080',
      ],
      'Video Source (YouTube)' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'source',
        'youtube',
      ],
      'Video Source (Vimeo)' => [
        'https://vimeo.com/channels/staffpicks/159700995',
        'source',
        'vimeo',
      ],
      'Video Thumbnail (YouTube)' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'image_local_uri',
        'public://video_thumbnails/gnERPdAiuSo.jpg',
      ],
      'Video Thumbnail (Vimeo)' => [
        'https://vimeo.com/channels/staffpicks/153786080',
        'image_local_uri',
        'public://video_thumbnails/153786080.jpg',
      ],
    ];
  }

  /**
   * Test the default thumbnail.
   */
  public function testDefaultThumbnail() {
    $this->assertEquals('public://media-icons/generic/video.png', $this->mediaVideoPlugin->getDefaultThumbnail());
  }

  /**
   * Test the fields provided by the integration.
   *
   * @dataProvider providedFieldsTestCases
   */
  public function testProvidedFields($input, $field, $expected) {
    $entity = Media::create([
      'bundle' => 'video',
      VideoEmbedField::VIDEO_EMBED_FIELD_DEFAULT_NAME => [['value' => $input]],
    ]);
    $actual = $this->mediaVideoPlugin->getField($entity, $field);
    $this->assertEquals($expected, $actual);
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    parent::setup();
    $this->installConfig(['media_entity']);
    $this->mediaVideoPlugin = $this->container->get('plugin.manager.media_entity.type')->createInstance('video_embed_field', []);
    $bundle = MediaBundle::create([
      'id' => 'video',
      'type' => 'video_embed_field',
    ]);
    $bundle->save();
  }

}
