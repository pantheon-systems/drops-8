<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'machine_name' element.
 *
 * @WebformElement(
 *   id = "machine_name",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!MachineName.php/class/MachineName",
 *   description = @Translation("Provides a form element to enter a machine name, which is validated to ensure that the name is unique and does not contain disallowed characters."),
 *   label = @Translation("Machine name"),
 *   hidden = TRUE,
 * )
 */
class MachineName extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    // Since all elements are placed under the $form['elements'] we need to
    // prepend the 'element' container to the #machine_name source.
    if (isset($element['#machine_name']['source'])) {
      array_unshift($element['#machine_name']['source'], 'elements');
    }
    else {
      $element['#machine_name']['source'] = ['elements', 'label'];
    }

    // Set #exists callback to function that will always returns TRUE.
    // This will prevent error and arbitrary functions from being called.
    // @see \Drupal\Core\Render\Element\MachineName::validateMachineName.
    $element['#machine_name']['exists'] = [get_class($this), 'exists'];
  }

  /**
   * Exists callback for machine name that always returns TRUE.
   *
   * @return bool
   *   Always returns TRUE.
   */
  public static function exists() {
    return FALSE;
  }

}
