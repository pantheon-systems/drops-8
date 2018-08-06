<?php

namespace Drupal\responsive_preview\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\responsive_preview\ResponsivePreviewInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Responsive preview controls' block.
 *
 * @Block(
 *   id = "responsive_preview_block",
 *   admin_label = @Translation("Responsive preview controls")
 * )
 */
class ResponsivePreviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The router admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The responsive preview service.
   *
   * @var \Drupal\responsive_preview\ResponsivePreviewInterface
   */
  protected $responsivePreview;

  /**
   * Constructs an ResponsivePreviewBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The router admin context service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\responsive_preview\ResponsivePreviewInterface $responsivePreview
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AdminContext $admin_context, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $currentUser, ResponsivePreviewInterface $responsivePreview) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->adminContext = $admin_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
    $this->responsivePreview = $responsivePreview;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.admin_context'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('responsive_preview')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access responsive preview');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];

    $preview_access = $this->currentUser->hasPermission('access responsive preview');

    $url = $this->responsivePreview->getUrl();

    if ($preview_access && $url) {
      $block = [
        'device_options' => $this->responsivePreview->getRenderableDevicesList(),
        '#attached' => [
          'library' => ['responsive_preview/drupal.responsive-preview'],
          'drupalSettings' => [
            'responsive_preview' => [
              'url' => ltrim($url, '/'),
            ],
          ],
        ],
      ];
    }

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user.permissions', 'route.is_admin']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $list_cache_tags = $this->entityTypeManager->getDefinition('responsive_preview_device')->getListCacheTags();
    return Cache::mergeTags($tags, $list_cache_tags);
  }

}
