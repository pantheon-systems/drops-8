<?php

namespace Drupal\webform;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for controller for webform.
 */
class WebformEntityForm extends BundleEntityFormBase {

  use WebformDialogTrait;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidator
   */
  protected $elementsValidator;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformEntityForm.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element manager.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityElementsValidator $elements_validator
   *   Webform element validator.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The token manager.
   */
  public function __construct(RendererInterface $renderer, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformEntityElementsValidator $elements_validator, WebformTokenManagerInterface $token_manager) {
    $this->renderer = $renderer;
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
      $container->get('renderer'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.elements_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation == 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }

    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Customize title for duplicate webform.
    if ($this->operation == 'duplicate') {
      // Display custom title.
      $form['#title'] = $this->t("Duplicate '@label' form", ['@label' => $webform->label()]);
    }

    $form = parent::buildForm($form, $form_state);

    return $this->buildFormDialog($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Only display id, title, and description for new webforms.
    // Once a webform is created this information is moved to the webform's settings
    // tab.
    if ($webform->isNew()) {
      $form['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $webform->id(),
        '#machine_name' => [
          'exists' => '\Drupal\webform\Entity\Webform::load',
          'source' => ['title'],
        ],
        '#maxlength' => 32,
        '#disabled' => (bool) $webform->id() && $this->operation != 'duplicate',
        '#required' => TRUE,
      ];
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#maxlength' => 255,
        '#default_value' => $webform->label(),
        '#required' => TRUE,
        '#id' => 'title',
        '#attributes' => [
          'autofocus' => 'autofocus',
        ],
      ];
      $form['description'] = [
        '#type' => 'webform_html_editor',
        '#title' => $this->t('Administrative description'),
        '#default_value' => $webform->get('description'),
      ];
      /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
      $webform_storage = $this->entityTypeManager->getStorage('webform');
      $form['category'] = [
        '#type' => 'webform_select_other',
        '#title' => $this->t('Category'),
        '#options' => $webform_storage->getCategories(),
        '#empty_option' => '<' . $this->t('None') . '>',
        '#default_value' => $webform->get('category'),
      ];
      $form = $this->protectBundleIdElement($form);
    }

    // Call the isolated edit webform that can be overridden by the
    // webform_ui.module.
    $form = $this->editForm($form, $form_state);

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
   * Edit webform element's source code webform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The webform structure.
   */
  protected function editForm(array $form, FormStateInterface $form_state) {
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
      '#description' => $this->t('Enter a <a href=":form_api_href">Form API (FAPI)</a> and/or a <a href=":render_api_href">Render Array</a> as <a href=":yaml_href">YAML</a>.', $t_args) . '<br/>' .
      '<em>' . $this->t('Please note that comments are not supported and will be removed.') . '</em>',
      '#default_value' => $this->getElementsWithoutWebformTypePrefix($webform->get('elements')),
      '#required' => TRUE,
      '#element_validate' => ['::validateElementsYaml'],
    ];
    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
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

    // Validate elements YAML.
    if ($messages = $this->elementsValidator->validate($this->getEntity())) {
      $form_state->setErrorByName('elements');
      foreach ($messages as $message) {
        drupal_set_message($message, 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirectUrl($this->getRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    if ($url = $this->getRedirectDestinationUrl()) {
      return $url;
    }
    return Url::fromRoute('entity.webform.edit_form', ['webform' => $this->getEntity()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $is_new = $webform->isNew();
    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'edit-form')->toString()
    ];
    $t_args = ['%label' => $webform->label()];
    if ($is_new) {
      $this->logger('webform')->notice('Webform @label created.', $context);
      drupal_set_message($this->t('Webform %label created.', $t_args));
    }
    else {
      $this->logger('webform')->notice('Webform @label elements saved.', $context);
      drupal_set_message($this->t('Webform %label elements saved.', $t_args));
    }
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
    return WebformYaml::tidy(Yaml::encode($elements));
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
    return WebformYaml::tidy(Yaml::encode($elements));
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
