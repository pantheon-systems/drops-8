<?php

namespace Drupal\Tests\metatag\Kernel\Migrate\d6;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of per-entity data from Nodewords-D6.
 *
 * @group metatag
 */
class NodewordsEntitiesTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Core modules.
    // @see testAvailableConfigEntities
    'comment',
    'datetime',
    'filter',
    'image',
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
    // root of the fictional Drupal 6 site we're migrating.
    $fs->mkdir('public://sites/default/files', NULL, TRUE);
    file_put_contents('public://sites/default/files/cube.jpeg', str_repeat('*', 3620));

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d6_file');
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
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/d6_nodewords.php');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig(static::$modules);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('metatag_defaults');

    $this->executeMigrations([
      'd6_nodewords_field',
      'd6_node_type',
      'd6_taxonomy_vocabulary',
      'd6_nodewords_field',
      'd6_nodewords_field_instance',
      'd6_filter_format',
      'd6_user_role',
      'd6_user',
      'd6_comment_type',
      'd6_field',
      'd6_field_instance',
    ]);
    $this->fileMigrationSetup();
    $this->executeMigrations([
      'd6_node_settings',
      'd6_node:story',
      'd6_node:article',
      'd6_node:forum',
      'd6_node:employee',
      'd6_node:company',
      'd6_taxonomy_term',
    ]);
  }

  /**
   * Test Nodewords migration from Drupal 6 to Metatag in 8.
   */
  public function testMetatag() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::load(23);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertTrue($node->hasField('field_metatag'));
    // This should have the "current revision" keywords value, indicating it is
    // the current revision.
    $expected = [
      'abstract' => 'Test abstract',
      'canonical_url' => 'this/url',
      'description' => 'Test description',
      'keywords' => 'Keyword 1, keyword 2',
      'robots' => 'nofollow, nosnippet',
      'title' => 'Test title',
    ];
    $this->assertSame(serialize($expected), $node->field_metatag->value);

    $node = node_revision_load(2004);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertTrue($node->hasField('field_metatag'));
    // This should have the "old revision" keywords value, indicating it is
    // a non-current revision.
    $expected = [
      'abstract' => 'Test abstract',
      'canonical_url' => 'this/url',
      'description' => 'Test description',
      'keywords' => 'Keyword 1, keyword 2',
      'robots' => 'nofollow, nosnippet',
      'title' => 'Test title',
    ];
    $this->assertSame(serialize($expected), $node->field_metatag->value);

    /** @var \Drupal\user\Entity\User $user */
    $user = User::load(2);
    $this->assertInstanceOf(UserInterface::class, $user);
    $this->assertTrue($user->hasField('field_metatag'));
    $expected = [
      'revisit_after' => '1',
      'robots' => '',
    ];
    $this->assertSame(serialize($expected), $user->field_metatag->value);

    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = Term::load(16);
    $this->assertInstanceOf(TermInterface::class, $term);
    $this->assertTrue($term->hasField('field_metatag'));
    $expected = [
      'canonical_url' => 'the-term',
      'keywords' => 'a taxonomy, term',
    ];
    $this->assertSame(serialize($expected), $term->field_metatag->value);
  }

}
