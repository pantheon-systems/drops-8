<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'container' class.
 */
abstract class ContainerBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'title' => '',
      // Form validation.
      'required' => FALSE,
      // Randomize.
      'randomize' => FALSE,
      // Attributes.
      'attributes' => [],
      // Format.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_attributes' => [],
    ] + $this->defineDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultBaseProperties() {
    $properties = parent::defineDefaultBaseProperties();
    unset($properties['prepopulate']);
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    if (!empty($element['#randomize'])) {
      $elements = [];
      foreach (Element::children($element) as $child_key) {
        $elements[$child_key] = $element[$child_key];
        unset($element[$child_key]);
      }
      $element += WebformElementHelper::randomize($elements);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format_function = 'format' . ucfirst($format);
    $formatted_value = $this->$format_function($element, $webform_submission, $options);

    if (empty($formatted_value)) {
      return NULL;
    }

    if (is_array($formatted_value)) {
      // Add #first and #last property to $children.
      // This is used to remove returns from #last with multiple lines of
      // text.
      // @see webform-element-base-text.html.twig
      reset($formatted_value);
      $first_key = key($formatted_value);
      if (isset($formatted_value[$first_key]['#options'])) {
        $formatted_value[$first_key]['#options']['first'] = TRUE;
      }

      end($formatted_value);
      $last_key = key($formatted_value);
      if (isset($formatted_value[$last_key]['#options'])) {
        $formatted_value[$last_key]['#options']['last'] = TRUE;
      }
    }

    return [
      '#theme' => 'webform_container_base_' . $format,
      '#element' => $element,
      '#value' => $formatted_value,
      '#webform_submission' => $webform_submission,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $children = $view_builder->buildElements($element, $webform_submission, $options, 'html');
    if (empty($children)) {
      return [];
    }

    $format = $this->getItemFormat($element);

    // Emails can only display div containers with <h3>.
    if (!empty($options['email'])) {
      $format = 'header';
    }

    // Build format attributes.
    $attributes = (isset($element['#format_attributes'])) ? $element['#format_attributes'] : [];
    $attributes += ['class' => []];

    switch ($format) {
      case 'details':
      case 'details-closed':
        $attributes['data-webform-element-id'] = $element['#webform_id'];
        $attributes['class'][] = 'webform-container';
        $attributes['class'][] = 'webform-container-type-details';
        return [
          '#type' => 'details',
          '#title' => $element['#title'],
          '#id' => $element['#webform_id'],
          '#open' => ($format === 'details-closed') ? FALSE : TRUE,
          '#attributes' => $attributes,
          '#children' => $children,
        ];

      case 'fieldset':
        $attributes['class'][] = 'webform-container';
        $attributes['class'][] = 'webform-container-type-fieldset';

        return [
          '#type' => 'fieldset',
          '#title' => $element['#title'],
          '#id' => $element['#webform_id'],
          '#attributes' => $attributes,
          '#children' => $children,
        ];

      case 'header':
      default:
        return [
          '#type' => 'webform_section',
          '#id' => $element['#webform_id'],
          '#title' => $element['#title'],
          '#title_tag' => \Drupal::config('webform.settings')->get('element.default_section_title_tag'),
          '#attributes' => $attributes,
        ] + $children;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $children = $view_builder->buildElements($element, $webform_submission, $options, 'text');
    if (empty($children)) {
      return [];
    }

    $build = [];
    if (!empty($element['#title'])) {
      $build['title'] = [
        '#markup' => $element['#title'],
        '#suffix' => PHP_EOL,
      ];
      $build['divider'] = [
        '#markup' => str_repeat('-', mb_strlen($element['#title'])),
        '#suffix' => PHP_EOL,
      ];
    }
    $build['children'] = $children;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatCustomItem($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = [], array $context = []) {
    $name = strtolower($type);

    // Parse children from template and children to context.
    $template = trim($element['#format_' . $name]);
    if (strpos($template, 'children') != FALSE) {
      /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
      $context['children'] = $view_builder->buildElements($element, $webform_submission, $options, $name);
    }

    return parent::formatCustomItem($type, $element, $webform_submission, $options, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'header';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [
      'header' => $this->t('Header'),
      'fieldset' => $this->t('Fieldset'),
      'details' => $this->t('Details (opened)'),
      'details-closed' => $this->t('Details (closed)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Randomize.
    $form['element']['randomize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Randomize elements'),
      '#description' => $this->t('Randomizes the order of the sub-element when they are displayed in the webform.'),
      '#return_value' => TRUE,
    ];

    // Containers are wrappers, therefore wrapper classes should be used by the
    // container element.
    $form['element_attributes']['attributes']['#classes'] = $this->configFactory->get('webform.settings')->get('element.wrapper_classes');

    // Containers can only hide the title using #title_display: invisible.
    // @see fieldset.html.twig
    // @see webform-section.html.twig
    $form['form']['display_container']['title_display']['#options'] = [
      'invisible' => $this->t('Invisible'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Containers should never have values and therefore should never have
    // a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
