<?php

namespace Drupal\Tests\media_entity\Kernel;

use Drupal\Core\Language\Language;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\Entity\MediaBundle;

/**
 * Tests token handling.
 *
 * @requires module token
 * @requires module entity
 *
 * @group media_entity
 */
class TokensTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'media_entity',
    'path',
    'file',
    'image',
    'entity',
    'datetime',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('media');
    $this->installConfig(['language', 'datetime', 'field', 'system']);
  }

  /**
   * Tests some of the tokens provided by media_entity.
   */
  public function testMediaEntityTokens() {
    // Create a generic media bundle.
    $bundle_name = $this->randomMachineName();

    MediaBundle::create([
      'id' => $bundle_name,
      'label' => $bundle_name,
      'type' => 'generic',
      'type_configuration' => [],
      'field_map' => [],
      'status' => 1,
      'new_revision' => FALSE,
    ])->save();

    // Create a media entity.
    $media = Media::create([
      'name' => $this->randomMachineName(),
      'bundle' => $bundle_name,
      'uid' => '1',
      'langcode' => Language::LANGCODE_DEFAULT,
      'status' => Media::PUBLISHED,
    ]);
    $media->save();

    $token_service = $this->container->get('token');

    // @TODO Extend this to cover also the other tokens, if necessary.
    $replaced_value = $token_service->replace('[media:name]', ['media' => $media]);
    $this->assertEquals($media->label(), $replaced_value, 'Token replacement for the media label was sucessful.');

  }

}
