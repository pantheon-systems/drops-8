<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'email_multiple' element.
 *
 * @WebformElement(
 *   id = "webform_email_multiple",
 *   label = @Translation("Email multiple"),
 *   description = @Translation("Provides a form element for multiple email addresses."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformEmailMultiple extends Email {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();
    unset(
      $properties['multiple'],
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'link':
        $emails = preg_split('/\s*,\s*/', $value);
        $total = count($emails);
        $i = 0;
        $links = [];
        foreach ($emails as $email) {
          $links[] = [
            '#type' => 'link',
            '#title' => $email,
            '#url' => \Drupal::pathValidator()->getUrlIfValid('mailto:' . $email),
            '#suffix' => (++$i !== $total) ? ', ' : '',
          ];
        }
        return $links;

      default:
        return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

}
