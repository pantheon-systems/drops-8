<?php

/**
 * @file
 * Provide views data for redirect_404.module.
 */

use Drupal\redirect_404\SqlRedirectNotFoundStorage;

/**
 * Implements hook_views_data().
 */
function redirect_404_views_data() {
  $data = [];

  // Only define views data if the service uses our specific implementation.
  if (!\Drupal::service('redirect.not_found_storage') instanceof SqlRedirectNotFoundStorage) {
    return $data;
  }

  $data['redirect_404']['table']['group'] = t('Redirect 404');

  $data['redirect_404']['table']['base'] = [
    'field' => '',
    'title' => t('Fix 404 pages'),
    'help' => t('Overview for 404 error paths with no redirect assigned yet.'),
  ];

  $data['redirect_404']['path'] = [
    'title' => t('Path'),
    'help' => t('The path of the request.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  $data['redirect_404']['langcode'] = [
    'title' => t('Language'),
    'help' => t('The language of this request.'),
    'field' => [
      'id' => 'redirect_404_langcode',
    ],
    'filter' => [
      'id' => 'language',
    ],
  ];

  $data['redirect_404']['count'] = [
    'title' => t('Count'),
    'help' => t('The number of requests with that path and language.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['redirect_404']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('The timestamp of the last request with that path and language.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['redirect_404']['resolved'] = [
    'title' => t('Resolved'),
    'help' => t('Whether or not this path has a redirect assigned.'),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Resolved'),
      'use_equal' => TRUE,
    ],
  ];

  $data['redirect_404']['redirect_404_operations'] = [
    'title' => t('Operations'),
    'help' => t('Provide operation buttons to handle the 404 path.'),
    'field' => [
      'id' => 'redirect_404_operations',
      'additional fields' => ['path', 'langcode'],
      'real field' => 'path',
    ],
  ];

  return $data;
}
