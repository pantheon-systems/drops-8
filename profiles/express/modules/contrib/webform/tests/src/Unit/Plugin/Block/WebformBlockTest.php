<?php

namespace Drupal\Tests\webform\Unit\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Plugin\Block\WebformBlock;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests webform submission bulk form actions.
 *
 * @coversDefaultClass \Drupal\webform\Plugin\Block\WebformBlock
 *
 * @group webform
 */
class WebformBlockTest extends UnitTestCase {

  /**
   * Tests the dependencies of a webform block.
   */
  public function testCalculateDependencies() {
    // Create mock webform and webform block.
    $webform = $this->getMockBuilder(WebformInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $webform->method('id')
      ->willReturn($this->randomMachineName());
    $webform->method('getConfigDependencyKey')
      ->willReturn('config');
    $webform->method('getConfigDependencyName')
      ->willReturn('config.webform.' . $webform->id());
    $block = $this->mockWebformBlock($webform);

    $dependencies = $block->calculateDependencies();
    $expected = [
      $webform->getConfigDependencyKey() => [$webform->getConfigDependencyName()],
    ];
    $this->assertEquals($expected, $dependencies, 'WebformBlock reports proper dependencies.');
  }

  /**
   * Tests the access of a webform block.
   */
  public function testBlockAccess() {
    $account = $this->getMockBuilder(AccountInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $cache_contexts = ['dummy_cache_context'];

    $cache_contexts_manager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')
      ->willReturnMap([
        [$cache_contexts, TRUE],
      ]);

    $container = $this->getMockBuilder(ContainerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->method('get')
      ->willReturnMap([
        ['cache_contexts_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $cache_contexts_manager],
      ]);

    \Drupal::setContainer($container);

    $access_result = AccessResult::allowed();
    $access_result->setCacheMaxAge(1);
    $access_result->addCacheTags(['dummy_cache_tag']);
    $access_result->addCacheContexts($cache_contexts);

    // Create mock webform and webform block.
    $webform = $this->getMockBuilder(WebformInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $webform->method('id')
      ->willReturn($this->randomMachineName());
    $webform->method('access')
      ->willReturnMap([
        ['submission_create', $account, TRUE, $access_result],
      ]);
    $block = $this->mockWebformBlock($webform);

    $result = $block->access($account, TRUE);

    // Make sure the block transparently follows the webform access logic.
    $this->assertSame($access_result->isAllowed(), $result->isAllowed(), 'Block access yields the same result as the access of the webform.');
    $this->assertEquals($access_result->getCacheContexts(), $result->getCacheContexts(), 'Block access has the same cache contexts as the access of the webform.');
    $this->assertEquals($access_result->getCacheTags(), $result->getCacheTags(), 'Block access has the same cache tags as the access of the webform.');
    $this->assertEquals($access_result->getCacheMaxAge(), $result->getCacheMaxAge(), 'Block access has the same cache max age as the access of the webform.');
  }

  /**
   * Create a mock webform block.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Drupal\webform\Plugin\Block\WebformBlock
   *   A mock webform block.
   */
  protected function mockWebformBlock(WebformInterface $webform) {
    $request_stack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $storage->method('load')
      ->willReturnMap([
        [$webform->id(), $webform],
      ]);

    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['webform', $storage],
      ]);

    $token_manager = $this->getMockBuilder(WebformTokenManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $configuration = ['webform_id' => $webform->id()];

    $plugin_id = 'webform_block';

    $plugin_definition = ['provider' => 'unit_test'];

    return new WebformBlock($configuration, $plugin_id, $plugin_definition, $request_stack, $entity_type_manager, $token_manager);
  }

}
