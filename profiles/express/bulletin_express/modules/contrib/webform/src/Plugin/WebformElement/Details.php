<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'details' element.
 *
 * @WebformElement(
 *   id = "details",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Details.php/class/Details",
 *   label = @Translation("Details"),
 *   description = @Translation("Provides an interactive element that a user can open and close."),
 *   category = @Translation("Containers"),
 * )
 */
class Details extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Form display.
      'open' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);

    if (isset($element['#webform_key'])) {
      $element['#attributes']['data-webform-key'] = $element['#webform_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    $name = $element['#webform_key'];
    return ["details[data-webform-key=\"$name\"]" => $title . '  [' . $this->getPluginLabel() . ']'];
  }

}
