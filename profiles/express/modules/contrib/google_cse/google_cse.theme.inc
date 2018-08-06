<?php

/**
 * @file
 * Themeable functions for Google Custom Search Engine.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Selects tags according to chosen search engine layout.
 *
 * @return string
 *   Tags
 */
function google_cse_get_cse_tag() {
  $display = \Drupal::config('search.page.google_cse_search')->get('configuration')['custom_results_display'];

  switch ($display) {
    case 'overlay':
    case 'compact':
    case 'full-width':
      return '<gcse:search gname="google_cse"></gcse:search>';

    case 'two-page':
      return '<gcse:searchbox-only gname="google_cse"></gcse:searchbox-only><gcse:searchresults-only gname="google_cse"></gcse:searchresults-only>';

    case 'two-column':
      return '<gcse:searchbox gname="google_cse"></gcse:searchbox><gcse:searchresults gname="google_cse"></gcse:searchresults>';

    case 'results-only':
      return '<gcse:searchresults-only gname="google_cse"></gcse:searchresults-only>';

    case 'google-hosted':
      return '<gcse:searchbox-only gname="google_cse"></gcse:searchbox-only>';

    default:
      \Drupal::logger('google_cse')->critical('Invalid custom result display %display', array('%display' => $display));
  }
}

/**
 * The search results page can be themed/customized.
 */
function template_preprocess_google_cse_results(&$variables) {
  $config = \Drupal::config('search.page.google_cse_search');
  $variables['results_searchbox_form'] = $variables['form'] ? \Drupal::formBuilder()->getForm('Drupal\google_cse\Form\GoogleCSESearchBoxForm') : '';
  $variables['cse_tag'] = google_cse_get_cse_tag();
  $url = Url::fromUri('http://' . $config->get('configuration')['domain'] . '/cse', [
    'query' => ['keys' => \Drupal::request()->query->get('keys')],
  ]);
  $variables['noscript'] = t('@google, or enable JavaScript to view them here.', [
    '@google' => Link::fromTextAndUrl('View the results at Google', $url)->toString(),
  ]);
  $variables['prefix'] = Xss::filterAdmin($config->get('configuration')['results_prefix']);
  $variables['suffix'] = Xss::filterAdmin($config->get('configuration')['results_suffix']);
  if (google_cse_validate_request()) {
    $variables['#attached']['library'][] = 'google_cse/googlecseResults';
    // Adds a custom library by google_cse_library_info_build()
    if (!empty($config->get('configuration')['custom_css'])) {
      $variables['#attached']['library'][] = 'google_cse/googlecseCustomcss';
    }
  }

}

/**
 * Validate GET parameters to avoid displaying inappropriate search results.
 */
function google_cse_validate_request() {
  $request = \Drupal::request();
  $config = \Drupal::config('search.page.google_cse_search');
  return (
    (empty($request->query->get('cx')) || $request->query->get('cx') == $config->get('configuration')['cx']) &&
    (empty($request->query->get('safe')) || $request->query->get('safe') == $config->get('configuration')['safe']) &&
    (empty($request->query->get('sitesearch')) || (($options = \Drupal::service('google_cse.services')->sitesearchOptions()) && isset($options[$request->query->get('sitesearch')])))
  );
}
