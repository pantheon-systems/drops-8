<?php

namespace Drupal\Tests\entity\Unit;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\UncacheableEntityPermissionProvider;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;

/**
 * @coversDefaultClass \Drupal\entity\UncacheableEntityPermissionProvider
 * @group entity
 */
class UncacheableEntityPermissionProviderTest extends UnitTestCase {

  /**
   * The entity permission provider.
   *
   * @var \Drupal\entity\EntityPermissionProviderInterface
   */
  protected $permissionProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity_type_bundle_info = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $entity_type_bundle_info->getBundleInfo('white_entity')->willReturn([
      'first' => ['label' => 'First'],
      'second' => ['label' => 'Second'],
    ]);
    $entity_type_bundle_info->getBundleInfo('black_entity')->willReturn([
      'third' => ['label' => 'Third'],
    ]);
    $entity_type_bundle_info->getBundleInfo('pink_entity')->willReturn([
      'third' => ['label' => 'Third'],
    ]);
    $this->permissionProvider = new UncacheableEntityPermissionProvider($entity_type_bundle_info->reveal());
    $this->permissionProvider->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::buildPermissions
   *
   * @dataProvider entityTypeProvider
   */
  public function testBuildPermissions(EntityTypeInterface $entity_type, array $expected_permissions) {
    $permissions = $this->permissionProvider->buildPermissions($entity_type);
    $this->assertEquals(array_keys($expected_permissions), array_keys($permissions));
    foreach ($permissions as $name => $permission) {
      $this->assertEquals('entity_module_test', $permission['provider']);
      $this->assertEquals($expected_permissions[$name], $permission['title']);
    }
  }

  /**
   * Data provider for testBuildPermissions().
   *
   * @return array
   *   A list of testBuildPermissions method arguments.
   */
  public function entityTypeProvider() {
    $data = [];
    // Content entity type.
    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->getProvider()->willReturn('entity_module_test');
    $entity_type->id()->willReturn('green_entity');
    $entity_type->getSingularLabel()->willReturn('green entity');
    $entity_type->getPluralLabel()->willReturn('green entities');
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn(FALSE);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn(FALSE);
    $entity_type->getPermissionGranularity()->willReturn('entity_type');
    $expected_permissions = [
      'administer green_entity' => 'Administer green entities',
      'access green_entity overview' => 'Access the green entities overview page',
      'create green_entity' => 'Create green entities',
      'update green_entity' => 'Update green entities',
      'delete green_entity' => 'Delete green entities',
      'view any green_entity' => 'View any green entities',
    ];
    $data[] = [$entity_type->reveal(), $expected_permissions];

    // Content entity type with owner.
    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->getProvider()->willReturn('entity_module_test');
    $entity_type->id()->willReturn('blue_entity');
    $entity_type->getSingularLabel()->willReturn('blue entity');
    $entity_type->getPluralLabel()->willReturn('blue entities');
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn(TRUE);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn(FALSE);
    $entity_type->getPermissionGranularity()->willReturn('entity_type');
    $expected_permissions = [
      'administer blue_entity' => 'Administer blue entities',
      'access blue_entity overview' => 'Access the blue entities overview page',
      'create blue_entity' => 'Create blue entities',
      'update any blue_entity' => 'Update any blue entity',
      'update own blue_entity' => 'Update own blue entities',
      'delete any blue_entity' => 'Delete any blue entity',
      'delete own blue_entity' => 'Delete own blue entities',
      'view any blue_entity' => 'View any blue entities',
      'view own blue_entity' => 'View own blue entities',
    ];
    $data[] = [$entity_type->reveal(), $expected_permissions];

    // Content entity type with bundles.
    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->getProvider()->willReturn('entity_module_test');
    $entity_type->id()->willReturn('white_entity');
    $entity_type->getSingularLabel()->willReturn('white entity');
    $entity_type->getPluralLabel()->willReturn('white entities');
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn(FALSE);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn(FALSE);
    $entity_type->getPermissionGranularity()->willReturn('bundle');
    $expected_permissions = [
      'administer white_entity' => 'Administer white entities',
      'access white_entity overview' => 'Access the white entities overview page',
      'create first white_entity' => 'First: Create white entities',
      'update first white_entity' => 'First: Update white entities',
      'delete first white_entity' => 'First: Delete white entities',
      'create second white_entity' => 'Second: Create white entities',
      'update second white_entity' => 'Second: Update white entities',
      'delete second white_entity' => 'Second: Delete white entities',
      'view any white_entity' => 'View any white entities',
      'view any first white_entity' => 'First: View any white entities',
      'view any second white_entity' => 'Second: View any white entities',
    ];
    $data[] = [$entity_type->reveal(), $expected_permissions];

    // Content entity type with bundles and owner.
    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->getProvider()->willReturn('entity_module_test');
    $entity_type->id()->willReturn('black_entity');
    $entity_type->getSingularLabel()->willReturn('black entity');
    $entity_type->getPluralLabel()->willReturn('black entities');
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn(TRUE);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn(FALSE);
    $entity_type->getPermissionGranularity()->willReturn('bundle');
    $expected_permissions = [
      'administer black_entity' => 'Administer black entities',
      'access black_entity overview' => 'Access the black entities overview page',
      'create third black_entity' => 'Third: Create black entities',
      'update any third black_entity' => 'Third: Update any black entity',
      'update own third black_entity' => 'Third: Update own black entities',
      'delete any third black_entity' => 'Third: Delete any black entity',
      'delete own third black_entity' => 'Third: Delete own black entities',
      'view any black_entity' => 'View any black entities',
      'view own black_entity' => 'View own black entities',
      'view any third black_entity' => 'Third: View any black entities',
      'view own third black_entity' => 'Third: View own black entities',
    ];
    $data[] = [$entity_type->reveal(), $expected_permissions];

    // Content entity type with bundles and owner and entity published.
    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->getProvider()->willReturn('entity_module_test');
    $entity_type->id()->willReturn('pink_entity');
    $entity_type->getSingularLabel()->willReturn('pink entity');
    $entity_type->getPluralLabel()->willReturn('pink entities');
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn(TRUE);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn(TRUE);
    $entity_type->getPermissionGranularity()->willReturn('bundle');
    $expected_permissions = [
      'administer pink_entity' => 'Administer pink entities',
      'access pink_entity overview' => 'Access the pink entities overview page',
      'view own unpublished pink_entity' => 'View own unpublished pink entities',
      'create third pink_entity' => 'Third: Create pink entities',
      'update any third pink_entity' => 'Third: Update any pink entity',
      'update own third pink_entity' => 'Third: Update own pink entities',
      'delete any third pink_entity' => 'Third: Delete any pink entity',
      'delete own third pink_entity' => 'Third: Delete own pink entities',
      'view any pink_entity' => 'View any pink entities',
      'view own pink_entity' => 'View own pink entities',
      'view any third pink_entity' => 'Third: View any pink entities',
      'view own third pink_entity' => 'Third: View own pink entities',
    ];
    $data[] = [$entity_type->reveal(), $expected_permissions];

    return $data;
  }

}
