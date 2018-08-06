<?php

namespace Drupal\Tests\diff\Kernel;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the diff controller.
 *
 * @group diff
 */
class DiffControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'diff',
    'entity_test',
    'diff_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['router', 'sequences']);
    $this->installSchema('user', 'users_data');
    \Drupal::service('router.builder')->rebuild();

    $this->installConfig('diff');
    $config = \Drupal::configFactory()->getEditable('diff.settings');
    $config->set('entity.entity_test_rev.name', TRUE);
    $config->save();
  }

  /**
   * Tests the Controller.
   */
  public function testController() {
    $entity = EntityTestRev::create([
      'name' => 'test entity 1',
      'type' => 'entity_test_rev',
    ]);
    $entity->save();
    $vid1 = $entity->getRevisionId();

    $entity->name->value = 'test entity 2';
    $entity->setNewRevision(TRUE);
    $entity->save();
    $vi2 = $entity->getRevisionId();

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = \Drupal::service('http_kernel');
    $request = Request::create(Url::fromRoute('entity.entity_test_rev.revisions_diff', [
      'node' => $entity->id(),
      'entity_test_rev' => $entity->id(),
      'left_revision' => $vid1,
      'right_revision' => $vi2,
    ])->toString(TRUE)->getGeneratedUrl());

    $response = $http_kernel->handle($request);
    $this->assertEquals(403, $response->getStatusCode());

    $role = Role::create([
      'id' => 'test_role',
    ]);
    $role->grantPermission('administer entity_test content');
    $role->save();
    $account = User::create([
      'name' => 'test user',
      'roles' => $role->id(),
    ]);
    $account->save();

    \Drupal::currentUser()->setAccount($account);
    $response = $http_kernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());

    $output = $response->getContent();
    $this->assertContains('<td class="diff-context diff-deletedline">test entity <span class="diffchange">1</span></td>', $output);
    $this->assertContains('<td class="diff-context diff-addedline">test entity <span class="diffchange">2</span></td>', $output);
  }

}
