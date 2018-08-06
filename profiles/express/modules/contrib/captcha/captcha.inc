<?php

/**
 * @file
 * General CAPTCHA functionality and helper functions.
 */

use Drupal\captcha\Entity\CaptchaPoint;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Element;

/**
 * Helper function for adding/updating a CAPTCHA point.
 *
 * @param string $form_id
 *   the form ID to configure.
 * @param string $captcha_type
 *   The setting for the given form_id, can be:
 *   - 'default' to use the default challenge type
 *   - NULL to remove the entry for the CAPTCHA type
 *   - something of the form 'image_captcha/Image'
 *   - an object with attributes $captcha_type->module
 *   and $captcha_type->captcha_type.
 */
function captcha_set_form_id_setting($form_id, $captcha_type) {
  /* @var CaptchaPoint $captcha_point */
  $captcha_point = CaptchaPoint::load($form_id);

  if ($captcha_point) {
    $captcha_point->setCaptchaType($captcha_type);
  }
  else {
    $captcha_point = new CaptchaPoint([
      'formId' => $form_id,
      'captchaType' => $captcha_type,
    ], 'captcha_point');
  }
  $captcha_point->enable();

  $captcha_point->save();
}

/**
 * Get the CAPTCHA setting for a given form_id.
 *
 * @param string $form_id
 *   The form_id to query for.
 * @param bool $symbolic
 *   Flag to return as (symbolic) strings instead of object.
 *
 * @return null|CaptchaPoint
 *   NULL if no setting is known
 *   captcha point object with fields 'module' and 'captcha_type'.
 *   If argument $symbolic is true, returns 'default' or in the
 *   form 'captcha/Math'.
 */
function captcha_get_form_id_setting($form_id, $symbolic = FALSE) {
  /* @var CaptchaPoint $captchaPoint */
  $captcha_point = CaptchaPoint::load($form_id);

  if ($symbolic) {
    $captcha_point = $captcha_point->getCaptchaType();
  }

  return $captcha_point;
}

/**
 * Helper function for generating a new CAPTCHA session.
 *
 * @param string $form_id
 *   The form_id of the form to add a CAPTCHA to.
 * @param int $status
 *   The initial status of the CAPTHCA session.
 *
 * @return string
 *   The session ID of the new CAPTCHA session.
 */
function _captcha_generate_captcha_session($form_id = NULL, $status = CAPTCHA_STATUS_UNSOLVED) {
  $user = \Drupal::currentUser();

  // Initialize solution with random data.
  $solution = hash('sha256', mt_rand());

  // Insert an entry and thankfully receive the value
  // of the autoincrement field 'csid'.
  $captcha_sid = db_insert('captcha_sessions')
    ->fields([
      'uid' => $user->id(),
      'sid' => session_id(),
      'ip_address' => Drupal::request()->getClientIp(),
      'timestamp' => REQUEST_TIME,
      'form_id' => $form_id,
      'solution' => $solution,
      'status' => $status,
      'attempts' => 0,
    ])
    ->execute();
  return $captcha_sid;
}

/**
 * Helper function for updating the solution in the CAPTCHA session table.
 *
 * @param string $captcha_sid
 *   The CAPTCHA session ID to update.
 * @param string $solution
 *   The new solution to associate with the given CAPTCHA session.
 */
function _captcha_update_captcha_session($captcha_sid, $solution) {
  db_update('captcha_sessions')
    ->condition('csid', $captcha_sid)
    ->fields([
      'timestamp' => REQUEST_TIME,
      'solution' => $solution,
    ])
    ->execute();
}

/**
 * Helper function for checking if CAPTCHA is required for user.
 *
 * Based on the CAPTCHA persistence setting, the CAPTCHA session
 * ID and user session info.
 */
function _captcha_required_for_user($captcha_sid, $form_id) {
  // Get the CAPTCHA persistence setting.
  $captcha_persistence = \Drupal::config('captcha.settings')
    ->get('persistence');

  // First check: should we always add a CAPTCHA?
  if ($captcha_persistence == CAPTCHA_PERSISTENCE_SHOW_ALWAYS) {
    return TRUE;
  }

  // Get the status of the current CAPTCHA session.
  $captcha_session_status = db_query('SELECT status FROM {captcha_sessions} WHERE csid = :csid', [':csid' => $captcha_sid])->fetchField();
  // Second check: if the current session is already
  // solved: omit further CAPTCHAs.
  if ($captcha_session_status == CAPTCHA_STATUS_SOLVED) {
    return FALSE;
  }

  // Third check: look at the persistence level
  // (per form instance, per form or per user).
  if ($captcha_persistence == CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE) {
    return TRUE;
  }
  else {
    $captcha_success_form_ids = isset($_SESSION['captcha_success_form_ids']) ? (array) ($_SESSION['captcha_success_form_ids']) : [];
    switch ($captcha_persistence) {
      case CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL:
        return (count($captcha_success_form_ids) == 0);

      case CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE:
        return !isset($captcha_success_form_ids[$form_id]);
    }
  }

  // We should never get to this point, but to be sure, we return TRUE.
  return TRUE;
}

/**
 * Get the CAPTCHA description.
 *
 * @return string
 *   CAPTCHA description.
 */
function _captcha_get_description() {
  $description = \Drupal::config('captcha.settings')->get('description');
  return Xss::filter($description);
}

/**
 * Parse or interpret the given captcha_type.
 *
 * @param string $captcha_type
 *   representation of the CAPTCHA type,
 *   e.g. 'default', 'captcha/Math', 'image_captcha/Image'.
 *
 * @return array
 *   list($captcha_module, $captcha_type).
 */
function _captcha_parse_captcha_type($captcha_type) {
  if ($captcha_type == 'default') {
    $captcha_type = \Drupal::config('captcha.settings')
      ->get('default_challenge', 'captcha/Math');
  }
  return explode('/', $captcha_type);
}

/**
 * Helper function to get placement information for a given form_id.
 */
function _captcha_get_captcha_placement($form_id, $form) {
  // Get CAPTCHA placement map from cache. Two levels of cache:
  // static variable in this function and storage in the variables table.
  static $placement_map = NULL;

  $write_cache = FALSE;

  // Try first level cache.
  if ($placement_map === NULL) {
    // If first level cache missed: try second level cache.
    if ($cache = \Drupal::cache()->get('captcha_placement_map_cache')) {
      $placement_map = $cache->data;
    }
    else {
      // If second level cache missed: initialize the placement map
      // and let other modules hook into this with the
      // hook_captcha_placement_map hook.
      // By default however, probably all Drupal core forms
      // are already correctly handled with the best effort guess
      // based on the 'actions' element (see below).
      $placement_map = \Drupal::moduleHandler()
        ->invokeAll('captcha_placement_map');
      $write_cache = TRUE;
    }
  }

  // Query the placement map.
  if (array_key_exists($form_id, $placement_map)) {
    $placement = $placement_map[$form_id];
  }
  // If no placement info is available in placement map:
  // make a best effort guess.
  else {
    // If there is an "actions" button group, a good placement
    // is just before that.
    if (isset($form['actions']) && isset($form['actions']['#type']) && $form['actions']['#type'] === 'actions') {
      $placement = [
        'path' => [],
        'key' => 'actions',
        // #type 'actions' defaults to 100.
        'weight' => (isset($form['actions']['#weight']) ? $form['actions']['#weight'] - 1 : 99),
      ];
    }
    else {
      // Search the form for buttons and guess placement from it.
      $buttons = _captcha_search_buttons($form);
      if (count($buttons)) {
        // Pick first button.
        // TODO: make this more sofisticated? Use cases needed.
        $placement = $buttons[0];
      }
      else {
        // Use NULL when no buttons were found.
        $placement = NULL;
      }
    }

    // Store calculated placement in cache.
    $placement_map[$form_id] = $placement;
    $write_cache = TRUE;
  }

  if ($write_cache) {
    \Drupal::cache()->set('captcha_placement_map_cache', $placement_map);
  }
  return $placement;
}

/**
 * Helper function for searching the buttons in a form.
 *
 * @param array $form
 *   The form to search button elements in.
 *
 * @return array
 *   Array of paths to the buttons.
 *   A path is an array of keys leading to the button, the last
 *   item in the path is the weight of the button element
 *   (or NULL if undefined).
 */
function _captcha_search_buttons(array $form) {
  $buttons = [];

  foreach (Element::children($form, FALSE) as $key) {
    // Look for submit or button type elements.
    if (isset($form[$key]['#type']) && ($form[$key]['#type'] == 'submit' || $form[$key]['#type'] == 'button')) {
      $weight = isset($form[$key]['#weight']) ? $form[$key]['#weight'] : NULL;
      $buttons[] = [
        'path' => [],
        'key' => $key,
        'weight' => $weight,
      ];
    }
    // Process children recursively.
    $children_buttons = _captcha_search_buttons($form[$key]);
    foreach ($children_buttons as $b) {
      $b['path'] = array_merge([$key], $b['path']);
      $buttons[] = $b;
    }
  }

  return $buttons;
}

/**
 * Helper function to insert a CAPTCHA element before a given form element.
 *
 * @param array $form
 *   the form to add the CAPTCHA element to.
 * @param array $placement
 *   information where the CAPTCHA element should be inserted.
 *   $placement should be an associative array with fields:
 *     - 'path': path (array of path items) of the container in
 *       the form where the CAPTCHA element should be inserted.
 *     - 'key': the key of the element before which the CAPTCHA element
 *       should be inserted. If the field 'key' is undefined or NULL,
 *       the CAPTCHA will just be appended in the container.
 *     - 'weight': if 'key' is not NULL: should be the weight of the
 *       element defined by 'key'. If 'key' is NULL and weight is not NULL:
 *       set the weight property of the CAPTCHA element to this value.
 * @param array $captcha_element
 *   the CAPTCHA element to insert.
 */
function _captcha_insert_captcha_element(array &$form, array $placement, array $captcha_element) {
  // Get path, target and target weight or use defaults if not available.
  $target_key = isset($placement['key']) ? $placement['key'] : NULL;
  $target_weight = isset($placement['weight']) ? $placement['weight'] : NULL;
  $path = isset($placement['path']) ? $placement['path'] : [];

  // Walk through the form along the path.
  $form_stepper = &$form;
  foreach ($path as $step) {
    if (isset($form_stepper[$step])) {
      $form_stepper = &$form_stepper[$step];
    }
    else {
      // Given path is invalid: stop stepping and
      // continue in best effort (append instead of insert).
      $target_key = NULL;
      break;
    }
  }

  // If no target is available: just append the CAPTCHA element
  // to the container.
  if ($target_key == NULL || !array_key_exists($target_key, $form_stepper)) {
    // Optionally, set weight of CAPTCHA element.
    if ($target_weight != NULL) {
      $captcha_element['#weight'] = $target_weight;
    }
    $form_stepper['captcha'] = $captcha_element;
  }
  // If there is a target available: make sure the CAPTCHA element
  // comes right before it.
  else {
    // If target has a weight: set weight of CAPTCHA element a bit smaller
    // and just append the CAPTCHA: sorting will fix the ordering anyway.
    if ($target_weight != NULL) {
      $captcha_element['#weight'] = $target_weight - .1;
      $form_stepper['captcha'] = $captcha_element;
    }
    else {
      // If we can't play with weights: insert the CAPTCHA element
      // at the right position. Because PHP lacks a function for
      // this (array_splice() comes close, but it does not preserve
      // the key of the inserted element), we do it by hand: chop of
      // the end, append the CAPTCHA element and put the end back.
      $offset = array_search($target_key, array_keys($form_stepper));
      $end = array_splice($form_stepper, $offset);
      $form_stepper['captcha'] = $captcha_element;
      foreach ($end as $k => $v) {
        $form_stepper[$k] = $v;
      }
    }
  }
}
