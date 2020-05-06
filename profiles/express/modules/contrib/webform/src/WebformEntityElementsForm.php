<?php

namespace Drupal\webform;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform manage elements YAML source form.
 */
class WebformEntityElementsForm extends BundleEntityFormBase {

  use WebformDialogFormTrait;

  /**
   * Element info manager.
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
   * Webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidatorInterface
   */
  protected $elementsValidator;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformEntityElementsForm.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityElementsValidatorInterface $elements_validator
   *   Webform element validator.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformEntityElementsValidatorInterface $elements_validator, WebformTokenManagerInterface $token_manager) {
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
    $this->tokenManager = $token_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.elements_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $t_args = [
      ':form_api_href' => 'https://www.drupal.org/node/37775',
      ':render_api_href' => 'https://www.drupal.org/developing/api/8/render',
      ':yaml_href' => 'https://en.wikipedia.org/wiki/YAML',
    ];
    $form['elements'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Elements (YAML)'),
      '#description' => $this->t('Enter a <a href=":form_api_href">Form API (FAPI)</a> and/or a <a href=":render_api_href">Render Array</a> as <a href=":yaml_href">YAML</a>.', $t_args) . '<br /><br />' .
        '<em>' . $this->t('Please note that comments are not supported and will be removed.') . '</em>',
      '#default_value' => $this->getElementsWithoutWebformTypePrefix($webform->get('elements')),
      '#required' => TRUE,
      '#element_validate' => ['::validateElementsYaml'],
      '#attributes' => ['style' => 'min-height: 300px'],
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeElement();

    $this->tokenManager->elementValidate($form);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * Element validate callback: Add 'webform_' #type prefix to elements.
   */
  public function validateElementsYaml(array &$element, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return;
    }

    $elements = $form_state->getValue('elements');
    $elements = $this->getElementsWithWebformTypePrefix($elements);
    $form_state->setValueForElement($element, $elements);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->hasAnyErrors()) {
      return;
    }

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $elements = $webform->getElementsDecoded();
    $this->addWebformTypePrefixRecursive($elements);
    $webform->setElements($elements);

    // Validate elements YAML.
    if ($messages = $this->elementsValidator->validate($webform)) {
      $form_state->setErrorByName('elements');
      foreach ($messages as $message) {
        $this->messenger()->addError($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    $t_args = ['%label' => $webform->label()];
    $this->logger('webform')->notice('Webform @label elements saved.', $context);
    $this->messenger()->addStatus($this->t('Webform %label elements saved.', $t_args));
  }

  /****************************************************************************/
  // Webform type prefix add and remove methods.
  /****************************************************************************/

  /**
   * Get elements without 'webform_' #type prefix.
   *
   * @return string
   *   Elements (YAML) without 'webform_' #type prefix.
   */
  protected function getElementsWithoutWebformTypePrefix($value) {
    $elements = Yaml::decode($value);
    if (!is_array($elements)) {
      return $value;
    }

    $this->removeWebformTypePrefixRecursive($elements);
    return WebformYaml::encode($elements);
  }

  /**
   * Remove 'webform_' prefix from #type.
   *
   * @param array $element
   *   A form element.
   */
  protected function removeWebformTypePrefixRecursive(array &$element) {
    if (isset($element['#type']) && strpos($element['#type'], 'webform_') === 0 && $this->elementManager->hasDefinition($element['#type'])) {
      $type = str_replace('webform_', '', $element['#type']);
      if (!$this->elementInfo->hasDefinition($type) && !$this->elementManager->hasDefinition($type)) {
        $element['#type'] = $type;
      }
    }

    foreach (Element::children($element) as $key) {
      if (is_array($element[$key])) {
        $this->removeWebformTypePrefixRecursive($element[$key]);
      }
    }
  }

  /**
   * Get elements with 'webform_' #type prefix.
   *
   * @return string
   *   Elements (YAML) with 'webform_' #type prefix.
   */
  protected function getElementsWithWebformTypePrefix($value) {
    $elements = Yaml::decode($value);
    if (!is_array($elements)) {
      return $value;
    }

    $this->addWebformTypePrefixRecursive($elements);
    return WebformYaml::encode($elements);
  }

  /**
   * Remove 'webform_' prefix from #type.
   *
   * @param array $element
   *   A form element.
   */
  protected function addWebformTypePrefixRecursive(array &$element) {
    if (isset($element['#type']) && !$this->elementInfo->hasDefinition($element['#type'])) {
      $type = 'webform_' . $element['#type'];
      if ($this->elementManager->hasDefinition($type)) {
        $element['#type'] = $type;
      }
    }

    foreach (Element::children($element) as $key) {
      if (is_array($element[$key])) {
        $this->addWebformTypePrefixRecursive($element[$key]);
      }
    }
  }

}
