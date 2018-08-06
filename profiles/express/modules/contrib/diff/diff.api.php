<?php

/**
 * @file
 * Hooks and documentation related to diff module.
 */

/**
 * @defgroup diff Diff API
 *
 * @{
 * Diff module provides a new plugin type, which determines how entity fields
 * are mapped into strings which are then compared by the Diff component.
 *
 * Field diff builders are plugins annotated with class
 * \Drupal\diff\Annotation\FieldDiffBuilder, and implement plugin interface
 * \Drupal\diff\FieldDiffBuilderInterface. Field diff builders plugins are
 * managed by the \Drupal\diff\DiffBuilderManager class. Field diff builders
 * classes usually extend base class \Drupal\diff\FieldDiffBuilderBase and need
 * to be in the namespace \Drupal\{your_module}\Plugin\diff\Field\. See the
 * @link plugin_api Plugin API topic @endlink for more information on how to
 * define plugins.
 *
 * @} End of "defgroup diff".
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in \Drupal\diff\Annotation\FieldDiffBuilder.
 *
 * @param array $diff_builders
 *   The array of field diff builders plugins, keyed on the machine-readable
 *    plugin name.
 */
function hook_field_diff_builder_info_alter(array &$diff_builders) {
  // Set a new label for the text_field_diff_builder plugin
  // instead of the one provided in the annotation.
  $diff_builders['text_field_diff_builder']['label'] = t('New label');
}

/**
 * Alter the information provided in \Drupal\diff\Annotation\DiffLayoutBuilder.
 *
 * @param array $diff_layouts
 *   The array of diff layout builders plugins, keyed on the machine-readable
 *    plugin name.
 */
function hook_diff_layout_builder_info_alter(array &$diff_layouts) {
  // Set a new label for the text_field_diff_builder plugin
  // instead of the one provided in the annotation.
  $diff_layouts['my_layout']['label'] = t('New label');
}

/**
 * @} End of "addtogroup hooks".
 */
