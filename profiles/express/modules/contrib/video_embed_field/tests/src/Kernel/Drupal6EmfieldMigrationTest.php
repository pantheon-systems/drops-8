<?php

namespace Drupal\Tests\video_embed_field\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Test the Drupal 6 emfield migration.
 *
 * @group video_embed_field
 */
class Drupal6EmfieldMigrationTest extends MigrateDrupal6TestBase {

  use EntityLoadTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'video_embed_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../fixtures/drupal6-emfield-2-x.php.gz';
  }

  /**
   * Test the emfield migration.
   */
  public function testEmfieldMigration() {
    $this->migrateContent();
    $migrated_vimeo = $this->loadEntityByLabel('Vimeo Example');
    $migrated_youtube = $this->loadEntityByLabel('YouTube Example');
    $this->assertEquals('https://vimeo.com/21681203', $migrated_vimeo->field_video->value);
    $this->assertEquals('https://www.youtube.com/watch?v=XgYu7-DQjDQ', $migrated_youtube->field_video->value);
  }

}
