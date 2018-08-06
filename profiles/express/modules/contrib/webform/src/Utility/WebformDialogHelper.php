<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Serialization\Json;

/**
 * Helper class for dialog methods.
 */
class WebformDialogHelper {

  /**
   * Off canvas trigger name.
   *
   * @var string
   */
  protected static $offCanvasTriggerName;

  /**
   * Get Off canvas trigger name.
   *
   * Issue #2862625: Rename offcanvas to two words in code and comments.
   * https://www.drupal.org/node/2862625
   *
   * @return string
   *   The off canvas trigger name.
   */
  public static function getOffCanvasTriggerName() {
    if (isset(self::$offCanvasTriggerName)) {
      return self::$offCanvasTriggerName;
    }

    $main_content_renderers = \Drupal::getContainer()->getParameter('main_content_renderers');

    if (isset($main_content_renderers['drupal_dialog_offcanvas'])) {
      self::$offCanvasTriggerName = 'offcanvas';
    }
    else {
      self::$offCanvasTriggerName = 'off_canvas';
    }

    return self::$offCanvasTriggerName;
  }

  /**
   * Use outside-in off-canvas system tray instead of dialogs.
   *
   * @return bool
   *   TRUE if outside_in.module is enabled and system trays are not disabled.
   */
  public static function useOffCanvas() {
    return ((floatval(\Drupal::VERSION) >= 8.3) && \Drupal::moduleHandler()->moduleExists('outside_in') && !\Drupal::config('webform.settings')->get('ui.offcanvas_disabled')) ? TRUE : FALSE;
  }

  /**
   * Attach libraries required by (modal) dialogs.
   *
   * @param array $build
   *   A render array.
   */
  public static function attachLibraries(array &$build) {
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';
    // @see \Drupal\webform\Element\WebformHtmlEditor::preRenderWebformHtmlEditor
    if (\Drupal::moduleHandler()->moduleExists('imce') && \Drupal\imce\Imce::access()) {
      $build['#attached']['library'][] = 'imce/drupal.imce.ckeditor';
      $build['#attached']['drupalSettings']['webform']['html_editor']['ImceImageIcon'] = file_create_url(drupal_get_path('module', 'imce') . '/js/plugins/ckeditor/icons/imceimage.png');
    }
  }

  /**
   * Get modal dialog attributes.
   *
   * @param int $width
   *   Width of the modal dialog.
   * @param array $class
   *   Additional class names to be included in the dialog's attributes.
   *
   * @return array
   *   Modal dialog attributes.
   */
  public static function getModalDialogAttributes($width = 800, array $class = []) {
    if (\Drupal::config('webform.settings')->get('ui.dialog_disabled')) {
      return $class ? ['class' => $class] : [];
    }
    else {
      $class[] = 'webform-ajax-link';
      if (WebformDialogHelper::useOffCanvas()) {
        return [
          'class' => $class,
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => self::getOffCanvasTriggerName(),
          'data-dialog-options' => Json::encode([
            'width' => ($width > 480) ? 480 : $width,
            // @todo Decide if we want to use 'Outside In' custom system tray styling.
            // 'dialogClass' => 'ui-dialog-outside-in',
          ]),
        ];
      }
      else {
        return [
          'class' => $class,
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => $width,
            // .webform-modal is used to set the dialog's top position.
            // @see modules/sandbox/webform/css/webform.ajax.css
            'dialogClass' => 'webform-modal',
          ]),
        ];
      }
    }
  }

}
