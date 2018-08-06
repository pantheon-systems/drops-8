<?php

namespace Drupal\webform\Plugin\WebformElement;

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
  public function getDefaultProperties() {
    $default_properties = parent::getDefaultProperties();
    unset($default_properties['multiple']);
    return $default_properties;
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, $value, array $options = []) {
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
        return parent::formatHtmlItem($element, $value, $options);
    }
  }

}
