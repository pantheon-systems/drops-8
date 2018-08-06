<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'managed_file' element.
 *
 * @WebformElement(
 *   id = "managed_file",
 *   api = "https://api.drupal.org/api/drupal/core!modules!file!src!Element!ManagedFile.php/class/ManagedFile",
 *   label = @Translation("File"),
 *   description = @Translation("Provides a form element for uploading and saving a file."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class ManagedFile extends WebformManagedFileBase {}
