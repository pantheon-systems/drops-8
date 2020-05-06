<?php

namespace Drupal\webform_ui\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\webform\Form\WebformDeleteFormBase;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformElementVariantInterface;
use Drupal\webform\WebformEntityElementsValidatorInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform for deleting a webform element.
 */
class WebformUiElementDeleteForm extends WebformDeleteFormBase {

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
   * @var \Drupal\webform\WebformEntityElementsValidatorInterface
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

  /****************************************************************************/
  // Delete form.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->isDialog()) {
      $t_args = [
        '@title' => $this->getElementTitle(),
      ];
      return $this->t("Delete the '@title' element?", $t_args);
    }
    else {
      $t_args = [
        '%webform' => $this->webform->label(),
        '%title' => $this->getElementTitle(),
      ];
      return $this->t('Delete the %title element from the %webform webform?', $t_args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    $t_args = ['%title' => $this->getElementTitle()];
    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to delete the %title element?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $element_plugin = $this->getWebformElementPlugin();

    $items = [];
    $items[] = $this->t('Remove this element');
    $items[] = $this->t('Delete any submission data associated with this element');
    if ($element_plugin->isContainer($this->element)) {
      $items[] = $this->t('Delete all child elements');
    }
    if ($element_plugin instanceof WebformElementVariantInterface) {
      $items[] = $this->t('Delete all related variants');
    }
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    $elements = $this->getDeletedElementsItemList($this->element['#webform_children']);
    if ($elements) {
      return [
        '#type' => 'details',
        '#title' => $this->t('Nested elements being deleted'),
        'elements' => $elements,
      ];
    }
    else {
      return [];
    }
  }

  /****************************************************************************/
  // Form methods.
  /****************************************************************************/

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

    $this->messenger()->addStatus($this->t('The webform element %title has been deleted.', ['%title' => $this->getElementTitle()]));

    $query = [];
    // Variants require the entire page to be reloaded so that Variants tab
    // can be hidden.
    if ($this->getWebformElementPlugin() instanceof WebformElementVariantInterface) {
      $query = ['reload' => 'true'];
    }

    $form_state->setRedirectUrl($this->webform->toUrl('edit-form', ['query' => $query]));
  }

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

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
