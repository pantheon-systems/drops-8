<?php

namespace Drupal\externalauth\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal authmap source from database.
 *
 * @MigrateSource(
 *   id = "authmap",
 *   source_provider = "user"
 * )
 */
class Authmap extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('authmap', 'a')->fields('a');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'uid' => $this->t('User’s users.uid.'),
      'authname' => $this->t('Unique authentication name.'),
      'module' => $this->t('Module which is controlling the authentication.'),
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

}
