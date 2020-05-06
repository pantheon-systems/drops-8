<?php

namespace Drupal\webform_options_limit\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Access\WebformEntityAccess;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform_options_limit\Plugin\WebformOptionsLimitHandlerInterface;
use Drupal\webform_node\Access\WebformNodeAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform options limit.
 */
class WebformOptionsLimitController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformSubmissionExportImportController object.
   *
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(WebformRequestInterface $request_handler) {
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.request')
    );
  }

  /**
   * Returns the Webform submission export example CSV view.
   */
  public function index() {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform']);

    $build = [];

    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof WebformOptionsLimitHandlerInterface) {
        $handler->setSourceEntity($source_entity);
        $build[$handler->getHandlerId()] = $handler->buildSummaryTable();
        $build[$handler->getHandlerId()]['#suffix'] = '<br/><br/>';
      }
    }

    return $build;
  }

  /**
   * Check whether the webform option limits summary.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAccess(WebformInterface $webform) {
    if (!static::hasOptionsLimit($webform)) {
      return AccessResult::forbidden()->addCacheableDependency($webform);
    }

    return WebformEntityAccess::checkResultsAccess($webform);
  }

  /**
   * Check whether the user can access a node's webform options limits summary.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkNodeAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');
    $webform = $entity_reference_manager->getWebform($node);

    // Check that the node has a valid webform reference.
    if (!$webform) {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }

    if (!static::hasOptionsLimit($webform)) {
      return AccessResult::forbidden()->addCacheableDependency($webform);
    }

    return WebformNodeAccess::checkWebformResultsAccess($operation, $entity_access, $node, $account);
  }

  /**
   * Determine if the webform has an options limit handler.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return bool
   *   TRUE if the webform has an options limit handler.
   */
  protected static function hasOptionsLimit(WebformInterface $webform) {
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof WebformOptionsLimitHandlerInterface) {
        $configuration = $handler->getConfiguration();
        if (empty($configuration['settings']['limit_user'])) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
