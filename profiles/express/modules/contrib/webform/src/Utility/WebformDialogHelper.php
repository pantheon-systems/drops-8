<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Serialization\Json;

/**
 * Helper class for modal and off-canvas dialog methods.
 */
class WebformDialogHelper {

  /**
   * Width for wide dialog. (modal: 1000px; off-canvas: 800px)
   *
   * Used by: Video only.
   *
   * @var string
   */
  const DIALOG_WIDE = 'wide';

  /**
   * Width for normal dialog. (modal: 800px; off-canvas: 600px)
   *
   * Used by: Add and edit element/handler, etc…
   *
   * @var string
   */
  const DIALOG_NORMAL = 'normal';

  /**
   * Width for narrow dialog. (modal: 700px; off-canvas: 500px)
   *
   * Used by: Duplicate and delete entity, notes, etc…
   *
   * @var string
   */
  const DIALOG_NARROW = 'narrow';

  /**
   * Use outside-in off-canvas system tray instead of dialogs.
   *
   * @return bool
   *   TRUE if outside_in.module is enabled and system trays are not disabled.
   */
  public static function useOffCanvas() {
    return (!\Drupal::config('webform.settings')->get('ui.offcanvas_disabled')) ? TRUE : FALSE;
  }

  /**
   * Attach libraries required by (modal) dialogs.
   *
   * @param array $build
   *   A render array.
   */
  public static function attachLibraries(array &$build) {
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';
    if (static::useOffCanvas()) {
      $build['#attached']['library'][] = 'webform/webform.admin.off_canvas';
    }
    // @see \Drupal\webform\Element\WebformHtmlEditor::preRenderWebformHtmlEditor
    if (\Drupal::moduleHandler()->moduleExists('imce') && \Drupal\imce\Imce::access()) {
      $build['#attached']['library'][] = 'imce/drupal.imce.ckeditor';
      $build['#attached']['drupalSettings']['webform']['html_editor']['ImceImageIcon'] = file_create_url(drupal_get_path('module', 'imce') . '/js/plugins/ckeditor/icons/imceimage.png');
    }
  }

  /**
   * Get modal dialog attributes.
   *
   * @param int|string $width
   *   Width of the modal dialog.
   * @param array $class
   *   Additional class names to be included in the dialog's attributes.
   *
   * @return array
   *   Modal dialog attributes.
   */
  public static function getModalDialogAttributes($width = self::DIALOG_NORMAL, array $class = []) {
    if (\Drupal::config('webform.settings')->get('ui.dialog_disabled')) {
      return $class ? ['class' => $class] : [];
    }

    $dialog_widths = [
      static::DIALOG_WIDE => 1000,
      static::DIALOG_NORMAL => 800,
      static::DIALOG_NARROW => 700,
    ];
    $width = (isset($dialog_widths[$width])) ? $dialog_widths[$width] : $width;

    $class[] = 'webform-ajax-link';
    return [
      'class' => $class,
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => $width,
        // .webform-ui-dialog is used to set the dialog's top position.
        // @see modules/sandbox/webform/css/webform.ajax.css
        'dialogClass' => 'webform-ui-dialog',
      ]),
    ];
  }

  /**
   * Get modal dialog attributes.
   *
   * @param int|string $width
   *   Width of the modal dialog.
   * @param array $class
   *   Additional class names to be included in the dialog's attributes.
   *
   * @return array
   *   Modal dialog attributes.
   */
  public static function getOffCanvasDialogAttributes($width = self::DIALOG_NORMAL, array $class = []) {
    if (\Drupal::config('webform.settings')->get('ui.dialog_disabled')) {
      return $class ? ['class' => $class] : [];
    }

    if (!static::useOffCanvas()) {
      return self::getModalDialogAttributes($width, $class);
    }

    $dialog_widths = [
      static::DIALOG_WIDE => 800,
      static::DIALOG_NORMAL => 600,
      static::DIALOG_NARROW => 550,
    ];
    $width = (isset($dialog_widths[$width])) ? $dialog_widths[$width] : $width;

    $class[] = 'webform-ajax-link';
    return [
      'class' => $class,
      'data-dialog-type' => 'dialog',
      'data-dialog-renderer' => 'off_canvas',
      'data-dialog-options' => Json::encode([
        'width' => $width,
        'dialogClass' => 'ui-dialog-off-canvas webform-off-canvas',
      ]),
    ];
  }

}
