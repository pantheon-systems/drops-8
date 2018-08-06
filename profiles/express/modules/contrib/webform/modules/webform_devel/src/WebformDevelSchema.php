<?php

namespace Drupal\webform_devel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\Element\Email as EmailElement;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElement\BooleanBase;
use Drupal\webform\Plugin\WebformElement\DateBase;
use Drupal\webform\Plugin\WebformElement\NumericBase;
use Drupal\webform\Plugin\WebformElement\Textarea;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides a webform schema generator.
 *
 */
class WebformDevelSchema implements WebformDevelSchemaInterface {

  use StringTranslationTrait;

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformDevelSchema object.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager) {
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
  }

  public function getColumns() {
    return [
      'name' => $this->t('Name'),
      'type' => $this->t('Type'),
      'datatype' => $this->t('Datatype'),
      'maxlength' => $this->t('Maxlength'),
      'required' => $this->t('Required'),
      'multiple' => $this->t('Multiple'),
      'options' => $this->t('Options'),
    ];
  }

  public function getElements(WebformInterface $webform) {
    $records = [];
    $elements = $webform->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $element_key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);

      $records[$element_key] = $this->getElement($element_key, $element);
      if ($element_plugin instanceof WebformCompositeBase) {
        $composite_elements = $element_plugin->getInitializedCompositeElement($element);
        foreach ($composite_elements as $composite_element_key => $composite_element) {
          $records["$element_key.$composite_element_key"] = $this->getElement("$element_key.$composite_element_key", $composite_element);
        }
      }
    }
    return $records;
  }

  /**
   * Get webform element schema.
   *
   * @param $element_key
   *   The webform element key.
   * @param array $element
   *   The webform element.
   *
   * @return array
   *   An array containing the schema for the webform element.
   */
  protected function getElement($element_key, array $element) {
    $element_info = $this->elementInfo->getInfo($element['#type']);
    $element_plugin = $this->elementManager->getElementInstance($element);

    $data = [];

    // Name.
    $data['name'] = $element_key;

    // Element type.
    $data['type'] = $element['#type'];

    // Datatype.
    if ($element_plugin instanceof WebformCompositeBase) {
      $datatype = 'Composite';
    }
    elseif ($element_plugin instanceof BooleanBase) {
      $datatype = 'Boolean';
    }
    elseif ($element_plugin instanceof DateBase) {
      $datatype = 'Date';
    }
    elseif ($element_plugin instanceof NumericBase) {
      $datatype = 'Number';
    }
    elseif ($element_plugin instanceof Textarea) {
      $datatype = 'Blob';
    }
    elseif ($element_plugin instanceof WebformManagedFileBase) {
      $datatype = 'Number';
    }
    elseif ($element_plugin instanceof WebformElementEntityReferenceInterface) {
      $datatype = 'Number';
    }
    else {
      $datatype = 'Text';
    }
    $data['datatype'] = $datatype;

    // Maxlength.
    if (isset($element['#maxlength'])) {
      $maxlength = $element['#maxlength'];
    }
    elseif (isset($element['#options'])) {
      $maxlength = $this->getOptionsMaxlength($element);
    }
    elseif ($element_plugin instanceof TextField) {
      // @see \Drupal\webform\Plugin\WebformElement\TextField::prepare
      $maxlength = '255';
    }
    elseif (isset($element_info['#maxlength'])) {
      $maxlength = $element_info['#maxlength'];
    }
    else {
      switch ($element['#type']) {
        case 'color':
          $maxlength = 7;
          break;

        case 'email':
        case 'webform_email_confirm':
          $maxlength = EmailElement::EMAIL_MAX_LENGTH;
          break;

        case 'password_confirm':
          $maxlength = $this->elementInfo->getInfo('password')['#maxlength'];
          break;

        case 'textarea':
        case 'text_format':
        case 'webform_signature':
        case 'webform_codemirror':
        case 'webform_email_multiple':
          $maxlength = $this->t('Unlimited');
          break;

        default:
          $maxlength = '';
          break;
      }
    }
    $data['maxlength'] = $maxlength;

    // Required.
    $data['required'] = (!empty($element['#required'])) ? $this->t('Yes') : $this->t('No');

    // Multiple.
    if (isset($element['#multiple'])) {
      $multiple = ($element['#multiple'] > 1) ? $element['#multiple'] : $this->t('Unlimited');
    }
    else {
      $multiple = '1';
    }
    $data['multiple'] = $multiple;

    if (isset($element['#options'])) {
      $data['options'] = OptGroup::flattenOptions($element['#options']);
    }
    else {
      $data['options'] = [];
    }

    return $data;
  }

  /**
   * Get element options maxlength from option values.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   An element options maxlength from option values.
   */
  protected function getOptionsMaxlength(array $element) {
    $options = OptGroup::flattenOptions($element['#options']);
    $maxlength = 0;
    foreach ($options as $option_value => $option_text) {
      $maxlength = max(Unicode::strlen($option_value), $maxlength);
    }

    // Check element w/ other value maxlength.
    if (preg_match('/_other$/', $element['#type'])) {
      if (isset($element['#other__maxlength'])) {
        $maxlength = max($element['#other__maxlength'], $maxlength);
      }
      else {
        // @see \Drupal\webform\Plugin\WebformElement\TextField::prepare
        $maxlength = max(255, $maxlength);
      }
    }

    return $maxlength;
  }

}
