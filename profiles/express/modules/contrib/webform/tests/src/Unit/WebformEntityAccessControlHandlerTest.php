<?php

namespace Drupal\Tests\webform\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformAccessRulesManagerInterface;
use Drupal\webform\Plugin\WebformSourceEntityManagerInterface;
use Drupal\webform\WebformEntityAccessControlHandler;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests webform access handler.
 *
 * @coversDefaultClass \Drupal\webform\WebformEntityAccessControlHandler
 *
 * @group webform
 */
class WebformEntityAccessControlHandlerTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * The test container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    // Mock cache context manager and set container.
    // @copied from \Drupal\Tests\Core\Access\AccessResultTest::setUp
    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();

    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
  }

  /**
   * Tests the access logic.
   *
   * @param string $operation
   *   Operation to request from ::checkAccess() method.
   * @param array $options
   *   Array of extra options.
   * @param array $expected
   *   Expected data from the tested class.
   * @param string $assert_message
   *   Assertion message to use for this test case.
   *
   * @see WebformEntityAccessControlHandler::checkAccess()
   *
   * @dataProvider providerCheckAccess
   */
  public function testCheckAccess($operation, array $options, array $expected, $assert_message = '') {
    // Set $options default value.
    $options += [
      // What is the request path.
      'request_path' => '',
      // What is the request format.
      'request_format' => 'html',
      // Array of permissions to assign to a mocked account.
      'permissions' => [],
      // Array of access rules that should yield 'allowed' when the mocked
      // access rules manager is requested ::checkWebformAccess()
      // or ::checkWebformSubmissionAccess().
      'access_rules' => [],
      // Whether the mocked user should be owner of the webform.
      'account_is_webform_owner' => FALSE,
      // Whether the mocked webform should be a template.
      'webform_is_template' => FALSE,
      // Whether the mocked webform should be open.
      'webform_is_open' => TRUE,
      // Whether the mocked webform submission should successfully
      // load through token in query string. Defaults to FALSE.
      'submission_load_from_token' => FALSE,
    ];

    // Set $expected default value.
    $expected += [
      // Whether ::isAllowed() on the return should yield TRUE.
      'access_result_is_allowed' => TRUE,
      // Cache tags of the return.
      'access_result_cache_tags' => [],
      // Cache contexts of the return.
      'access_result_cache_contexts' => [],
    ];

    /**************************************************************************/

    $token = $this->randomMachineName();

    // Mock entity type.
    $entity_type = new ConfigEntityType(['id' => 'webform']);

    // Mock request stack.
    $request_stack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();
    $request_stack->method('getCurrentRequest')
      ->willReturn(new Request(['token' => $token], [], ['_format' => $options['request_format']]));

    // Mock webform submission storage.
    $webform_submission_storage = $this->getMockBuilder(WebformSubmissionStorageInterface::class)
      ->getMock();

    // Mock entity type manager.
    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->getMock();
    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['webform_submission', $webform_submission_storage],
      ]);

    // Mock webform source entity manager.
    $webform_source_entity_manager = $this->getMockBuilder(WebformSourceEntityManagerInterface::class)
      ->getMock();
    $webform_source_entity_manager->method('getSourceEntity')
      ->willReturn(NULL);

    // Mock account.
    $permissions = $options['permissions'];
    $account_id = 2;
    $account = $this->getMockBuilder(AccountInterface::class)
      ->getMock();
    $account->method('hasPermission')
      ->willReturnCallback(function ($permission) use ($permissions) {
        return in_array($permission, $permissions);
      });
    $account->method('id')
      ->willReturn($options['account_is_webform_owner'] ? $account_id : $account_id + 1);
    $account->method('isAuthenticated')
      ->willReturn($account->id() > 0);

    // Mock webform.
    $webform = $this->getMockBuilder(WebformInterface::class)
      ->getMock();
    $webform->method('getOwnerId')
      ->willReturn($account_id);
    $webform->method('isTemplate')
      ->willReturn($options['webform_is_template']);
    $webform->method('isOpen')
      ->willReturn($options['webform_is_open']);
    $webform->method('access')
      ->willReturnMap([
        ['create', $account, TRUE, AccessResult::allowed()],
      ]);
    $webform->method('getSetting')->willReturnMap([
      ['page', FALSE, TRUE],
    ]);
    $webform->method('getCacheMaxAge')
      ->willReturn(Cache::PERMANENT);
    $webform->method('getCacheContexts')
      ->willReturn(['webform_cache_context']);
    $webform->method('getCacheTags')
      ->willReturn(['webform_cache_tag']);

    // Mock webform submissions.
    $webform_submission = $this->getMockBuilder(WebformSubmissionInterface::class)
      ->getMock();
    $webform_submission->method('getCacheContexts')
      ->willReturn(['webform_submission_cache_context']);
    $webform_submission->method('getCacheTags')
      ->willReturn(['webform_submission_cache_tag']);
    $webform_submission->method('getCacheMaxAge')
      ->willReturn(Cache::PERMANENT);
    $webform_submission->method('getWebform')
      ->willReturn($webform);
    $webform_submission_storage->method('loadFromToken')
      ->willReturnMap([
        [$token, $webform, NULL, NULL, ($options['submission_load_from_token'] ? $webform_submission : NULL)],
      ]);

    // Mock access rules manager.
    $access_rules_manager = $this->getMockBuilder(WebformAccessRulesManagerInterface::class)
      ->getMock();
    $access_rules_manager->method('checkWebformAccess')
      ->will(
        $this->returnCallback(
          function ($operation, AccountInterface $account, WebformInterface $webform) use ($options) {
            $condition = in_array($operation, $options['access_rules']) || in_array($operation . '_any', $options['access_rules']);
            return AccessResult::allowedIf($condition)
              ->addCacheContexts(['access_rules_cache_context'])
              ->addCacheTags(['access_rules_cache_tag']);
          }
        )
      );

    /**************************************************************************/

    // Create webform access control handler.
    $access_handler = new WebformEntityAccessControlHandler($entity_type, $request_stack, $entity_type_manager, $webform_source_entity_manager, $access_rules_manager);

    // Check access.
    $access_result = $access_handler->checkAccess($webform, $operation, $account);

    // Check expected results.
    $this->assertEquals($expected['access_result_is_allowed'], $access_result->isAllowed(), $assert_message);
    $this->assertEquals(Cache::PERMANENT, $access_result->getCacheMaxAge(), $assert_message . ': cache max age');
    $this->assertArrayEquals($expected['access_result_cache_contexts'], $access_result->getCacheContexts(), $assert_message . ': cache contexts');
    $this->assertArrayEquals($expected['access_result_cache_tags'], $access_result->getCacheTags(), $assert_message . ': cache tags');
  }

  /**
   * Data provider for testCheckAccess().
   *
   * @see testCheckAccess()
   */
  public function providerCheckAccess() {
    $tests = [];

    /**************************************************************************/
    // The "view" HTML operation.
    /**************************************************************************/

    $tests[] = [
      'view',
      [],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context', 'request_format', 'url.path'],
      ],
      'View when nobody',
    ];

    $tests[] = [
      'view',
      [
        'permissions' => ['administer webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => [],
        'access_result_cache_contexts' => ['user.permissions'],
      ],
      'View when has "administer webform" permission',
    ];

    $tests[] = [
      'view',
      [
        'access_rules' => ['administer'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'View when has "administer" access rule',
    ];

    /**************************************************************************/
    // The "view" configuration operation.
    /**************************************************************************/

    $tests[] = [
      'view',
      [
        'permissions' => ['access any webform configuration'],
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context', 'request_format', 'url.path'],
      ],
      'View when has "access any webform configuration" permission and request form is HTML',
    ];

    $tests[] = [
      'view',
      [
        'request_format' => 'not_html',
        'permissions' => ['access any webform configuration'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['request_format', 'url.path', 'user', 'user.permissions', 'webform_cache_context'],
      ],
      'View when has "access any webform configuration" permission and request form is NOT HTML',
    ];

    $tests[] = [
      'view',
      [
        'permissions' => ['access own webform configuration'],
        'account_is_webform_owner' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context', 'request_format', 'url.path'],
      ],
      'View when has "access own webform configuration" permission and is not owner and request form is HTML',
    ];

    $tests[] = [
      'view',
      [
        'request_format' => 'not_html',
        'permissions' => ['access own webform configuration'],
        'account_is_webform_owner' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context', 'request_format', 'url.path'],
      ],
      'View when has "access own webform configuration" permission and is not owner and request form is NOT HTML',
    ];

    $tests[] = [
      'view',
      [
        'permissions' => ['access own webform configuration'],
        'account_is_webform_owner' => TRUE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context', 'request_format', 'url.path'],
      ],
      'View when has "access own webform configuration" permission and is owner and request form is HTML',
    ];

    $tests[] = [
      'view',
      [
        'request_format' => 'not_html',
        'permissions' => ['access own webform configuration'],
        'account_is_webform_owner' => TRUE,
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['request_format', 'url.path', 'user', 'user.permissions', 'webform_cache_context'],
      ],
      'View when has "access own webform configuration" permission and is owner and request form is NOT HTML',
    ];

    /**************************************************************************/
    // The "test" operation.
    /**************************************************************************/

    $tests[] = [
      'test',
      [],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Test when nobody',
    ];

    $tests[] = [
      'test',
      [
        'permissions' => ['administer webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => [],
        'access_result_cache_contexts' => ['user.permissions'],
      ],
      'Test when has "administer webform" permission',
    ];

    $tests[] = [
      'test',
      [
        'access_rules' => ['administer'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'Test when has "administer" access rule',
    ];

    $tests[] = [
      'test',
      [
        'permissions' => ['edit any webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Test when has "edit any webform" permission',
    ];

    $tests[] = [
      'test',
      [
        'permissions' => ['edit own webform'],
        'account_is_webform_owner' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Test when has "edit own webform" permission and is not owner',
    ];

    $tests[] = [
      'test',
      [
        'permissions' => ['edit own webform'],
        'account_is_webform_owner' => TRUE,
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Test when has "edit own webform" permission and is owner',
    ];

    /**************************************************************************/
    // The "update" operation.
    /**************************************************************************/

    $tests[] = [
      'update',
      [],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Update when nobody',
    ];

    $tests[] = [
      'update',
      [
        'permissions' => ['administer webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => [],
        'access_result_cache_contexts' => ['user.permissions'],
      ],
      'Update when has "administer webform" permission',
    ];

    $tests[] = [
      'update', [
        'access_rules' => ['administer'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'Update when has "administer" access rule',
    ];

    $tests[] = [
      'update',
      [
        'permissions' => ['edit any webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Update when has "edit any webform" permission',
    ];

    $tests[] = [
      'update',
      [
        'permission' => ['edit own webform'],
        'account_is_webform_owner' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Update when has "edit own webform" permission and is not owner',
    ];

    $tests[] = [
      'update', [
        'permissions' => ['edit own webform'],
        'account_is_webform_owner' => TRUE,
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Update when has "edit own webform" permission and is owner',
    ];

    /**************************************************************************/
    // The "duplicate" operation.
    /**************************************************************************/

    $tests[] = [
      'duplicate',
      [],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Duplicate when nobody',
    ];

    $tests[] = [
      'duplicate',
      [
        'permissions' => ['administer webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => [],
        'access_result_cache_contexts' => ['user.permissions'],
      ],
      'Duplicate when has "administer webform" permission',
    ];

    $tests[] = [
      'duplicate',
      [
        'access_rules' => ['administer'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'Duplicate when has "administer" access rule',
    ];

    $tests[] = [
      'duplicate',
      [
        'permissions' => ['create webform'],
        'webform_is_template' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Duplicate when has "create webform" permission and webform is not template',
    ];

    $tests[] = [
      'duplicate',
      [
        'permissions' => ['create webform', 'edit any webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Duplicate when has "create webform" and "edit any webform" permissions',
    ];

    $tests[] = [
      'duplicate', [
        'permisssions' => ['create webform', 'edit own webform'],
        'account_is_webform_owner' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Duplicate when has "create webform" and "edit own webform" permissions and is not owner',
    ];

    $tests[] = [
      'duplicate',
      [
        'permissions' => ['create webform', 'edit own webform'],
        'account_is_webform_owner' => TRUE,
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Duplicate when has "create webform" and "edit own webform" permissions and is owner',
    ];

    /**************************************************************************/
    // The "delete" operation.
    /**************************************************************************/

    $tests[] = [
      'delete',
      [],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Delete when nobody',
    ];

    $tests[] = [
      'delete',
      [
        'permissions' => ['administer webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => [],
        'access_result_cache_contexts' => ['user.permissions'],
      ],
      'Delete when has "administer webform" permission',
    ];

    $tests[] = [
      'delete',
      [
        'access_rules' => ['administer'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'Delete when has "administer" access rule',
    ];

    $tests[] = [
      'delete',
      [
        'permissions' => ['delete any webform'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Delete when has "delete any webform" permission',
    ];

    $tests[] = [
      'delete',
      [
        'permissions' => ['delete own webform'],
        'account_is_webform_owner' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Delete when has "delete own webform" permission and is not owner',
    ];

    $tests[] = [
      'delete',
      [
        'permissions' => ['delete own webform'],
        'account_is_webform_owner' => TRUE,
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user', 'user.permissions', 'webform_cache_context'],
      ],
      'Delete when has "delete own webform" permission and is owner',
    ];

    /**************************************************************************/
    // The "purge" operation.
    /**************************************************************************/

    $tests[] = [
      'submission_purge',
      [
        'access_rules' => ['purge_any'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'Purge when has "purge_any" access rule',
    ];

    $tests[] = [
      'submission_purge_any',
      [
        'access_rules' => ['purge_any'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'Purge when has "purge_any" access rule',
    ];

    /**************************************************************************/
    // The "view" operation.
    /**************************************************************************/

    $tests[] = [
      'submission_view_any',
      [],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Submission view any when nobody',
    ];

    $tests[] = [
      'submission_view_any',
      [
        'permissions' => ['view any webform submission'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_contexts' => ['user.permissions'],
      ],
      'Submission view any when has "view any webform submission" permission',
    ];

    $tests[] = [
      'submission_view_any',
      [
        'permissions' => ['view own webform submission'],
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Submission view any when has "view own webform submission" permission but is not owner',
    ];

    $tests[] = [
      'submission_view_own',
      [],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Submission view own when nobody',
    ];

    $tests[] = [
      'submission_view_own',
      [
        'permissions' => ['view own webform submission'],
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_contexts' => ['user.permissions'],
      ],
      'Submission view own when has "view own webform submission" permission',
    ];

    // The "submission_page" operation.
    $tests[] = [
      'submission_page',
      [
        'webform_is_open' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Submission page when nobody',
    ];

    $tests[] = [
      'submission_page',
      [
        'submission_load_from_token' => TRUE,
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['webform_cache_tag', 'webform_submission_cache_tag'],
        'access_result_cache_contexts' => ['url', 'user.permissions', 'webform_cache_context', 'webform_submission_cache_context'],
      ],
      'Submission page when accessible through token',
    ];

    $tests[] = [
      'submission_page',
      [
        'webform_is_template' => TRUE,
        'webform_is_open' => FALSE,
      ],
      [
        'access_result_is_allowed' => FALSE,
        'access_result_cache_tags' => ['webform_cache_tag'],
        'access_result_cache_contexts' => ['user.permissions', 'webform_cache_context'],
      ],
      'Submission page when the webform is template without create access',
    ];

    $tests[] = [
      'submission_page',
      [
        'access_rules' => ['create'],
        'webform_is_open' => FALSE,
      ],
      [
        'access_result_is_allowed' => TRUE,
        'access_result_cache_tags' => ['access_rules_cache_tag'],
        'access_result_cache_contexts' => ['access_rules_cache_context'],
      ],
      'Submission page when the webform allows "page"',
    ];

    return $tests;
  }

}
