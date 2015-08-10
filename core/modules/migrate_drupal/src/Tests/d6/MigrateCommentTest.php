<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\comment\Tests\CommentTestTrait;

/**
 * Upgrade comments.
 *
 * @group migrate_drupal
 */
class MigrateCommentTest extends MigrateDrupal6TestBase {

  use CommentTestTrait;

  static $modules = array('node', 'comment', 'text', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installConfig(['node', 'comment']);

    entity_create('node_type', array('type' => 'page'))->save();
    entity_create('node_type', array('type' => 'story'))->save();
    $this->addDefaultCommentField('node', 'story');
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment_no_subject',
      'label' => 'comment_no_subject',
      'target_entity_type_id' => 'node',
    ))->save();
    \Drupal::service('comment.manager')->addBodyField('comment_no_subject');

    $node = entity_create('node', array(
      'type' => 'story',
      'nid' => 1,
    ));
    $node->enforceIsNew();
    $node->save();
    $id_mappings = array(
      'd6_filter_format' => array(array(array(1), array('filtered_html'))),
      'd6_node' => array(array(array(1), array(1))),
      'd6_user' => array(array(array(0), array(0))),
      'd6_comment_type' => array(array(array('comment'), array('comment_no_subject'))),
      'd6_comment_entity_display' => array(array(array('story'), array('node', 'story', 'default', 'comment'))),
      'd6_comment_entity_form_display' => array(array(array('story'), array('node', 'story', 'default', 'comment'))),
    );
    $this->prepareMigrations($id_mappings);

    $this->loadDumps([
      'Node.php',
      'NodeRevisions.php',
      'ContentTypeStory.php',
      'ContentTypeTestPlanet.php',
      'Variable.php',
      'NodeType.php',
      'Comments.php',
    ]);
    $this->executeMigration('d6_comment');
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment migration.
   */
  public function testComments() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $comment_storage */
    $comment_storage = $this->container->get('entity.manager')->getStorage('comment');
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $comment_storage->load(1);
    $this->assertIdentical('The first comment.', $comment->getSubject());
    $this->assertIdentical('The first comment body.', $comment->comment_body->value);
    $this->assertIdentical('filtered_html', $comment->comment_body->format);
    $this->assertIdentical('0', $comment->pid->target_id);
    $this->assertIdentical('1', $comment->getCommentedEntityId());
    $this->assertIdentical('node', $comment->getCommentedEntityTypeId());
    $this->assertIdentical('en', $comment->language()->getId());
    $this->assertIdentical('comment_no_subject', $comment->getTypeId());

    $comment = $comment_storage->load(2);
    $this->assertIdentical('The response to the second comment.', $comment->subject->value);
    $this->assertIdentical('3', $comment->pid->target_id);

    $comment = $comment_storage->load(3);
    $this->assertIdentical('The second comment.', $comment->subject->value);
    $this->assertIdentical('0', $comment->pid->target_id);
  }
}
