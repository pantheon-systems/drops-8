<?php

namespace Drupal\Tests\metatag\Kernel\Migrate\d7;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of per-entity data from Metatag-D7.
 *
 * @group metatag
 */
class MetatagEntitiesTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Core modules.
    // @see testAvailableConfigEntities
    'comment',
    'content_translation',
    'datetime',
    'filter',
    'image',
    'language',
    'link',
    'menu_link_content',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * Prepare the file migration for running.
   *
   * Copied from FileMigrationSetupTrait from 8.4 so that this doesn't have to
   * then also extend getFileMigrationInfo().
   */
  protected function fileMigrationSetup() {
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->container->get('stream_wrapper_manager')
      ->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);

    $fs = \Drupal::service('file_system');
    // The public file directory active during the test will serve as the
    // root of the fictional Drupal 7 site we're migrating.
    $fs->mkdir('public://sites/default/files', NULL, TRUE);
    file_put_contents('public://sites/default/files/cube.jpeg', str_repeat('*', 3620));

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d7_file');
    // Set the source plugin's source_base_path configuration value, which
    // would normally be set by the user running the migration.
    $source = $migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs->realpath('public://');
    $migration->set('source', $source);
    $this->executeMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (version_compare(\Drupal::VERSION, '8.9', '<')) {
      $this->markTestSkipped('This test requires at least Drupal 8.9');
    }
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/d7_metatag.php');

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('metatag_defaults');

    $this->executeMigrations([
      'language',
      'd7_metatag_field',
      'd7_node_type',
      'd7_taxonomy_vocabulary',
      'd7_metatag_field_instance',
      'd7_metatag_field_instance_widget_settings',
      'd7_user_role',
      'd7_user',
      'd7_comment_type',
      'd7_field',
      'd7_field_instance',
      'd7_language_content_settings',
    ]);
    $this->fileMigrationSetup();
    $this->executeMigrations([
      'd7_node_complete',
      'd7_taxonomy_term',
    ]);
  }

  /**
   * Test Metatag migration from Drupal 7 to 8.
   */
  public function testMetatag() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::load(998);
    $this->assertTrue($node instanceof NodeInterface);
    $this->assertTrue($node->hasField('field_metatag'));
    // This should have the "current revision" keywords value, indicating it is
    // the current revision.
    $expected = [
      'keywords' => 'current revision',
      'canonical_url' => 'the-node',
      'robots' => 'noindex, nofollow',
    ];
    $this->assertSame(serialize($expected), $node->field_metatag->value);

    $node = node_revision_load(998);
    $this->assertTrue($node instanceof NodeInterface);
    $this->assertTrue($node->hasField('field_metatag'));
    // This should have the "old revision" keywords value, indicating it is
    // a non-current revision.
    $expected = [
      'keywords' => 'old revision',
      'canonical_url' => 'the-node',
      'robots' => 'noindex, nofollow',
    ];
    $this->assertSame(serialize($expected), $node->field_metatag->value);

    /** @var \Drupal\user\Entity\User $user */
    $user = User::load(2);
    $this->assertTrue($user instanceof UserInterface);
    $this->assertTrue($user->hasField('field_metatag'));
    $expected = [
      'keywords' => 'a user',
      'canonical_url' => 'the-user',
    ];
    $this->assertSame(serialize($expected), $user->field_metatag->value);

    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = Term::load(152);
    $this->assertTrue($term instanceof TermInterface);
    $this->assertTrue($term->hasField('field_metatag'));
    $expected = [
      'keywords' => 'a taxonomy',
      'canonical_url' => 'the-term',
    ];
    $this->assertSame(serialize($expected), $term->field_metatag->value);
  }

}
