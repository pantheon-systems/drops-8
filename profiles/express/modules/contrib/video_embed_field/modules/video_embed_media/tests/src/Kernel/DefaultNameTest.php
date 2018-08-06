<?php

namespace Drupal\Tests\video_embed_media\Kernel;

use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\Entity\MediaBundle;
use Drupal\Tests\video_embed_field\Kernel\KernelTestBase;
use Drupal\video_embed_media\Plugin\MediaEntity\Type\VideoEmbedField;

/**
 * Test the media bundle default names.
 *
 * @group video_embed_media
 */
class DefaultNameTest extends KernelTestBase {

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
   * Test cases for ::testDefaultName().
   */
  public function defaultNameTestCases() {
    return [
      'YouTube' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'YouTube Video (gnERPdAiuSo)',
      ],
      'Vimeo' => [
        'https://vimeo.com/21681203',
        'Drupal Commerce at DrupalCon Chicago',
      ],
    ];
  }

  /**
   * Test the default name.
   *
   * @dataProvider defaultNameTestCases
   */
  public function testDefaultName($input, $expected) {
    $entity = Media::create([
      'bundle' => 'video',
      VideoEmbedField::VIDEO_EMBED_FIELD_DEFAULT_NAME => [['value' => $input]],
    ]);
    $actual = $this->mediaVideoPlugin->getDefaultName($entity);
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
