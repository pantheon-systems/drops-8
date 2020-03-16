<?php

namespace Drupal\metatag\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\metatag\MetatagTagPluginManager;
use Drupal\metatag\MetatagGroupPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Metatag routes.
 */
class MetatagController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Metatag tag plugin manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $tagManager;

  /**
   * Metatag group plugin manager.
   *
   * @var \Drupal\metatag\MetatagGroupPluginManager
   */
  protected $groupManager;

  /**
   * Constructs a new \Drupal\views_ui\Controller\ViewsUIController object.
   *
   * @param \Drupal\metatag\MetatagTagPluginManager $tag_manaager
   *   The tag manager object.
   * @param \Drupal\metatag\MetatagGroupPluginManager $group_manager
   *   The group manager object.
   */
  public function __construct(MetatagTagPluginManager $tag_manaager, MetatagGroupPluginManager $group_manager) {
    $this->tagManager = $tag_manaager;
    $this->groupManager = $group_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.metatag.tag'),
      $container->get('plugin.manager.metatag.group')
    );
  }

  /**
   * Lists all plugins.
   *
   * @return array
   *   The Metatag plugins report page.
   */
  public function reportPlugins() {
    // Get tags.
    $tag_definitions = $this->tagManager->getDefinitions();
    uasort($tag_definitions, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $tags = [];
    foreach ($tag_definitions as $tag_name => $tag_definition) {
      $tags[$tag_definition['group']][$tag_name] = $tag_definition;
    }

    // Get groups.
    $group_definitions = $this->groupManager->getDefinitions();
    uasort($group_definitions, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Build plugin by group.
    $build = [];
    foreach ($group_definitions as $group_name => $group_definition) {
      $build[$group_name] = [];
      // Group title.
      $build[$group_name]['title'] = [
        '#markup' => $group_definition['label'] . ' (' . $group_name . ')',
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
      ];
      // Group description.
      $build[$group_name]['description'] = [
        '#markup' => $group_definition['description'],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      $rows = [];
      foreach ($tags[$group_name] as $definition) {
        $row = [];
        $row['label'] = [
          'data' => [
            'label' => [
              '#markup' => $definition['label'],
              '#prefix' => '<h3>',
              '#suffix' => '</h3>',
            ],
          ],
        ];
        $row['name'] = [
          'data' => $definition['name'],
          'nowrap' => 'nowrap',
        ];
        $row['id'] = $definition['id'];
        $row['type'] = $definition['type'];
        $row['weight'] = $definition['weight'];
        $row['secure'] = $definition['secure'] ? $this->t('Yes') : $this->t('No');
        $row['multiple'] = $definition['multiple'] ? $this->t('Yes') : $this->t('No');
        $row['provider'] = $definition['provider'];
        $key = $definition['group'] . '.' . $definition['id'];
        $rows[$key] = $row;
        $row = [];
        $row['description'] = [
          'data' => [
            '#markup' => $definition['description'],
          ],
          'colspan' => 8,
        ];
        $rows[$key . '_desc'] = $row;
      }
      ksort($rows);

      $build[$group_name]['tags'] = [
        '#type' => 'table',
        '#header' => [
          ['data' => $this->t('Label / Description')],
          ['data' => $this->t('Name')],
          ['data' => $this->t('ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Type'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Weight'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Secure'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Multiple'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Provided by')],
        ],
        '#rows' => $rows,
        '#suffix' => '<br /><br />',
        '#caption' => $this->t('All meta tags in the "@group" group.', ['@group' => $group_definition['label']]),
        '#sticky' => TRUE,
      ];
    }
    return $build;
  }

}
