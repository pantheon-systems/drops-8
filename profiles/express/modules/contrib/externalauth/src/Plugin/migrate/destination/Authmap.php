<?php

namespace Drupal\externalauth\Plugin\migrate\destination;

use Drupal\externalauth\AuthmapInterface;
use Drupal\user\Entity\User;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Drupal 8 authmap destination.
 *
 * @MigrateDestination(
 *   id = "authmap"
 * )
 */
class Authmap extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The Authmap class.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The Authmap handling class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, AuthmapInterface $authmap) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->authmap = $authmap;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('externalauth.authmap')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'uid' => array(
        'type' => 'integer',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'uid' => 'Primary key: users.uid for user.',
      'provider' => 'The name of the authentication provider providing the authname',
      'authname' => 'Unique authentication name provided by authentication provider',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load($row->getDestinationProperty('uid'));
    $provider = $row->getDestinationProperty('provider');
    $authname = $row->getDestinationProperty('authname');
    $this->authmap->save($account, $provider, $authname);

    return array($account->id());
  }

}
