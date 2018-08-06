<?php

namespace Drupal\webform_ui\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformEntityElementsValidatorInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform for deleting a webform element.
 */
class WebformUiElementDeleteForm extends ConfirmFormBase {

  use WebformDialogFormTrait;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidator
   */
  protected $elementsValidator;

  /**
   * The webform containing the webform handler to be deleted.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * A webform element.
   *
   * @var \Drupal\webform\Plugin\WebformElementInterface
   */
  protected $webformElement;

  /**
   * The webform element key.
   *
   * @var string
   */
  protected $key;

  /**
   * The webform element.
   *
   * @var array
   */
  protected $element;

  /**
   * Constructs a WebformUiElementDeleteForm.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityElementsValidatorInterface $elements_validator
   *   Webform element validator.
   */
  public function __construct(RendererInterface $renderer, WebformElementManagerInterface $element_manager, WebformEntityElementsValidatorInterface $elements_validator) {
    $this->renderer = $renderer;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.elements_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $t_args = [
      '%element' => $this->getElementTitle(),
      '%webform' => $this->webform->label(),
    ];

    $build = [];
    $element_plugin = $this->getWebformElementPlugin();
    if ($element_plugin->isContainer($this->element)) {
      $build['warning'] = [
        '#markup' => $this->t('This will immediately delete the %element container and all nested elements within %element from the %webform webform. This cannot be undone.', $t_args),
      ];
    }
    else {
      $build['warning'] = [
        '#markup' => $this->t('This will immediately delete the %element element from the %webform webform. This cannot be undone.', $t_args),
      ];
    }

    if ($this->element['#webform_children']) {
      $build['elements'] = $this->getDeletedElementsItemList($this->element['#webform_children']);
      $build['elements']['#title'] = t('The below nested elements will be also deleted.');
    }

    return $this->renderer->renderPlain($build);
  }

  /**
   * Get deleted elements as item list.
   *
   * @param array $children
   *   An array child key.
   *
   * @return array
   *   A render array representing an item list of elements.
   */
  protected function getDeletedElementsItemList(array $children) {
    if (empty($children)) {
      return [];
    }

    $items = [];
    foreach ($children as $key) {
      $element = $this->webform->getElement($key);
      if (isset($element['#title'])) {
        $title = new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $key]);
      }
      else {
        $title = $key;
      }
      $items[$key]['title'] = ['#markup' => $title];
      if ($element['#webform_children']) {
        $items[$key]['items'] = $this->getDeletedElementsItemList($element['#webform_children']);
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %title element from the %webform webform?', ['%webform' => $this->webform->label(), '%title' => $this->getElementTitle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->webform->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_ui_element_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL) {
    $this->webform = $webform;
    $this->key = $key;
    $this->element = $webform->getElement($key);

    if ($this->element === NULL) {
      throw new NotFoundHttpException();
    }

    $form = parent::buildForm($form, $form_state);
    $form = $this->buildDialogConfirmForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->webform->deleteElement($this->key);
    $this->webform->save();

    drupal_set_message($this->t('The webform element %title has been deleted.', ['%title' => $this->getElementTitle()]));
    $form_state->setRedirectUrl($this->webform->toUrl('edit-form'));
  }

  /**
   * Get the webform element's title or key.
   *
   * @return string
   *   The webform element's title or key,
   */
  protected function getElementTitle() {
    return (!empty($this->element['#title'])) ? $this->element['#title'] : $this->key;
  }

  /**
   * Return the webform element plugin associated with this form.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface
   *   A webform element.
   */
  protected function getWebformElementPlugin() {
    return $this->elementManager->getElementInstance($this->element);
  }

}
