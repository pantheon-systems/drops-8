<?php

namespace Drupal\responsive_preview;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\responsive_preview\Entity\Device;

/**
 * ResponsivePreview service.
 */
class ResponsivePreview implements ResponsivePreviewInterface {

  /**
   * Admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $routerAdminContext;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * ResponsivePreview constructor.
   *
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   Admin context service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   CurrentPathStack service to get the path.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(AdminContext $adminContext, CurrentPathStack $currentPathStack, RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {

    $this->routerAdminContext = $adminContext;
    $this->currentPathStack = $currentPathStack;
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {

    $url = NULL;
    if (!$this->routerAdminContext->isAdminRoute()) {
      $url = $this->currentPathStack->getPath();
    }
    else {
      // If we are on an edit-form, try to resolve the canonical url.
      $form = $this->routeMatch->getRouteObject()->getDefault("_entity_form");
      if ($form) {
        $entity_type = current(explode('.', $form));
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $this->routeMatch->getParameter($entity_type);
        if ($entity && $entity->hasLinkTemplate('canonical')) {
          $url = $entity->toUrl()->toString();
        }
      }
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderableDevicesList() {
    $links = [];

    /** @var \Drupal\responsive_preview\Entity\Device[] $devices */
    $devices = $this->entityTypeManager
      ->getStorage('responsive_preview_device')
      ->loadByProperties(['status' => 1]);

    uasort($devices, [Device::class, 'sort']);

    foreach ($devices as $name => $entity) {
      $dimensions = $entity->getDimensions();
      $links[$name] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $entity->label(),
        '#attributes' => [
          'data-responsive-preview-name' => $name,
          'data-responsive-preview-width' => $dimensions['width'],
          'data-responsive-preview-height' => $dimensions['height'],
          'data-responsive-preview-dppx' => $dimensions['dppx'],
          'class' => [
            'responsive-preview-device',
            'responsive-preview-icon',
            'responsive-preview-icon-active',
          ],
        ],
      ];
    }

    // Add a configuration link.
    $links['configure_link'] = [
      '#type' => 'link',
      '#title' => t('Configure devices'),
      '#url' => Url::fromRoute('entity.responsive_preview_device.collection'),
      '#access' => $this->currentUser->hasPermission('administer responsive preview'),
      '#attributes' => [
        'class' => ['responsive-preview-configure'],
      ],
    ];

    return [
      '#theme' => 'item_list__responsive_preview',
      '#items' => $links,
      '#attributes' => [
        'class' => ['responsive-preview-options'],
      ],
      '#wrapper_attributes' => [
        'class' => ['responsive-preview-item-list'],
      ],
    ];
  }

}
