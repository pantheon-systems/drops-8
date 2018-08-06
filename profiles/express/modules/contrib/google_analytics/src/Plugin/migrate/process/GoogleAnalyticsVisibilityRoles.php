<?php

namespace Drupal\google_analytics\Plugin\migrate\process;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts D7 role ids to D8 role names.
 *
 * @MigrateProcessPlugin(
 *   id = "google_analytics_visibility_roles"
 * )
 */
class GoogleAnalyticsVisibilityRoles extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The migration process plugin, configured for lookups in the d6_user_role
   * and d7_user_role migrations.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->migrationPlugin = $migration_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $migration_configuration = [
      'migration' => [
        'd6_user_role',
        'd7_user_role',
      ],
    ];
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($roles) = $value;

    // Remove role IDs disabled in D6/D7.
    $roles = array_filter($roles);

    $user_role_roles = [];

    if ($roles) {
      foreach ($roles as $key => $role_id) {
        $roles[$key] = $this->migrationPlugin->transform($role_id, $migrate_executable, $row, $destination_property);
      }
      $user_role_roles = array_combine($roles, $roles);
    }

    return $user_role_roles;
  }

}
