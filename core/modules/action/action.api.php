<?php

/**
 * @file
 * Hooks provided by the Actions module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Executes code after an action is deleted.
 *
 * @param $aid
 *   The action ID.
 */
function hook_action_delete($aid) {
  db_delete('actions_assignments')
    ->condition('aid', $aid)
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
