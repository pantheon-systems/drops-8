<?php

namespace Drupal\system\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\system\SystemManager;

/**
 * Returns responses for System Info routes.
 */
class SystemInfoController implements ContainerInjectionInterface {

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * Cached database version
   *
   * @var string
   */
  protected $databaseServerVersion;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system.manager')
    );
  }

  /**
   * Constructs a SystemInfoController object.
   *
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   */
  public function __construct(SystemManager $systemManager) {
    $this->systemManager = $systemManager;
  }

  /**
   * Displays the site status report.
   *
   * @return array
   *   A render array containing a list of system requirements for the Drupal
   *   installation and whether this installation meets the requirements.
   */
  public function status() {
    $requirements = $this->systemManager->listRequirements();
    if (isset($requirements['database_system_version'])) {
      $requirements['database_system_version']['value'] = $this->fixMariaDbVersion($requirements['database_system_version']['value']);
    }
    return ['#type' => 'status_report_page', '#requirements' => $requirements];
  }

  /**
   * Returns the contents of phpinfo().
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object to be sent to the client.
   */
  public function php() {
    if (function_exists('phpinfo')) {
      ob_start();
      phpinfo();
      $output = ob_get_clean();
    }
    else {
      $output = t('The phpinfo() function has been disabled for security reasons. For more information, visit <a href=":phpinfo">Enabling and disabling phpinfo()</a> handbook page.', [':phpinfo' => 'https://www.drupal.org/node/243993']);
    }
    return new Response($output);
  }

  /**
   * When running on MariaDb on Drupal 8.9.x, the version
   * in $requirements['database_system_version'] is not
   * reported correctly. This is fixed in Drupal 9 directly
   * in the Mysql database driver. For the backport to
   * Drupal 8.9.x, though, we want to avoid the risk of b/c
   * breaks with contrib that might have behavior that varies
   * on the version, e.g. if it might depend on the
   * value in its incorrect state.
   *
   * @see https://www.drupal.org/project/drupal/issues/3213482
   *
   * @return string
   *   Returns the MariaDb server version if applicable, or the passed-in version if not
   */
  protected function fixMariaDbVersion($version) {
    if ($this->isMariaDb()) {
      return $this->getMariaDbVersionMatch();
    }

    return $version;
  }

  /**
   * Determines whether the MySQL distribution is MariaDB or not.
   *
   * @return bool
   *   Returns TRUE if the distribution is MariaDB, or FALSE if not.
   */
  protected function isMariaDb(): bool {
    return (bool) $this->getMariaDbVersionMatch();
  }

  /**
   * Gets the MariaDB portion of the server version.
   *
   * @return string
   *   The MariaDB portion of the server version if present, or NULL if not.
   */
  protected function getMariaDbVersionMatch(): ?string {
    // MariaDB may prefix its version string with '5.5.5-', which should be
    // ignored.
    // @see https://github.com/MariaDB/server/blob/f6633bf058802ad7da8196d01fd19d75c53f7274/include/mysql_com.h#L42.
    $regex = '/^(?:5\.5\.5-)?(\d+\.\d+\.\d+.*-mariadb.*)/i';

    preg_match($regex, $this->getServerVersion(), $matches);
    return (empty($matches[1])) ? NULL : $matches[1];
  }

  /**
   * Gets the database server version.
   *
   * @return string
   *   The database server version.
   */
  protected function getServerVersion(): string {
    if (!$this->databaseServerVersion) {
      $this->databaseServerVersion = \Drupal::database()->query('SELECT VERSION()')->fetchColumn();
    }
    return $this->databaseServerVersion;
  }

}
