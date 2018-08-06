<?php

namespace Drupal\webform_node\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserDataInterface;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Defines a controller for webform node entity references.
 */
class WebformNodeEntityReferenceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform entity reference manager
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * Constructs a WebformNodeEntityReferenceController object.
   *
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   */
  public function __construct(WebformEntityReferenceManagerInterface $webform_entity_reference_manager) {
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.entity_reference_manager')
    );
  }

  /**
   * Set the current webform for a node with with multiple webform attached.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function change(Request $request, NodeInterface $node, WebformInterface $webform) {
    $this->webformEntityReferenceManager->setUserWebformId($node, $webform->id());
    return new RedirectResponse($request->query->get('destination') ?: $node->toUrl()->setAbsolute()->toString());
  }

}
