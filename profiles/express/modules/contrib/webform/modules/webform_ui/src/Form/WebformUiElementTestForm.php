<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a test webform for webform elements.
 *
 * This webform is only visible if the webform_devel.module is enabled.
 *
 * @see \Drupal\webform\Controller\WebformPluginElementController::index
 */
class WebformUiElementTestForm extends WebformUiElementFormBase {

  /**
   * Type of webform element being tested.
   *
   * @var string
   */
  protected $type;

  /**
   * A webform element.
   *
   * @var \Drupal\webform\Plugin\WebformElementInterface
   */
  protected $webformElement;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_ui_element_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    // Create a temp webform.
    $this->webform = Webform::create(['id' => '_webform_ui_temp_form']);

    $this->type = $type;

    if (!$this->elementManager->hasDefinition($type)) {
      throw new NotFoundHttpException();
    }

    if ($element = \Drupal::request()->getSession()->get('webform_ui_test_element_' . $type)) {
      $this->element = $element;
    }
    else {
      $element = ['#type' => $type] + $this->getWebformElementPlugin()->preview();
      $this->element = $element;
    }

    $webform_element = $this->getWebformElementPlugin();
    $form['#title'] = $this->t('Test %type element', ['%type' => $type]);

    if ($element) {
      $webform_submission = WebformSubmission::create(['webform' => $this->webform]);
      $webform_element->initialize($this->element);
      $webform_element->prepare($this->element, $webform_submission);

      $form['test'] = [
        '#type' => 'details',
        '#title' => $this->t('Element test'),
        '#open' => TRUE,
        '#attributes' => [
          'style' => 'background-color: #f5f5f2',
        ],
        'element' => $this->element,
        'hr' => ['#markup' => '<hr />'],
      ];

      if (isset($element['#default_value'])) {
        $html = $webform_element->formatHtml($element + ['#value' => $element['#default_value']], $webform_submission);
        $form['test']['html'] = [
          '#type' => 'item',
          '#title' => $this->t('HTML'),
          '#markup' => (is_array($html)) ? $this->renderer->render($html) : $html,
          '#allowed_tag' => Xss::getAdminTagList(),
        ];
        $form['test']['text'] = [
          '#type' => 'item',
          '#title' => $this->t('Plain text'),
          '#markup' => '<pre>' . $webform_element->formatText($element + ['#value' => $element['#default_value']], $webform_submission) . '</pre>',
          '#allowed_tag' => Xss::getAdminTagList(),
        ];
      }

      $form['test']['code'] = [
        '#type' => 'item',
        '#title' => $this->t('Source'),
        'source' => [
          '#theme' => 'webform_codemirror',
          '#type' => 'yaml',
          '#code' => Yaml::encode($this->convertTranslatableMarkupToStringRecursive($element)),
        ],
      ];

      $form['test']['render_array'] = [
        '#type' => 'details',
        '#title' => $this->t('Render array'),
        '#desciption' => $this->t("Below is the element's final render array."),
        'source' => [
          '#theme' => 'webform_codemirror',
          '#type' => 'yaml',
          '#code' => Yaml::encode($this->convertTranslatableMarkupToStringRecursive($this->element)),
        ],
      ];
    }

    $form['key'] = [
      '#type' => 'value',
      '#value' => 'element',
    ];
    $form['parent_key'] = [
      '#type' => 'value',
      '#value' => '',
    ];

    $form['properties'] = $webform_element->buildConfigurationForm(['#tabs' => FALSE], $form_state);
    $form['properties']['#tree'] = TRUE;
    $form['properties']['custom']['#open'] = TRUE;

    $form['properties']['element']['type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => $type,
      '#weight' => -100,
      '#parents' => ['type'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test'),
      '#button_type' => 'primary',
    ];
    if (\Drupal::request()->getSession()->get('webform_ui_test_element_' . $type)) {
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::reset'],
      ];
    }

    // Clear all messages including 'Unable to display this webform...' which is
    // generated because we are using a temp webform.
    // drupal_get_messages();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    \Drupal::request()->getSession()->remove('webform_ui_test_element_' . $this->type);
    drupal_set_message($this->t('Webform element %type test has been reset.', ['%type' => $this->type]));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Rebuild is throwing the below error.
    // LogicException: Settings can not be serialized.
    // $form_state->setRebuild();
    // @todo Determine what object is being serialized with webform
    //
    // The webform element configuration is stored in the 'properties' key in
    // the webform, pass that through for submission.
    $element_form_state = clone $form_state;
    $element_form_state->setValues($form_state->getValue('properties'));

    $properties = $this->getWebformElementPlugin()->getConfigurationFormProperties($form, $element_form_state);

    // Set #default_value using 'test' element value.
    if ($element_value = $form_state->getValue('element')) {
      $properties['#default_value'] = $element_value;
    }

    \Drupal::request()->getSession()->set('webform_ui_test_element_' . $this->type, $properties);

    drupal_set_message($this->t('Webform element %type test has been updated.', ['%type' => $this->type]));
  }

  /**
   * Determines if the webform element key already exists.
   *
   * @param string $key
   *   The webform element key.
   *
   * @return bool
   *   TRUE if the webform element key, FALSE otherwise.
   */
  public function exists($key) {
    return FALSE;
  }

  /**
   * Convert all translatable markup to strings.
   *
   * This allows element to be serialized.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   The element with all translatable markup converted to strings.
   */
  protected function convertTranslatableMarkupToStringRecursive(array $element) {
    foreach ($element as $key => $value) {
      if ($value instanceof TranslatableMarkup) {
        $element[$key] = (string) $value;
      }
      elseif (is_array($value)) {
        $element[$key] = $this->convertTranslatableMarkupToStringRecursive($value);
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformElementPlugin() {
    if (empty($this->element)) {
      return $this->elementManager->getElementInstance(['#type' => $this->type]);
    }
    else {
      return parent::getWebformElementPlugin();
    }
  }

}
