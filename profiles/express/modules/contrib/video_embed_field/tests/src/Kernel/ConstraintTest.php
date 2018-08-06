<?php

namespace Drupal\Tests\video_embed_field\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\UserCreationTrait;
use Drupal\video_embed_field\Plugin\Validation\Constraint\VideoEmbedConstraint;

/**
 * Test for the video embed constraint.
 *
 * @group video_embed_field
 */
class ConstraintTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    $this->user = $this->createUser([]);
  }

  /**
   * Test the video embed constraint.
   */
  public function testConstraint() {
    $entity = EntityTest::create(['user_id' => $this->user->id()]);
    $entity->{$this->fieldName}->value = 'invalid URL';
    $violations = $entity->validate();

    $this->assertCount(1, $violations);
    $this->assertInstanceOf(VideoEmbedConstraint::class, $violations[0]->getConstraint());

    $entity->{$this->fieldName}->value = 'https://youtube.com/watch?v=fdbFV_Wup-Ssw';
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
  }

}
