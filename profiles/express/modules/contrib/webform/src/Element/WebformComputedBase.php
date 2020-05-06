<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Template\Attribute;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformXss;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base class for 'webform_computed' elements.
 */
abstract class WebformComputedBase extends FormElement implements WebformComputedInterface {

  /**
   * Denotes HTML.
   *
   * @var string
   */
  const MODE_HTML = 'html';

  /**
   * Denotes plain text.
   *
   * @var string
   */
  const MODE_TEXT = 'text';

  /**
   * Denotes markup whose content type should be detected.
   *
   * @var string
   */
  const MODE_AUTO = 'auto';

  /**
   * Cache of submissions being processed.
   *
   * @var array
   */
  protected static $submissions = [];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processWebformComputed'],
      ],
      '#input' => TRUE,
      '#template' => '',
      '#mode' => NULL,
      '#hide_empty' => FALSE,
      // Note: Computed elements do not use the default #ajax wrapper, which is
      // why we can use #ajax as a boolean.
      // @see \Drupal\Core\Render\Element\RenderElement::preRenderAjaxForm
      '#ajax' => FALSE,
      '#webform_submission' => NULL,
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Processes a Webform computed token element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processWebformComputed(&$element, FormStateInterface $form_state, &$complete_form) {
    $webform_submission = static::getWebformSubmission($element, $form_state, $complete_form);
    if ($webform_submission) {
      // Set tree.
      $element['#tree'] = TRUE;

      // Set #type to item to trigger #states behavior.
      // @see drupal_process_states;
      $element['#type'] = 'item';

      $value = static::computeValue($element, $webform_submission);
      static::setWebformComputedElementValue($element, $value);
    }

    if (!empty($element['#states'])) {
      webform_process_states($element, '#wrapper_attributes');
    }

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformComputed']);

    /**************************************************************************/
    // Ajax support
    /**************************************************************************/

    // Enabled Ajax support only for computed elements associated with a
    // webform submission form.
    if ($element['#ajax'] && $form_state->getFormObject() instanceof WebformSubmissionForm) {
      // Get button name and wrapper id.
      $button_name = 'webform-computed-' . implode('-', $element['#parents']) . '-button';
      $wrapper_id = 'webform-computed-' . implode('-', $element['#parents']) . '-wrapper';

      // Get computed value element keys which are used to trigger Ajax updates.
      preg_match_all('/(?:\[webform_submission:values:|data\.|data\[\')([_a-z0-9]+)/', $element['#template'], $matches);
      $element_keys = $matches[1] ?: [];
      $element_keys = array_unique($element_keys);

      // Wrapping the computed element is two div tags.
      // div.js-webform-computed is used to initialize the Ajax updates.
      // div#wrapper_id is used to display response from the Ajax updates.
      $element['#wrapper_id'] = $wrapper_id;
      $element['#prefix'] = '<div class="js-webform-computed" data-webform-element-keys="' . implode(',', $element_keys) . '">' .
        '<div class="js-webform-computed-wrapper" id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div></div>';

      // Add hidden update button.
      $element['update'] = [
        '#type' => 'submit',
        '#value' => t('Update'),
        '#validate' => [[get_called_class(), 'validateWebformComputedCallback']],
        '#submit' => [[get_called_class(), 'submitWebformComputedCallback']],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxWebformComputedCallback'],
          'wrapper' => $wrapper_id,
          'progress' => ['type' => 'none'],
        ],
        // Disable validation, hide button, add submit button trigger class.
        '#attributes' => [
          'formnovalidate' => 'formnovalidate',
          'class' => [
            'js-hide',
            'js-webform-computed-submit',
          ],
        ],
        // Issue #1342066 Document that buttons with the same #value need a unique
        // #name for the Form API to distinguish them, or change the Form API to
        // assign unique #names automatically.
        '#name' => $button_name,
      ];

      // Attached computed element Ajax library.
      $element['#attached']['library'][] = 'webform/webform.element.computed';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function computeValue(array $element, WebformSubmissionInterface $webform_submission) {
    return $element['#template'];
  }

  /**
   * Validates an computed element.
   */
  public static function validateWebformComputed(&$element, FormStateInterface $form_state, &$complete_form) {
    // Make sure the form's state value uses the computed value and not the
    // raw #value. This ensures conditional handlers are triggered using
    // the accurate computed value.
    $webform_submission = static::getWebformSubmission($element, $form_state, $complete_form);
    if ($webform_submission) {
      $value = static::computeValue($element, $webform_submission);
      $form_state->setValueForElement($element['value'], NULL);
      $form_state->setValueForElement($element['hidden'], NULL);
      $form_state->setValueForElement($element, $value);
    }
  }

  /****************************************************************************/
  // Form/Ajax callbacks.
  /****************************************************************************/

  /**
   * Set computed element's value.
   *
   * @param array $element
   *   A computed element.
   * @param string $value
   *   A computer value.
   */
  protected static function setWebformComputedElementValue(array &$element, $value) {
    // Hide empty computed element using display:none so that #states API
    // can still use the empty computed value.
    if ($element['#hide_empty']) {
      $element += ['#wrapper_attributes' => []];
      $element['#wrapper_attributes'] += ['style' => ''];
      if ($value === '') {
        $element['#wrapper_attributes']['style'] .= ($element['#wrapper_attributes']['style'] ? ';' : '') . 'display:none';
      }
      else {
        $element['#wrapper_attributes']['style'] = preg_replace('/;?display:none/', '', $element['#wrapper_attributes']['style']);
      }
    }

    // Display markup.
    $element['value']['#markup'] = $value;
    $element['value']['#allowed_tags'] = WebformXss::getAdminTagList();

    // Include hidden element so that computed value will be available to
    // conditions (#states).
    $element['hidden']['#type'] = 'hidden';
    $element['hidden']['#value'] = ['#markup' => $value];
    $element['hidden']['#parents'] = $element['#parents'];
  }

  /**
   * Determine if the current request is using Ajax.
   */
  protected static function isAjax() {
    // return (\Drupal::request()->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_ajax');
    //
    // ISSUE:
    // For nodes with computed elements there is a duplicate
    // _wrapper_format parameter.
    // (i.e ?_wrapper_format=html&_wrapper_format=drupal_ajax)
    // WORKAROUND:
    // See if _wrapper_format=drupal_ajax is being appended to the query string.
    $querystring = \Drupal::request()->getQueryString();
    return (strpos($querystring, MainContentViewSubscriber::WRAPPER_FORMAT . '=drupal_ajax') !== FALSE);
  }

  /**
   * Webform computed element validate callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateWebformComputedCallback(array $form, FormStateInterface $form_state) {
    $form_state->clearErrors();
  }

  /**
   * Webform computed element submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitWebformComputedCallback(array $form, FormStateInterface $form_state) {
    // Only rebuild if the request is not using Ajax.
    if (!static::isAjax()) {
      $form_state->setRebuild();
    }
  }

  /**
   * Webform computed element Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The computed element element.
   */
  public static function ajaxWebformComputedCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Set element value and #markup  after the form has been validated.
    $webform_submission = static::getWebformSubmission($element, $form_state, $form);
    $value = static::computeValue($element, $webform_submission);
    static::setWebformComputedElementValue($element, $value);

    // Only return the wrapper id, this prevents the computed element from
    // being reinitialized via JS after each update.
    // @see js/webform.element.computed.js
    //
    // The announce attribute allows FAPI Ajax callbacks to easily
    // trigger announcements.
    // @see js/webform.announce.js
    $t_args = ['@title' => $element['#title'], '@value' => strip_tags($value)];
    $attributes = [
      'class' => ['js-webform-computed-wrapper'],
      'id' => $element['#wrapper_id'],
      'data-webform-announce' => t('@title is @value', $t_args),
    ];
    $element['#prefix'] = '<div' . new Attribute($attributes) . '>';

    $element['#suffix'] = '</div>';

    // Remove flexbox wrapper because it already been render outside this
    // computed element's ajax wrapper.
    // @see \Drupal\webform\Plugin\WebformElementBase::prepareWrapper
    // @see \Drupal\webform\Plugin\WebformElementBase::preRenderFixFlexboxWrapper
    $preRenderFixFlexWrapper = ['Drupal\webform\Plugin\WebformElement\WebformComputedTwig', 'preRenderFixFlexboxWrapper'];
    foreach ($element['#pre_render'] as $index => $pre_render) {
      if (is_array($pre_render) && $pre_render === $preRenderFixFlexWrapper) {
        unset($element['#pre_render'][$index]);
      }
    }

    return $element;
  }

  /****************************************************************************/
  // Form/Ajax helpers and callbacks.
  /****************************************************************************/

  /**
   * Get an element's value mode/type.
   *
   * @param array $element
   *   The element.
   *
   * @return string
   *   The markup type (html or text).
   */
  public static function getMode(array $element) {
    if (empty($element['#mode']) || $element['#mode'] === static::MODE_AUTO) {
      return (WebformHtmlHelper::containsHtml($element['#template'])) ? static::MODE_HTML : static::MODE_TEXT;
    }
    else {
      return $element['#mode'];
    }
  }

  /**
   * Get the Webform submission for element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   A webform submission.
   */
  protected static function getWebformSubmission(array $element, FormStateInterface $form_state, array &$complete_form) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof WebformSubmissionForm) {
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $form_object->getEntity();

      // We must continually copy validated form values to the
      // webform submission since a computed element's value can be based on
      // another computed element's value.
      //
      // Therefore, we are creating a single clone of the webform submission
      // and only copying the submitted form values to the cached submission.
      if ($form_state->isValidationComplete() && !$form_state->isRebuilding()) {
        if (!isset(static::$submissions[$webform_submission->uuid()])) {
          static::$submissions[$webform_submission->uuid()] = clone $form_object->getEntity();
        }
        $webform_submission = static::$submissions[$webform_submission->uuid()];
        $form_object->copyFormValuesToEntity($webform_submission, $complete_form, $form_state);
      }

      return $webform_submission;
    }
    elseif (isset($element['#webform_submission'])) {
      if (is_string($element['#webform_submission'])) {
        return WebformSubmission::load($element['#webform_submission']);
      }
      else {
        return $element['#webform_submission'];
      }
    }
    else {
      return NULL;
    }
  }

}
