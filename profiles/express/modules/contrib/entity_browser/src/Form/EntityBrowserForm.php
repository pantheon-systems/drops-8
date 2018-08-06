<?php

namespace Drupal\entity_browser\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\entity_browser\DisplayAjaxInterface;
use Drupal\entity_browser\EntityBrowserFormInterface;
use Drupal\entity_browser\EntityBrowserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The entity browser form.
 */
class EntityBrowserForm extends FormBase implements EntityBrowserFormInterface {

  /**
   * UUID generator service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The entity browser object.
   *
   * @var \Drupal\entity_browser\EntityBrowserInterface
   */
  protected $entityBrowser;

  /**
   * The entity browser selection storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $selectionStorage;

  /**
   * Constructs a EntityBrowserForm object.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator service.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $selection_storage
   *   Selection storage.
   */
  public function __construct(UuidInterface $uuid_generator, KeyValueStoreExpirableInterface $selection_storage) {
    $this->uuidGenerator = $uuid_generator;
    $this->selectionStorage = $selection_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uuid'),
      $container->get('entity_browser.selection_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_' . $this->entityBrowser->id() . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityBrowser(EntityBrowserInterface $entity_browser) {
    $this->entityBrowser = $entity_browser;
  }

  /**
   * Initializes form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function init(FormStateInterface $form_state) {
    // Flag that this form has been initialized.
    $form_state->set('entity_form_initialized', TRUE);
    if ($this->getRequest()->query->has('uuid')) {
      $form_state->set(['entity_browser', 'instance_uuid'], $this->getRequest()->query->get('uuid'));
    }
    else {
      $form_state->set(['entity_browser', 'instance_uuid'], $this->uuidGenerator->generate());
    }
    $form_state->set(['entity_browser', 'selected_entities'], []);
    $form_state->set(['entity_browser', 'validators'], []);
    $form_state->set(['entity_browser', 'widget_context'], []);
    $form_state->set(['entity_browser', 'selection_completed'], FALSE);

    // Initialize form state with persistent data, if present.
    if ($storage = $this->selectionStorage->get($form_state->get(['entity_browser', 'instance_uuid']))) {
      foreach ($storage as $key => $value) {
        $form_state->set(['entity_browser', $key], $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // During the initial form build, add this form object to the form state and
    // allow for initial preparation before form building and processing.
    if (!$form_state->has('entity_form_initialized')) {
      $this->init($form_state);
    }

    $this->isFunctionalForm();

    $form['#attributes']['class'][] = 'entity-browser-form';
    if (!empty($form_state->get(['entity_browser', 'instance_uuid']))) {
      $form['#attributes']['data-entity-browser-uuid'] = $form_state->get(['entity_browser', 'instance_uuid']);
    }
    $form['#browser_parts'] = [
      'widget_selector' => 'widget_selector',
      'widget' => 'widget',
      'selection_display' => 'selection_display',
    ];
    $this->entityBrowser
      ->getWidgetSelector()
      ->setDefaultWidget($this->getCurrentWidget($form_state));
    $form[$form['#browser_parts']['widget_selector']] = $this->entityBrowser
      ->getWidgetSelector()
      ->getForm($form, $form_state);
    $form[$form['#browser_parts']['widget']] = $this->entityBrowser
      ->getWidgets()
      ->get($this->getCurrentWidget($form_state))
      ->getForm($form, $form_state, $this->entityBrowser->getAdditionalWidgetParameters());

    $form[$form['#browser_parts']['selection_display']] = $this->entityBrowser
      ->getSelectionDisplay()
      ->getForm($form, $form_state);

    if ($this->entityBrowser->getDisplay() instanceof DisplayAjaxInterface) {
      $this->entityBrowser->getDisplay()->addAjax($form);
    }

    $form['#attached']['library'][] = 'entity_browser/entity_browser';

    return $form;
  }

  /**
   * Check if entity browser with selected configuration combination can work.
   */
  protected function isFunctionalForm() {
    /** @var \Drupal\entity_browser\WidgetInterface $widget */
    foreach ($this->entityBrowser->getWidgets() as $widget) {
      /** @var \Drupal\entity_browser\SelectionDisplayInterface $selectionDisplay */
      $selectionDisplay = $this->entityBrowser->getSelectionDisplay();

      if ($widget->requiresJsCommands() && !$selectionDisplay->supportsJsCommands()) {
        throw new ConfigException('Used entity browser selection display cannot work in combination with settings defined for used selection widget.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->entityBrowser->getWidgetSelector()->validate($form, $form_state);
    $this->entityBrowser->getWidgets()->get($this->getCurrentWidget($form_state))->validate($form, $form_state);
    $this->entityBrowser->getSelectionDisplay()->validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $original_widget = $this->getCurrentWidget($form_state);
    if ($new_widget = $this->entityBrowser->getWidgetSelector()->submit($form, $form_state)) {
      $this->setCurrentWidget($new_widget, $form_state);
    }

    // Only call widget submit if we didn't change the widget.
    if ($original_widget == $this->getCurrentWidget($form_state)) {
      $this->entityBrowser
        ->getWidgets()
        ->get($this->getCurrentWidget($form_state))
        ->submit($form[$form['#browser_parts']['widget']], $form, $form_state);

      $this->entityBrowser
        ->getSelectionDisplay()
        ->submit($form, $form_state);
    }

    if (!$this->isSelectionCompleted($form_state)) {
      $form_state->setRebuild();
    }
    else {
      $this->entityBrowser->getDisplay()->selectionCompleted($this->getSelectedEntities($form_state));
    }
  }

  /**
   * Returns the widget that is currently selected.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   ID of currently selected widget.
   */
  protected function getCurrentWidget(FormStateInterface $form_state) {
    // Do not use has() as that returns TRUE if the value is NULL.
    if (!$form_state->get('entity_browser_current_widget')) {
      $form_state->set('entity_browser_current_widget', $this->entityBrowser->getFirstWidget());
    }

    return $form_state->get('entity_browser_current_widget');
  }

  /**
   * Sets widget that is currently active.
   *
   * @param string $widget
   *   New active widget UUID.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function setCurrentWidget($widget, FormStateInterface $form_state) {
    $form_state->set('entity_browser_current_widget', $widget);
  }

  /**
   * Indicates selection is done.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return bool
   *   Indicates selection is done.
   */
  protected function isSelectionCompleted(FormStateInterface $form_state) {
    return (bool) $form_state->get(['entity_browser', 'selection_completed']);
  }

  /**
   * Returns currently selected entities.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of currently selected entities.
   */
  protected function getSelectedEntities(FormStateInterface $form_state) {
    return $form_state->get(['entity_browser', 'selected_entities']);
  }

}
