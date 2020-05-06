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
  protected function defineDefaultProperties() {
    return [
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Title.
      'title_display' => '',
      'help_display' => '',
      // Details.
      'open' => FALSE,
      'summary_attributes' => [],
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    if (isset($element['#webform_key'])) {
      $element['#attributes']['data-webform-key'] = $element['#webform_key'];
    }

    $element['#attached']['library'][] = 'webform/webform.element.details';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'details';
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
