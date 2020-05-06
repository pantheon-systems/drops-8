<?php

namespace Drupal\webform_access\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_access_group_entity' block.
 *
 * @Block(
 *   id = "webform_access_group_entity",
 *   admin_label = @Translation("Webform access group entities"),
 *   category = @Translation("Webform access")
 * )
 */
class WebformAccessGroupEntityBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The webform access group storage.
   *
   * @var \Drupal\webform_access\WebformAccessGroupStorageInterface
   */
  protected $webformAccessGroupStorage;

  /**
   * Creates a WebformAccessGroupEntityBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->webformAccessGroupStorage = $entity_type_manager->getStorage('webform_access_group');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->webformAccessGroupStorage->getUserEntities($this->currentUser, 'node');
    if (empty($nodes)) {
      return NULL;
    }

    $items = [];
    foreach ($nodes as $node) {
      if ($node->access()) {
        $items[] = $node->toLink()->toRenderable();
      }
    }
    if (empty($items)) {
      return NULL;
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // @todo Setup cache tags and context .
    return 0;
  }

}
