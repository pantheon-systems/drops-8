<?php

namespace Drupal\redirect_404\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a views field for the redirect operation buttons.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("redirect_404_operations")
 */
class Redirect404Operations extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructor for the redirect operations view field.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $links = [];

    $query = [
      'query' => [
        'source' => ltrim($this->getValue($values, 'path'), '/'),
        'language' => $this->getValue($values, 'langcode'),
        'destination' => $this->view->getPath(),
      ],
    ];
    $links['add'] = [
      'title' => $this->t('Add redirect'),
      'url' => Url::fromRoute('redirect.add', [], $query),
    ];

    if ($this->currentUser->hasPermission('administer redirect settings')) {
      $links['ignore'] = [
        'title' => $this->t('Ignore'),
        'url' => Url::fromRoute('redirect_404.ignore_404', [
          'path' => $this->getValue($values, 'path'),
          'langcode' => $this->getValue($values, 'langcode'),
        ]),
      ];
    }

    $operations['data'] = [
      '#type' => 'operations',
      '#links' => $links,
    ];

    return $this->renderer->render($operations);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->entityTypeManager->getAccessControlHandler('redirect')->createAccess();
  }

}
