<?php

/**
 * @file
 * Contains \Drupal\redirect\Plugin\migrate\source\d7\PathRedirect.
 */

namespace Drupal\redirect\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "d7_path_redirect",
 *   source_provider = "redirect"
 * )
 */
class PathRedirect extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('redirect', 'p')->fields('p');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    static $default_status_code;
    if (!isset($default_status_code)) {
      $default_status_code = unserialize($this->getDatabase()
        ->select('variable', 'v')
        ->fields('v', ['value'])
        ->condition('name', 'redirect_default_status_code')
        ->execute()
        ->fetchField());
    }
    $current_status_code = $row->getSourceProperty('status_code');
    $status_code = $current_status_code != 0 ? $current_status_code : $default_status_code;
    $row->setSourceProperty('status_code', $status_code);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'rid' => $this->t('Redirect ID'),
      'hash' => $this->t('Hash'),
      'type' => $this->t('Type'),
      'uid' => $this->t('UID'),
      'source' => $this->t('Source'),
      'source_options' => $this->t('Source Options'),
      'redirect' => $this->t('Redirect'),
      'redirect_options' => $this->t('Redirect Options'),
      'language' => $this->t('Language'),
      'status_code' => $this->t('Status Code'),
      'count' => $this->t('Count'),
      'access' => $this->t('Access'),
    ];
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
