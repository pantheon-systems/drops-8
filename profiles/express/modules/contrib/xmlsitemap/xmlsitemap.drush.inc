<?php

/**
 * @file
 * Drush integration functions for the xmlsitemap module.
 *
 * @ingroup xmlsitemap
 */
use Drupal\Component\Utility\Timer;

/**
 * Implements hook_drush_command().
 */
function xmlsitemap_drush_command() {
  $items['xmlsitemap-regenerate'] = array(
    'description' => 'Regenerate the XML sitemap files.',
    'callback' => 'drush_xmlsitemap_regenerate',
    'drupal dependencies' => array('xmlsitemap'),
  );
  $items['xmlsitemap-rebuild'] = array(
    'description' => 'Dump and re-process all possible XML sitemap data, and then regenerate the files.',
    'callback' => 'drush_xmlsitemap_rebuild',
    'drupal dependencies' => array('xmlsitemap'),
  );
  $items['xmlsitemap-index'] = array(
    'description' => 'Process un-indexed XML sitemap links.',
    'callback' => 'drush_xmlsitemap_index',
    'drupal dependencies' => array('xmlsitemap'),
    'options' => array(
      'limit' => 'The limit of links of each type to process.',
    ),
  );
  return $items;
}

/**
 * Regenerate the sitemap files from existing data.
 */
function drush_xmlsitemap_regenerate() {
  // Run the batch process.
  Timer::start('xmlsitemap_regenerate');
  xmlsitemap_run_unprogressive_batch('xmlsitemap_regenerate_batch');

  $vars = array(
    '@timer' => Timer::read('xmlsitemap_regenerate'),
    '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
  );
  drush_print(dt('XML sitemap files regenerated in @timer ms. Peak memory usage: @memory-peak.', $vars));
  Timer::stop('xmlsitemap_regenerate');
}

/**
 * Dump and rebuild all the sitemap data, then regenerate the files.
 */
function drush_xmlsitemap_rebuild() {
  // Build a list of rebuildable link types.
  $rebuild_types = xmlsitemap_get_rebuildable_link_types();
  if (empty($rebuild_types)) {
    return drush_set_error("No link types are rebuildable.");
  }

  // Run the batch process.
  Timer::start('xmlsitemap_rebuild');
  xmlsitemap_run_unprogressive_batch('xmlsitemap_rebuild_batch', $rebuild_types, TRUE);

  $vars = array(
    '@timer' => Timer::read('xmlsitemap_rebuild'),
    '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
  );
  drush_print(dt('XML sitemap files rebuilt in @timer ms. Peak memory usage: @memory-peak.', $vars));
  Timer::stop('xmlsitemap_rebuild');
}

/**
 * Process un-indexed XML sitemap links.
 */
function drush_xmlsitemap_index() {
  $limit = (int) drush_get_option('limit', \Drupal::config('xmlsitemap.settings')->get('batch_limit'));
  $count_before = db_select('xmlsitemap', 'x')->countQuery()->execute()->fetchField();

  \Drupal::moduleHandler()->invokeAll('xmlsitemap_index_links', array('limit' => $limit));

  $count_after = db_select('xmlsitemap', 'x')->countQuery()->execute()->fetchField();

  if ($count_after == $count_before) {
    drush_print(dt('No new XML sitemap links to index.'));
  }
  else {
    drush_print(dt('Indexed @count new XML sitemap links.', array('@count' => $count_after - $count_before)));
  }
}
