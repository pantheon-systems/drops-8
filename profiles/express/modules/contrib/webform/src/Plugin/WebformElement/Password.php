<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'password' element.
 *
 * @WebformElement(
 *   id = "password",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Password.php/class/Password",
 *   label = @Translation("Password"),
 *   description = @Translation("Provides a form element for entering a password, with hidden text."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Password extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // Return empty value.
    if ($value === '' || $value === NULL) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'obscured':
        return '********';

      default:
        return parent::formatTextItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'obscured';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'obscured' => $this->t('Obscured'),
    ];
  }

}
