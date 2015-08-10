<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentCacheTagsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Comment entity's cache tags.
 *
 * @group comment
 */
class CommentCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to view comments, so that we can verify
    // the cache tags of cached versions of comment pages.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $user_role->grantPermission('access comments');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "bar" bundle for the "entity_test" entity type and create.
    $bundle = 'bar';
    entity_test_create_bundle($bundle, NULL, 'entity_test');

    // Create a comment field on this bundle.
    $this->addDefaultCommentField('entity_test', 'bar', 'comment');

    // Display comments in a flat list; threaded comments are not render cached.
    $field = FieldConfig::loadByName('entity_test', 'bar', 'comment');
    $field->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT);
    $field->save();

    // Create a "Camelids" test entity.
    $entity_test = entity_create('entity_test', array(
      'name' => 'Camelids',
      'type' => 'bar',
    ));
    $entity_test->save();

    // Create a "Llama" comment.
    $comment = entity_create('comment', array(
      'subject' => 'Llama',
      'comment_body' => array(
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ),
      'entity_id' => $entity_test->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'status' => \Drupal\comment\CommentInterface::PUBLISHED,
    ));
    $comment->save();

    return $comment;
  }


  /**
   * {@inheritdoc}
   */
  protected function getAdditionalCacheContextsForEntity(EntityInterface $entity) {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Each comment must have a comment body, which always has a text format.
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $entity) {
    /** @var \Drupal\comment\CommentInterface $entity */
    return array(
      'config:filter.format.plain_text',
      'user:' . $entity->getOwnerId(),
      'user_view',
    );
  }

}
