<?php

namespace Drupal\redirect\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "d6_path_redirect"
 * )
 */
class PathRedirect extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('path_redirect', 'p')
      ->fields('p', array(
        'rid',
        'source',
        'redirect',
        'query',
        'fragment',
        'language',
        'type',
        'last_used',
      ));

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'rid' => $this->t('Redirect ID'),
      'source' => $this->t('Source'),
      'redirect' => $this->t('Redirect'),
      'query' => $this->t('Query'),
      'fragment' => $this->t('Fragment'),
      'language' => $this->t('Language'),
      'type' => $this->t('Type'),
      'last_used' => $this->t('Last Used'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['rid']['type'] = 'integer';
    return $ids;
  }

}
