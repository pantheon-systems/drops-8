<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_document_file' element.
 *
 * @WebformElement(
 *   id = "webform_document_file",
 *   label = @Translation("Document file"),
 *   description = @Translation("Provides a form element for uploading and saving a document."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class WebformDocumentFile extends WebformManagedFileBase {}
