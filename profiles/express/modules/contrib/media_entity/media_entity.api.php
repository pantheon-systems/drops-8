<?php

/**
 * @file
 * Hooks related to media entity and it's plugins.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in \Drupal\media_entity\Annotation\MediaType.
 *
 * @param array $types
 *   The array of type plugins, keyed on the machine-readable name.
 */
function hook_media_entity_type_info_alter(&$types) {
  $types['youtube']['label'] = t('Youtube rocks!');
}

/**
 * @} End of "addtogroup hooks".
 */
