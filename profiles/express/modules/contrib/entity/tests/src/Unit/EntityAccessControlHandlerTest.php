<?php

namespace Drupal\Tests\entity\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandler;
use Drupal\entity\EntityPermissionProvider;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\entity\EntityAccessControlHandler
 * @group entity
 */
class EntityAccessControlHandlerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->invokeAll(Argument::any(), Argument::any())->willReturn([]);
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('module_handler', $module_handler->reveal());
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::checkAccess
   * @covers ::checkEntityPermissions
   * @covers ::checkEntityOwnerPermissions
   * @covers ::checkCreateAccess
   *
   * @dataProvider accessProvider
   */
  public function testAccess(EntityInterface $entity, $operation, $account, $allowed) {
    $handler = new EntityAccessControlHandler($entity->getEntityType());
    $handler->setStringTranslation($this->getStringTranslationStub());
    $result = $handler->access($entity, $operation, $account);
    $this->assertEquals($allowed, $result);
  }

  /**
   * @covers ::checkCreateAccess
   *
   * @dataProvider createAccessProvider
   */
  public function testCreateAccess(EntityTypeInterface $entity_type, $bundle, $account, $allowed) {
    $handler = new EntityAccessControlHandler($entity_type);
    $handler->setStringTranslation($this->getStringTranslationStub());
    $result = $handler->createAccess($bundle, $account);
    $this->assertEquals($allowed, $result);
  }

  /**
   * Data provider for testAccess().
   *
   * @return array
   *   A list of testAccess method arguments.
   */
  public function accessProvider() {
    $data = [];

    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->id()->willReturn('green_entity');
    $entity_type->getAdminPermission()->willReturn('administer green_entity');
    $entity_type->hasHandlerClass('permission_provider')->willReturn(TRUE);
    $entity_type->getHandlerClass('permission_provider')->willReturn(EntityPermissionProvider::class);

    // User with the admin permission can do anything.
    $entity = $this->buildMockEntity($entity_type->reveal());
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(6);
    $account->hasPermission('administer green_entity')->willReturn(TRUE);
    $data[] = [$entity->reveal(), 'view', $account->reveal(), TRUE];
    $data[] = [$entity->reveal(), 'update', $account->reveal(), TRUE];
    $data[] = [$entity->reveal(), 'delete', $account->reveal(), TRUE];

    // Entity with no owner.
    $entity = $this->buildMockEntity($entity_type->reveal());
    // User who has access.
    $first_account = $this->prophesize(AccountInterface::class);
    $first_account->id()->willReturn(6);
    $first_account->hasPermission('view green_entity')->willReturn(TRUE);
    $first_account->hasPermission(Argument::any())->willReturn(FALSE);
    // User who doesn't have access.
    $second_account = $this->prophesize(AccountInterface::class);
    $second_account->id()->willReturn(7);
    $second_account->hasPermission('view green_entity')->willReturn(FALSE);
    $second_account->hasPermission(Argument::any())->willReturn(FALSE);
    $data[] = [$entity->reveal(), 'view', $first_account->reveal(), TRUE];
    $data[] = [$entity->reveal(), 'view', $second_account->reveal(), FALSE];

    // Entity with owner.
    $entity = $this->buildMockEntity($entity_type->reveal(), 6);
    // Owner.
    $first_account = $this->prophesize(AccountInterface::class);
    $first_account->id()->willReturn(6);
    $first_account->hasPermission('update own green_entity')->willReturn(TRUE);
    $first_account->hasPermission(Argument::any())->willReturn(FALSE);
    // Non-owner.
    $second_account = $this->prophesize(AccountInterface::class);
    $second_account->id()->willReturn(7);
    $second_account->hasPermission('update own green_entity')->willReturn(TRUE);
    $second_account->hasPermission(Argument::any())->willReturn(FALSE);
    // User who can update any.
    $third_account = $this->prophesize(AccountInterface::class);
    $third_account->id()->willReturn(8);
    $third_account->hasPermission('update any green_entity')->willReturn(TRUE);
    $third_account->hasPermission(Argument::any())->willReturn(FALSE);
    $data[] = [$entity->reveal(), 'update', $first_account->reveal(), TRUE];
    $data[] = [$entity->reveal(), 'update', $second_account->reveal(), FALSE];
    $data[] = [$entity->reveal(), 'update', $third_account->reveal(), TRUE];

    // Test the unpublished permissions.
    $entity_first_other_up = $this->buildMockEntity($entity_type->reveal(), 9999, 'first', FALSE);
    $entity_first_own_up = $this->buildMockEntity($entity_type->reveal(), 14, 'first', FALSE);
    $entity_first_own_bundle_up = $this->buildMockEntity($entity_type->reveal(), 15, 'first', FALSE);

    $entity_second_other_up = $this->buildMockEntity($entity_type->reveal(), 9999, 'second', FALSE);
    $entity_second_own_up = $this->buildMockEntity($entity_type->reveal(), 14, 'second', FALSE);
    $entity_second_own_bundle_up = $this->buildMockEntity($entity_type->reveal(), 15, 'second', FALSE);

    $user_view_own_up = $this->buildMockUser(14, 'view own unpublished green_entity');
    $user_view_other = $this->buildMockUser(15, 'view green_entity');

    $data['entity_first_other_up user_view_own_up'] = [$entity_first_other_up->reveal(), 'view', $user_view_own_up->reveal(), FALSE];
    $data['entity_first_own_up user_view_own_up'] = [$entity_first_own_up->reveal(), 'view', $user_view_own_up->reveal(), TRUE];
    $data['entity_first_own_bundle_up user_view_own_up'] = [$entity_first_own_bundle_up->reveal(), 'view', $user_view_own_up->reveal(), FALSE];
    $data['entity_second_other_up user_view_own_up'] = [$entity_second_other_up->reveal(), 'view', $user_view_own_up->reveal(), FALSE];
    $data['entity_second_own_up user_view_own_up'] = [$entity_second_own_up->reveal(), 'view', $user_view_own_up->reveal(), TRUE];
    $data['entity_second_own_bundle_up user_view_own_up'] = [$entity_second_own_bundle_up->reveal(), 'view', $user_view_own_up->reveal(), FALSE];

    $data['entity_first_other_up user_view_other'] = [$entity_first_other_up->reveal(), 'view', $user_view_other->reveal(), FALSE];
    $data['entity_first_own_up user_view_other'] = [$entity_first_own_up->reveal(), 'view', $user_view_other->reveal(), FALSE];
    $data['entity_first_own_bundle_up user_view_other'] = [$entity_first_own_bundle_up->reveal(), 'view', $user_view_other->reveal(), FALSE];
    $data['entity_second_other_up user_view_other'] = [$entity_second_other_up->reveal(), 'view', $user_view_other->reveal(), FALSE];
    $data['entity_second_own_up user_view_other'] = [$entity_second_own_up->reveal(), 'view', $user_view_other->reveal(), FALSE];
    $data['entity_second_own_bundle_up user_view_other'] = [$entity_second_own_bundle_up->reveal(), 'view', $user_view_other->reveal(), FALSE];

    return $data;
  }

  /**
   * Data provider for testCreateAccess().
   *
   * @return array
   *   A list of testCreateAccess method arguments.
   */
  public function createAccessProvider() {
    $data = [];

    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->id()->willReturn('green_entity');
    $entity_type->getAdminPermission()->willReturn('administer green_entity');
    $entity_type->hasHandlerClass('permission_provider')->willReturn(TRUE);
    $entity_type->getHandlerClass('permission_provider')->willReturn(EntityPermissionProvider::class);

    // User with the admin permission.
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(6);
    $account->hasPermission('administer green_entity')->willReturn(TRUE);
    $data[] = [$entity_type->reveal(), NULL, $account->reveal(), TRUE];

    // Ordinary user.
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(6);
    $account->hasPermission('create green_entity')->willReturn(TRUE);
    $account->hasPermission(Argument::any())->willReturn(FALSE);
    $data[] = [$entity_type->reveal(), NULL, $account->reveal(), TRUE];

    // Ordinary user, entity with a bundle.
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(6);
    $account->hasPermission('create first_bundle green_entity')->willReturn(TRUE);
    $account->hasPermission(Argument::any())->willReturn(FALSE);
    $data[] = [$entity_type->reveal(), 'first_bundle', $account->reveal(), TRUE];

    // User with no permissions.
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(6);
    $account->hasPermission(Argument::any())->willReturn(FALSE);
    $data[] = [$entity_type->reveal(), NULL, $account->reveal(), FALSE];

    return $data;
  }

  /**
   * Builds a mock entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $owner_id
   *   The owner ID.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The entity mock.
   */
  protected function buildMockEntity(EntityTypeInterface $entity_type, $owner_id = NULL, $bundle = NULL, $published = NULL) {
    $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $entity = $this->prophesize(ContentEntityInterface::class);
    if (isset($published)) {
      $entity->willImplement(EntityPublishedInterface::class);
    }
    if ($owner_id) {
      $entity->willImplement(EntityOwnerInterface::class);
    }
    if (isset($published)) {
      $entity->isPublished()->willReturn($published);
    }
    if ($owner_id) {
      $entity->getOwnerId()->willReturn($owner_id);
    }

    $entity->bundle()->willReturn($bundle ?: $entity_type->id());
    $entity->isNew()->willReturn(FALSE);
    $entity->uuid()->willReturn('fake uuid');
    $entity->id()->willReturn('fake id');
    $entity->getRevisionId()->willReturn(NULL);
    $entity->language()->willReturn(new Language(['id' => $langcode]));
    $entity->getEntityTypeId()->willReturn($entity_type->id());
    $entity->getEntityType()->willReturn($entity_type);
    $entity->getCacheContexts()->willReturn([]);
    $entity->getCacheTags()->willReturn([]);
    $entity->getCacheMaxAge()->willReturn(Cache::PERMANENT);


    return $entity;
  }

  protected function buildMockUser($uid, $permission) {
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($uid);
    $account->hasPermission($permission)->willReturn(TRUE);
    $account->hasPermission(Argument::any())->willReturn(FALSE);
    return $account;
  }

}
