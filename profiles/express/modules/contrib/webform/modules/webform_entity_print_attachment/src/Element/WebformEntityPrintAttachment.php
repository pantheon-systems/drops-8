<?php

namespace Drupal\webform_entity_print_attachment\Element;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;

/**
 * Provides a 'webform_entity_print_attachment' element.
 *
 * @FormElement("webform_entity_print_attachment")
 */
class WebformEntityPrintAttachment extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#view_mode' => 'html',
      '#export_type' => 'pdf',
      '#template' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    /** @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface $print_engine_manager */
    $print_engine_manager = \Drupal::service('plugin.manager.entity_print.print_engine');

    /** @var \Drupal\entity_print\PrintBuilderInterface $print_builder */
    $print_builder = \Drupal::service('entity_print.print_builder');

    // Make sure Webform Entity Print template is used.
    // @see webform_entity_print_entity_view_alter()
    \Drupal::request()->request->set('_webform_entity_print', TRUE);

    // Set view mode or render custom twig.
    // @see \Drupal\webform\WebformSubmissionViewBuilder::view
    // @see webform_entity_print_attachment_webform_submission_view_alter()
    $view_mode = (isset($element['#view_mode'])) ? $element['#view_mode'] : 'html';
    if ($view_mode === 'twig') {
      $webform_submission->_webform_view_mode_twig = $element['#template'];
    }
    \Drupal::request()->request->set('_webform_submissions_view_mode', $view_mode);

    // Get scheme.
    $scheme = 'temporary';

    // Get filename.
    $file_name = 'webform-entity-print-attachment--' . $webform_submission->getWebform()->id() . '-' . $webform_submission->id() . '.pdf';

    // Save printable document.
    $export_type_id = static::getExportTypeId($element);
    $print_engine = $print_engine_manager->createSelectedInstance($export_type_id);
    $temporary_file_path = $print_builder->savePrintable([$webform_submission], $print_engine, $scheme, $file_name);
    if ($temporary_file_path) {
      $contents = file_get_contents($temporary_file_path);
      \Drupal::service('file_system')->delete($temporary_file_path);
    }
    else {
      // Log error.
      $context = ['@filename' => $file_name];
      \Drupal::logger('webform_entity_print')->error("Unable to generate '@filename'.", $context);
      $contents = '';
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileName(array $element, WebformSubmissionInterface $webform_submission) {
    if (empty($element['#filename'])) {
      return $element['#webform_key'] . '.' . static::getExportTypeFileExtension($element);
    }
    else {
      return parent::getFileName($element, $webform_submission);
    }
  }

  /**
   * Get export type id.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   An export type id.
   */
  protected static function getExportTypeId(array $element) {
    if (isset($element['#export_type'])) {
      return $element['#export_type'];
    }
    else {
      list(, $export_type_id) = explode(':', $element['#type']);
      return $export_type_id;
    }
  }

  /**
   * Get export type file extension.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   An export type file extension.
   */
  protected static function getExportTypeFileExtension(array $element) {
    /** @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface $export_type_manager */
    $export_type_manager = \Drupal::service('plugin.manager.entity_print.export_type');

    $export_type_id = static::getExportTypeId($element);
    $definition = $export_type_manager->getDefinition($export_type_id);
    return $definition['file_extension'];
  }

}
